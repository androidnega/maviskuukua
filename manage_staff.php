<?php
require 'layout.php';
require_admin();

if (!can_manage_staff_accounts()) {
    flash('admin_notice', 'You do not have access to staff accounts.');
    redirect('admin.php');
}

$pdo = db();
$notice = flash('admin_notice');
$actorId = (int)$_SESSION['admin_id'];

function notify_coordinators_staff_removal_request(PDO $pdo, array $target): void {
    $creatorId = (int)($target['created_by_admin_id'] ?? 0);
    $recipients = [];
    if ($creatorId > 0) {
        $stmt = $pdo->prepare('SELECT id, phone, role FROM admins WHERE id = ?');
        $stmt->execute([$creatorId]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($c && (string)$c['role'] === ROLE_COORDINATOR) {
            $recipients[] = $c;
        }
    }
    if ($recipients === []) {
        $recipients = $pdo->query("SELECT id, phone FROM admins WHERE role = '" . ROLE_COORDINATOR . "'")->fetchAll(PDO::FETCH_ASSOC);
    }
    $uname = (string)($target['username'] ?? '');
    $tid = (int)($target['id'] ?? 0);
    $msg = 'Kuukua Cares: Super admin requested removal of field officer "' . $uname . '" (staff ID ' . $tid . '). Sign in to Staff Accounts to approve or decline.';
    foreach ($recipients as $r) {
        $phone = sms_normalize_ghana_phone((string)($r['phone'] ?? ''));
        if ($phone !== null) {
            $result = arkesel_send_sms($pdo, $phone, $msg);
            if (!$result['ok']) {
                error_log('staff removal notify SMS failed: ' . ($result['error'] ?? 'unknown'));
            }
        }
    }
}

function random_staff_password(int $length = 12): string {
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['staff_action'] ?? ''));

    if ($action === 'approve_staff_removal') {
        if (!is_coordinator()) {
            flash('admin_notice', 'Only a coordinator can approve this removal.');
            redirect('manage_staff.php');
        }
        $requestId = (int)($_POST['request_id'] ?? 0);
        $stmt = $pdo->prepare(
            'SELECT r.id, r.target_admin_id FROM staff_delete_requests r WHERE r.id = ?'
        );
        $stmt->execute([$requestId]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$req) {
            flash('admin_notice', 'That request is no longer pending.');
            redirect('manage_staff.php');
        }
        $stmt = $pdo->prepare('SELECT id, username, role, phone, created_at, created_by_admin_id FROM admins WHERE id = ?');
        $stmt->execute([(int)$req['target_admin_id']]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || !coordinator_can_approve_fo_delete($pdo, $actorId, $target)) {
            flash('admin_notice', 'You cannot approve that removal.');
            redirect('manage_staff.php');
        }
        try {
            staff_perform_hard_delete($pdo, $target, ['via_coordinator_approval' => true]);
        } catch (Throwable $e) {
            error_log('manage_staff approve removal failed: ' . $e->getMessage());
            flash('admin_notice', 'Could not complete removal. Try again or check server logs.');
            redirect('manage_staff.php');
        }
        flash('admin_notice', 'Field officer account removed after your approval.');
        redirect('manage_staff.php');
    }

    if ($action === 'reject_staff_removal') {
        if (!is_coordinator()) {
            flash('admin_notice', 'Only a coordinator can decline this removal.');
            redirect('manage_staff.php');
        }
        $requestId = (int)($_POST['request_id'] ?? 0);
        $stmt = $pdo->prepare(
            'SELECT r.id, r.target_admin_id FROM staff_delete_requests r WHERE r.id = ?'
        );
        $stmt->execute([$requestId]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$req) {
            flash('admin_notice', 'That request is no longer pending.');
            redirect('manage_staff.php');
        }
        $stmt = $pdo->prepare('SELECT id, username, role, created_at, created_by_admin_id FROM admins WHERE id = ?');
        $stmt->execute([(int)$req['target_admin_id']]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || !coordinator_can_approve_fo_delete($pdo, $actorId, $target)) {
            flash('admin_notice', 'You cannot decline that request.');
            redirect('manage_staff.php');
        }
        $pdo->prepare('DELETE FROM staff_delete_requests WHERE id = ?')->execute([$requestId]);
        log_admin_action($pdo, 'staff_removal_rejected', 'admin', (int)$target['id'], [
            'target_username' => $target['username'],
        ]);
        flash('admin_notice', 'Removal request declined. The account stays active.');
        redirect('manage_staff.php');
    }

    if ($action === 'cancel_staff_removal') {
        if (!is_super_admin()) {
            flash('admin_notice', 'Only the super admin can cancel a pending removal.');
            redirect('manage_staff.php');
        }
        $targetId = (int)($_POST['target_id'] ?? 0);
        $pdo->prepare('DELETE FROM staff_delete_requests WHERE target_admin_id = ?')->execute([$targetId]);
        log_admin_action($pdo, 'staff_removal_cancelled', 'admin', $targetId, []);
        flash('admin_notice', 'Pending removal cancelled.');
        redirect('manage_staff.php');
    }

    if ($action === 'request_staff_removal') {
        if (!is_super_admin()) {
            flash('admin_notice', 'Only the super admin can request removal.');
            redirect('manage_staff.php');
        }
        $targetId = (int)($_POST['target_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, username, role, created_at, created_by_admin_id FROM admins WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (
            !$target
            || (string)$target['role'] !== ROLE_FIELD_OFFICER
            || !staff_target_deletable_by_actor($target)
        ) {
            flash('admin_notice', 'That field officer cannot be queued for removal.');
            redirect('manage_staff.php');
        }
        $exists = $pdo->prepare('SELECT id FROM staff_delete_requests WHERE target_admin_id = ?');
        $exists->execute([$targetId]);
        if ($exists->fetch()) {
            flash('admin_notice', 'A removal request is already pending for this account.');
            redirect('manage_staff.php');
        }
        $pdo->prepare(
            'INSERT INTO staff_delete_requests (target_admin_id, requested_by_admin_id, requested_at) VALUES (?,?,?)'
        )->execute([$targetId, $actorId, date('c')]);
        notify_coordinators_staff_removal_request($pdo, $target);
        log_admin_action($pdo, 'staff_removal_requested', 'admin', $targetId, [
            'target_username' => $target['username'],
        ]);
        flash('admin_notice', 'Removal requested. Coordinators were notified by SMS (where phone is on file). They must approve on Staff Accounts before the account is deleted.');
        redirect('manage_staff.php');
    }

    if ($action === 'super_admin_force_delete') {
        if (!is_super_admin()) {
            flash('admin_notice', 'Only the super admin can force-delete accounts.');
            redirect('manage_staff.php');
        }
        $targetId = (int)($_POST['target_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, username, role, created_by_admin_id FROM admins WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || !staff_target_deletable_by_actor($target)) {
            flash('admin_notice', 'That account cannot be deleted.');
            redirect('manage_staff.php');
        }
        if ((string)$target['role'] !== ROLE_FIELD_OFFICER) {
            flash('admin_notice', 'Force delete applies to field officers. Use Delete account for coordinators.');
            redirect('manage_staff.php');
        }

        $pendingStmt = $pdo->prepare('SELECT id FROM staff_delete_requests WHERE target_admin_id = ?');
        $pendingStmt->execute([$targetId]);
        $hadPendingRemoval = (bool)$pendingStmt->fetch(PDO::FETCH_ASSOC);

        $creatorMeta = null;
        $creatorId = (int)($target['created_by_admin_id'] ?? 0);
        if ($creatorId > 0) {
            $cst = $pdo->prepare('SELECT id, username, role FROM admins WHERE id = ?');
            $cst->execute([$creatorId]);
            $creatorMeta = $cst->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $superName = (string)($_SESSION['admin_username'] ?? '');
        $auditExtras = [
            'super_admin_override' => true,
            'super_admin_actor_id' => $actorId,
            'super_admin_actor_username' => $superName,
            'had_pending_removal_request' => $hadPendingRemoval,
            'created_by_admin_id' => $creatorId > 0 ? $creatorId : null,
            'created_by_username' => $creatorMeta['username'] ?? null,
            'created_by_role' => $creatorMeta['role'] ?? null,
        ];

        try {
            staff_perform_hard_delete($pdo, $target, $auditExtras);
        } catch (Throwable $e) {
            error_log('manage_staff force delete failed: ' . $e->getMessage());
            flash('admin_notice', 'Delete failed. Try again or check server logs.');
            redirect('manage_staff.php');
        }

        if ($creatorMeta !== null && (string)$creatorMeta['role'] === ROLE_COORDINATOR) {
            log_admin_action($pdo, 'staff_override_delete_coordinator_context', 'admin', (int)$creatorMeta['id'], [
                'note' => 'Audit link: a field officer created under this coordinator was removed by super admin (workflow overridden).',
                'deleted_staff_id' => $targetId,
                'deleted_staff_username' => $target['username'],
                'super_admin_actor_id' => $actorId,
                'super_admin_actor_username' => $superName,
                'had_pending_removal_request' => $hadPendingRemoval,
            ]);
        }

        flash('admin_notice', 'Account permanently removed (super admin override). Audit logs recorded.');
        redirect('manage_staff.php');
    }

    if ($action === 'delete') {
        if (!is_super_admin()) {
            flash('admin_notice', 'Only the super admin can delete staff accounts.');
            redirect('manage_staff.php');
        }
        $targetId = (int)($_POST['target_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, username, role FROM admins WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || !staff_target_deletable_by_actor($target)) {
            flash('admin_notice', 'That account cannot be deleted.');
            redirect('manage_staff.php');
        }
        if ((string)$target['role'] === ROLE_FIELD_OFFICER) {
            flash('admin_notice', 'Field officers: use Request removal, or Force delete (override) as super admin.');
            redirect('manage_staff.php');
        }

        try {
            staff_perform_hard_delete($pdo, $target, [
                'via_super_admin_standard_delete' => true,
                'super_admin_actor_id' => $actorId,
                'super_admin_actor_username' => (string)($_SESSION['admin_username'] ?? ''),
            ]);
        } catch (Throwable $e) {
            error_log('manage_staff delete failed: ' . $e->getMessage());
            flash('admin_notice', 'Delete failed. Try again or check server logs.');
            redirect('manage_staff.php');
        }

        flash('admin_notice', 'Account removed.');
        redirect('manage_staff.php');
    }

    if ($action === 'reset_password') {
        $targetId = (int)($_POST['target_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, username, role, phone FROM admins WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || !staff_target_password_resettable_by_actor($target)) {
            flash('admin_notice', 'You cannot reset that account password.');
            redirect('manage_staff.php');
        }
        $phoneNorm = sms_normalize_ghana_phone((string)($target['phone'] ?? ''));
        if ($phoneNorm === null) {
            flash('admin_notice', 'No valid phone number on file for this user. Update their phone before resetting.');
            redirect('manage_staff.php');
        }
        $plain = random_staff_password(12);
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $targetId]);

        $loginUrl = 'https://kuukuacares.com/login.php';
        $uname = (string)$target['username'];
        $msg = 'Kuukua Cares password reset. OTP: %otp_code%. Username: ' . $uname . ' New password: ' . $plain . ' Login: ' . $loginUrl . ' — Sponsored by Mavis Kuukua Bissue. Change password after login.';
        $otpResult = arkesel_otp_generate($pdo, $phoneNorm, $msg);

        log_admin_action($pdo, 'staff_password_reset', 'admin', $targetId, [
            'username' => $uname,
            'role' => $target['role'],
            'otp_ok' => $otpResult['ok'],
            'otp_error' => $otpResult['error'] ?? null,
            'via_arkesel_otp' => true,
            'otp_delivery' => $otpResult['delivery'] ?? 'otp_api',
        ]);

        if ($otpResult['ok']) {
            $del = $otpResult['delivery'] ?? 'otp_api';
            flash(
                'admin_notice',
                $del === 'sms_fallback'
                    ? 'New password saved. Login details sent by SMS (managed OTP API unavailable; standard SMS path used).'
                    : 'New password saved and OTP SMS sent with login details.'
            );
        } else {
            flash('admin_notice', 'Password was reset but OTP SMS failed: ' . ($otpResult['error'] ?? 'unknown') . '. Set a new password again or share securely.');
        }
        redirect('manage_staff.php');
    }

    if ($action !== 'create') {
        flash('admin_notice', 'Invalid request.');
        redirect('manage_staff.php');
    }

    // Create account — password generated server-side and sent only via OTP SMS
    $username = trim((string)($_POST['username'] ?? ''));
    $phoneRaw = trim((string)($_POST['phone'] ?? ''));
    $roleIn = trim((string)($_POST['role'] ?? ''));
    $password = random_staff_password(14);

    $allowedRole = ROLE_FIELD_OFFICER;
    if (is_super_admin()) {
        if ($roleIn === ROLE_COORDINATOR || $roleIn === ROLE_FIELD_OFFICER) {
            $allowedRole = $roleIn;
        }
    }

    $err = null;
    if ($username === '' || strlen($username) < 3) {
        $err = 'Username must be at least 3 characters.';
    } else {
        $dup = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
        $dup->execute([$username]);
        if ($dup->fetch()) {
            $err = 'That username is already taken.';
        }
    }

    $phoneNorm = sms_normalize_ghana_phone($phoneRaw);
    if ($phoneNorm === null) {
        $err = ($err ?? '') ?: 'Enter a valid Ghana mobile number for OTP delivery (e.g. 024xxxxxxx).';
    }

    if ($err === null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO admins (username, password_hash, role, created_at, created_by_admin_id, phone) VALUES (?,?,?,?,?,?)')
            ->execute([$username, $hash, $allowedRole, date('c'), $actorId, $phoneNorm]);
        $newId = (int)$pdo->lastInsertId();

        $loginUrl = 'https://kuukuacares.com/login.php';
        $msg = 'Kuukua Cares staff account. OTP: %otp_code%. Username: ' . $username . ' Password: ' . $password . ' Login: ' . $loginUrl . ' — Sponsored by Mavis Kuukua Bissue. Change password after login.';
        $otpResult = arkesel_otp_generate($pdo, $phoneNorm, $msg);

        log_admin_action($pdo, 'staff_account_created', 'admin', $newId, [
            'username' => $username,
            'role' => $allowedRole,
            'phone' => $phoneNorm,
            'otp_ok' => $otpResult['ok'],
            'otp_error' => $otpResult['error'] ?? null,
            'password_sent_via_otp_sms' => true,
            'otp_delivery' => $otpResult['delivery'] ?? 'otp_api',
        ]);

        if ($otpResult['ok']) {
            $del = $otpResult['delivery'] ?? 'otp_api';
            flash(
                'admin_notice',
                $del === 'sms_fallback'
                    ? 'Account created. Credentials sent by SMS (managed OTP API failed; standard SMS API used — same balance as your other apps).'
                    : 'Account created and OTP SMS dispatched via Arkesel.'
            );
        } else {
            flash('admin_notice', 'Account created, but OTP SMS failed: ' . ($otpResult['error'] ?? 'unknown') . '. Share credentials securely out of band.');
        }
        redirect('manage_staff.php');
    }
    flash('admin_notice', $err);
    redirect('manage_staff.php');
}

if (is_super_admin()) {
    $staff = $pdo->query('SELECT id, username, role, phone, created_at, created_by_admin_id FROM admins ORDER BY role ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $st = $pdo->prepare('SELECT id, username, role, phone, created_at, created_by_admin_id FROM admins WHERE role = ? ORDER BY id ASC');
    $st->execute([ROLE_FIELD_OFFICER]);
    $staff = $st->fetchAll(PDO::FETCH_ASSOC);
}

$pendingByTarget = [];
if (is_super_admin()) {
    foreach ($pdo->query('SELECT target_admin_id, requested_at FROM staff_delete_requests')->fetchAll(PDO::FETCH_ASSOC) as $pr) {
        $pendingByTarget[(int)$pr['target_admin_id']] = (string)$pr['requested_at'];
    }
}

$coordinatorPending = is_coordinator() ? staff_pending_removal_rows_for_coordinator($pdo, $actorId) : [];
?>
<?php render_layout_start('Staff Accounts', 'manage_staff'); ?>
<div class="max-w-6xl mx-auto">
  <h1 class="text-3xl font-black text-slate-900">Staff accounts</h1>
  <p class="text-slate-500 mt-1 text-sm"><?= is_super_admin()
    ? 'Super admin: create accounts, reset passwords. Coordinators: delete immediately. Field officers: default workflow is request removal then coordinator approval — or use Force delete (override) to remove immediately (fully audited).'
    : 'Coordinator: create field officers, reset passwords, and approve or decline super-admin requests to remove field officers.' ?></p>
  <?php if ($notice): ?><div class="mt-4 p-4  bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <?php if ($coordinatorPending): ?>
  <div class="mt-6 p-4 bg-amber-50 border border-amber-200 text-amber-950">
    <p class="font-bold text-sm">Pending field officer removals (super admin requested)</p>
    <ul class="mt-3 space-y-3">
      <?php foreach ($coordinatorPending as $p): ?>
      <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-amber-200/80 bg-white/80 p-3">
        <div class="text-sm min-w-0">
          <span class="font-semibold"><?=h((string)$p['target_username'])?></span>
          <span class="text-slate-500"> · ID <?= (int)$p['target_id'] ?></span>
          <span class="block text-xs text-slate-600 mt-1">Requested <?=h(date('d M Y H:i', strtotime((string)$p['requested_at'])))?></span>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
          <form method="post" onsubmit="return confirm('Permanently delete this field officer account?');">
            <input type="hidden" name="staff_action" value="approve_staff_removal">
            <input type="hidden" name="request_id" value="<?=(int)$p['request_id']?>">
            <button type="submit" class="text-xs font-semibold px-3 py-2 bg-emerald-700 text-white hover:bg-emerald-800">Approve removal</button>
          </form>
          <form method="post" onsubmit="return confirm('Decline this removal request? The account stays active.');">
            <input type="hidden" name="staff_action" value="reject_staff_removal">
            <input type="hidden" name="request_id" value="<?=(int)$p['request_id']?>">
            <button type="submit" class="text-xs font-semibold px-3 py-2 border border-slate-300 bg-white text-slate-800">Decline</button>
          </form>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <div class="mt-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div>
      <h2 class="font-bold text-lg text-slate-900">Directory</h2>
      <p class="text-xs text-slate-500 mt-1">Each staff user has a numeric ID. Open <strong>Create staff account</strong> to add someone; they receive username and a generated password by Arkesel OTP SMS only.</p>
    </div>
    <button type="button" id="btn-open-create-staff" class="shrink-0 px-5 py-3 bg-slate-950 text-white font-bold hover:bg-slate-800 text-sm w-full sm:w-auto">
      Create staff account
    </button>
  </div>

  <style>#create-staff-modal::backdrop{background:rgba(15,23,42,.45)}</style>
  <dialog id="create-staff-modal" class="max-w-lg w-[calc(100%-2rem)] rounded-lg border border-slate-200 shadow-2xl p-0">
    <form method="post" class="flex flex-col max-h-[90vh]">
      <div class="px-5 py-4 border-b border-slate-200 flex items-start justify-between gap-3 bg-slate-50">
        <div>
          <h3 class="font-bold text-slate-900">Create staff account</h3>
          <p class="text-xs text-slate-500 mt-1">A secure password is generated for you and sent to the phone below via OTP SMS only.</p>
        </div>
        <button type="button" id="btn-close-create-staff" class="text-slate-500 hover:text-slate-800 p-1" aria-label="Close">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>
      <div class="px-5 py-4 space-y-4 overflow-y-auto">
        <input type="hidden" name="staff_action" value="create">
        <?php if (is_super_admin()): ?>
        <label class="block text-sm font-semibold text-slate-700">Role</label>
        <select name="role" class="w-full border border-slate-200 p-3">
          <option value="<?=h(ROLE_FIELD_OFFICER)?>">Field officer</option>
          <option value="<?=h(ROLE_COORDINATOR)?>">Coordinator</option>
        </select>
        <?php else: ?>
        <input type="hidden" name="role" value="<?=h(ROLE_FIELD_OFFICER)?>">
        <p class="text-sm text-slate-600">New account role: <strong>field officer</strong>.</p>
        <?php endif; ?>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Username</span>
          <input name="username" required minlength="3" class="mt-1 w-full border border-slate-200 p-3" autocomplete="off">
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Phone (OTP)</span>
          <input name="phone" type="tel" required placeholder="0241234567" class="mt-1 w-full border border-slate-200 p-3" autocomplete="off">
        </label>
      </div>
      <div class="px-5 py-4 border-t border-slate-200 flex flex-wrap gap-2 justify-end bg-white">
        <button type="button" id="btn-cancel-create-staff" class="px-4 py-2 border border-slate-300 bg-white text-slate-800 text-sm font-semibold hover:bg-slate-50">Cancel</button>
        <button type="submit" class="px-5 py-2 bg-slate-950 text-white font-bold hover:bg-slate-800 text-sm">Create &amp; send OTP</button>
      </div>
    </form>
  </dialog>

  <div class="mt-6 bg-white border border-slate-200 p-5 sm:p-6 min-w-0">
      <h3 class="sr-only">Staff directory table</h3>

      <div class="mt-4 xl:hidden space-y-3">
        <?php foreach ($staff as $s): ?>
          <?php
            $canDelCoord = is_super_admin()
                && staff_target_deletable_by_actor($s)
                && (string)$s['role'] === ROLE_COORDINATOR;
            $canRequestFoRemoval = is_super_admin()
                && staff_target_deletable_by_actor($s)
                && (string)$s['role'] === ROLE_FIELD_OFFICER;
            $canForceDelFo = is_super_admin()
                && staff_target_deletable_by_actor($s)
                && (string)$s['role'] === ROLE_FIELD_OFFICER;
            $pendingAt = $pendingByTarget[(int)$s['id']] ?? null;
            $canReset = staff_target_password_resettable_by_actor($s);
          ?>
          <article class="border border-slate-200 bg-slate-50/50 p-4">
            <div class="flex justify-between gap-2 items-start">
              <div class="min-w-0">
                <p class="font-semibold text-slate-900 truncate"><span class="text-slate-500 font-mono text-xs mr-1">#<?= (int)$s['id'] ?></span><?=h((string)$s['username'])?><?php if ((int)$s['id'] === $actorId): ?> <span class="text-xs text-slate-400">(you)</span><?php endif; ?></p>
                <p class="text-xs text-slate-600 mt-0.5 uppercase tracking-wide"><?=h((string)$s['role'])?></p>
              </div>
            </div>
            <?php if ($pendingAt !== null): ?>
            <p class="mt-2 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-1.5">Removal pending coordinator approval (requested <?=h(date('d M Y', strtotime($pendingAt)))?>)</p>
            <?php endif; ?>
            <dl class="mt-3 space-y-1.5 text-xs">
              <div class="flex justify-between gap-3"><dt class="text-slate-400 shrink-0">Phone</dt><dd class="font-mono text-slate-800 text-right break-all"><?=h((string)($s['phone'] ?? '—'))?></dd></div>
              <div class="flex justify-between gap-3"><dt class="text-slate-400 shrink-0">Created</dt><dd class="text-slate-700 text-right"><?=h(date('d M Y', strtotime((string)$s['created_at'])))?></dd></div>
            </dl>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
              <?php if ($canReset): ?>
              <form method="post" class="w-full sm:w-auto">
                <input type="hidden" name="staff_action" value="reset_password">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="w-full sm:w-auto text-xs font-semibold text-indigo-800 px-3 py-2 border border-indigo-200 bg-white text-left sm:text-center">Reset password</button>
              </form>
              <?php endif; ?>
              <?php if ($canDelCoord): ?>
              <form method="post" class="w-full sm:w-auto" onsubmit="return confirm('Permanently delete this staff account?');">
                <input type="hidden" name="staff_action" value="delete">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="w-full sm:w-auto text-xs font-semibold text-red-800 px-3 py-2 border border-red-200 bg-white text-left sm:text-center">Delete account</button>
              </form>
              <?php endif; ?>
              <?php if ($canRequestFoRemoval && $pendingAt === null): ?>
              <form method="post" class="w-full sm:w-auto" onsubmit="return confirm('Request removal? A coordinator must approve before this account is deleted.');">
                <input type="hidden" name="staff_action" value="request_staff_removal">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="w-full sm:w-auto text-xs font-semibold text-red-800 px-3 py-2 border border-red-200 bg-white text-left sm:text-center">Request removal</button>
              </form>
              <?php endif; ?>
              <?php if ($canRequestFoRemoval && $pendingAt !== null): ?>
              <form method="post" class="w-full sm:w-auto" onsubmit="return confirm('Cancel the pending removal request?');">
                <input type="hidden" name="staff_action" value="cancel_staff_removal">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="w-full sm:w-auto text-xs font-semibold text-slate-800 px-3 py-2 border border-slate-300 bg-white text-left sm:text-center">Cancel request</button>
              </form>
              <?php endif; ?>
              <?php if ($canForceDelFo): ?>
              <form method="post" class="w-full sm:w-auto" onsubmit="return confirm('SUPER ADMIN OVERRIDE: Delete this field officer immediately?\n\nThis bypasses coordinator approval. Audit logs will record you (super admin) and, if applicable, the coordinator who created this account.');">
                <input type="hidden" name="staff_action" value="super_admin_force_delete">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="w-full sm:w-auto text-xs font-semibold text-white px-3 py-2 bg-red-950 border border-red-900 text-left sm:text-center">Force delete (override)</button>
              </form>
              <?php endif; ?>
              <?php if (!$canReset && !$canDelCoord && !($canRequestFoRemoval && $pendingAt === null) && !($canRequestFoRemoval && $pendingAt !== null) && !$canForceDelFo): ?>
              <span class="text-xs text-slate-400">—</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
        <?php if (!$staff): ?>
          <p class="text-sm text-slate-500 py-4">No accounts.</p>
        <?php endif; ?>
      </div>

      <div class="hidden xl:block mt-4 w-full">
        <table class="w-full text-sm table-fixed border-collapse">
          <thead><tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-200">
            <th class="py-2.5 pr-2 w-[10%]">ID</th><th class="py-2.5 pr-2 w-[18%]">User</th><th class="py-2.5 pr-2 w-[14%]">Role</th><th class="py-2.5 pr-2 w-[16%]">Phone</th><th class="py-2.5 pr-2 w-[12%]">Created</th><th class="py-2.5 text-right w-[30%]">Actions</th>
          </tr></thead>
          <tbody>
            <?php foreach ($staff as $s): ?>
              <?php
                  $canDelCoord = is_super_admin()
                      && staff_target_deletable_by_actor($s)
                      && (string)$s['role'] === ROLE_COORDINATOR;
                  $canRequestFoRemoval = is_super_admin()
                      && staff_target_deletable_by_actor($s)
                      && (string)$s['role'] === ROLE_FIELD_OFFICER;
                  $canForceDelFo = is_super_admin()
                      && staff_target_deletable_by_actor($s)
                      && (string)$s['role'] === ROLE_FIELD_OFFICER;
                  $pendingAt = $pendingByTarget[(int)$s['id']] ?? null;
                  $canReset = staff_target_password_resettable_by_actor($s);
              ?>
              <tr class="border-b border-slate-100 align-top">
                <td class="py-2.5 pr-2 font-mono text-xs text-slate-600"><?= (int)$s['id'] ?></td>
                <td class="py-2.5 pr-2 font-semibold break-words"><?=h((string)$s['username'])?><?php if ((int)$s['id'] === $actorId): ?> <span class="text-xs text-slate-400">(you)</span><?php endif; ?></td>
                <td class="py-2.5 pr-2 break-words"><?=h((string)$s['role'])?><?php if ($pendingAt !== null): ?><span class="block text-[10px] text-amber-800 font-semibold mt-0.5">Pending approval</span><?php endif; ?></td>
                <td class="py-2.5 pr-2 font-mono text-xs break-all"><?=h((string)($s['phone'] ?? '—'))?></td>
                <td class="py-2.5 pr-2 text-xs text-slate-600 whitespace-nowrap"><?=h(date('d M Y', strtotime((string)$s['created_at'])))?></td>
                <td class="py-2.5 text-right">
                  <div class="flex flex-col gap-2 items-stretch xl:items-end">
                  <?php if ($canReset): ?>
                  <form method="post">
                    <input type="hidden" name="staff_action" value="reset_password">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="w-full xl:w-auto text-xs font-semibold text-indigo-800 px-2 py-1.5 border border-indigo-200 bg-white">Reset password</button>
                  </form>
                  <?php endif; ?>
                  <?php if ($canDelCoord): ?>
                  <form method="post" onsubmit="return confirm('Permanently delete this staff account?');">
                    <input type="hidden" name="staff_action" value="delete">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="w-full xl:w-auto text-xs font-semibold text-red-800 px-2 py-1.5 border border-red-200 bg-white">Delete account</button>
                  </form>
                  <?php endif; ?>
                  <?php if ($canRequestFoRemoval && $pendingAt === null): ?>
                  <form method="post" onsubmit="return confirm('Request removal? A coordinator must approve before this account is deleted.');">
                    <input type="hidden" name="staff_action" value="request_staff_removal">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="w-full xl:w-auto text-xs font-semibold text-red-800 px-2 py-1.5 border border-red-200 bg-white">Request removal</button>
                  </form>
                  <?php endif; ?>
                  <?php if ($canRequestFoRemoval && $pendingAt !== null): ?>
                  <form method="post" onsubmit="return confirm('Cancel the pending removal request?');">
                    <input type="hidden" name="staff_action" value="cancel_staff_removal">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="w-full xl:w-auto text-xs font-semibold text-slate-800 px-2 py-1.5 border border-slate-300 bg-white">Cancel request</button>
                  </form>
                  <?php endif; ?>
                  <?php if ($canForceDelFo): ?>
                  <form method="post" onsubmit="return confirm('SUPER ADMIN OVERRIDE: Delete this field officer immediately?\n\nThis bypasses coordinator approval. Audit logs will record you (super admin) and, if applicable, the coordinator who created this account.');">
                    <input type="hidden" name="staff_action" value="super_admin_force_delete">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="w-full xl:w-auto text-xs font-semibold text-white px-2 py-1.5 bg-red-950 border border-red-900">Force delete (override)</button>
                  </form>
                  <?php endif; ?>
                  <?php if (!$canReset && !$canDelCoord && !($canRequestFoRemoval && $pendingAt === null) && !($canRequestFoRemoval && $pendingAt !== null) && !$canForceDelFo): ?>
                  <span class="text-xs text-slate-400">—</span>
                  <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$staff): ?>
              <tr><td colspan="6" class="py-6 text-slate-500">No accounts.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
  </div>
</div>
<script>
(function () {
  var dlg = document.getElementById('create-staff-modal');
  if (!dlg || !dlg.showModal) return;
  function openM() { dlg.showModal(); }
  function closeM() { dlg.close(); }
  document.getElementById('btn-open-create-staff')?.addEventListener('click', openM);
  document.getElementById('btn-close-create-staff')?.addEventListener('click', closeM);
  document.getElementById('btn-cancel-create-staff')?.addEventListener('click', closeM);
  dlg.addEventListener('click', function (e) {
    if (e.target === dlg) closeM();
  });
})();
</script>
<?php render_layout_end(); ?>
