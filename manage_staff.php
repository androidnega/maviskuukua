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
        try {
            $pdo->prepare('DELETE FROM chat_messages WHERE admin_id = ?')->execute([$targetId]);
        } catch (Throwable $e) {
            // table may be absent in some deployments
        }
        $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$targetId]);
        log_admin_action($pdo, 'staff_account_deleted', 'admin', $targetId, [
            'deleted_username' => $target['username'],
            'deleted_role' => $target['role'],
        ]);
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
        ]);

        if ($otpResult['ok']) {
            flash('admin_notice', 'New password saved and OTP SMS sent with login details.');
        } else {
            flash('admin_notice', 'Password was reset but OTP SMS failed: ' . ($otpResult['error'] ?? 'unknown') . '. Set a new password again or share securely.');
        }
        redirect('manage_staff.php');
    }

    // Create account (default)
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $phoneRaw = trim((string)($_POST['phone'] ?? ''));
    $roleIn = trim((string)($_POST['role'] ?? ''));

    $allowedRole = ROLE_FIELD_OFFICER;
    if (is_super_admin()) {
        if ($roleIn === ROLE_COORDINATOR || $roleIn === ROLE_FIELD_OFFICER) {
            $allowedRole = $roleIn;
        }
    }

    $err = null;
    if ($username === '' || strlen($username) < 3) {
        $err = 'Username must be at least 3 characters.';
    } elseif ($password === '' || strlen($password) < 8) {
        $err = 'Password must be at least 8 characters.';
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
        ]);

        if ($otpResult['ok']) {
            flash('admin_notice', 'Account created and OTP SMS dispatched via Arkesel.');
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
?>
<?php render_layout_start('Staff Accounts', 'manage_staff'); ?>
<div class="max-w-6xl mx-auto">
  <h1 class="text-3xl font-black text-slate-900">Staff accounts</h1>
  <p class="text-slate-500 mt-1 text-sm"><?= is_super_admin()
    ? 'Super admin: create coordinators and field officers, reset passwords, delete coordinators and field officers.'
    : 'Coordinator: create field officers and reset their passwords via OTP (cannot delete accounts).' ?></p>
  <?php if ($notice): ?><div class="mt-4 p-4  bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-2">
    <div class="bg-white border border-slate-200 p-5 sm:p-6 min-w-0">
      <h2 class="font-bold text-lg text-slate-900">Create account</h2>
      <p class="text-xs text-slate-500 mt-1">Credentials are sent by Arkesel OTP SMS (message includes %otp_code% plus username/password).</p>
      <form method="post" class="mt-4 space-y-4">
        <input type="hidden" name="staff_action" value="create">
        <?php if (is_super_admin()): ?>
        <label class="block text-sm font-semibold text-slate-700">Role</label>
        <select name="role" class="w-full  border border-slate-200 p-3">
          <option value="<?=h(ROLE_FIELD_OFFICER)?>">Field officer</option>
          <option value="<?=h(ROLE_COORDINATOR)?>">Coordinator</option>
        </select>
        <?php else: ?>
        <input type="hidden" name="role" value="<?=h(ROLE_FIELD_OFFICER)?>">
        <p class="text-sm text-slate-600">New accounts are created as <strong>field officers</strong>.</p>
        <?php endif; ?>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Username</span>
          <input name="username" required class="mt-1 w-full  border border-slate-200 p-3" autocomplete="off">
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Temporary password</span>
          <input name="password" type="text" required class="mt-1 w-full  border border-slate-200 p-3 font-mono text-sm" autocomplete="off">
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Phone (OTP)</span>
          <input name="phone" required placeholder="0241234567" class="mt-1 w-full  border border-slate-200 p-3">
        </label>
        <button type="submit" class="px-6 py-3  bg-slate-950 text-white font-bold hover:bg-slate-800">Create &amp; send OTP</button>
      </form>
    </div>

    <div class="bg-white border border-slate-200 p-5 sm:p-6 min-w-0">
      <h2 class="font-bold text-lg text-slate-900">Directory</h2>

      <div class="mt-4 md:hidden space-y-3">
        <?php foreach ($staff as $s): ?>
          <?php
            $canDel = staff_target_deletable_by_actor($s);
            $canReset = staff_target_password_resettable_by_actor($s);
          ?>
          <article class="border border-slate-200 bg-slate-50/50 p-4">
            <div class="flex justify-between gap-2 items-start">
              <div class="min-w-0">
                <p class="font-semibold text-slate-900 truncate"><?=h((string)$s['username'])?><?php if ((int)$s['id'] === $actorId): ?> <span class="text-xs text-slate-400">(you)</span><?php endif; ?></p>
                <p class="text-xs text-slate-600 mt-0.5 uppercase tracking-wide"><?=h((string)$s['role'])?></p>
              </div>
            </div>
            <dl class="mt-3 space-y-1.5 text-xs">
              <div class="flex justify-between gap-3"><dt class="text-slate-400 shrink-0">Phone</dt><dd class="font-mono text-slate-800 text-right break-all"><?=h((string)($s['phone'] ?? '—'))?></dd></div>
              <div class="flex justify-between gap-3"><dt class="text-slate-400 shrink-0">Created</dt><dd class="text-slate-700 text-right"><?=h(date('d M Y', strtotime((string)$s['created_at'])))?></dd></div>
            </dl>
            <div class="mt-4 flex flex-wrap gap-3 justify-end">
              <?php if ($canReset): ?>
              <form method="post" class="inline">
                <input type="hidden" name="staff_action" value="reset_password">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="text-xs font-semibold text-indigo-700 px-2 py-1 border border-indigo-200 bg-white">Reset password</button>
              </form>
              <?php endif; ?>
              <?php if ($canDel): ?>
              <form method="post" class="inline" onsubmit="return confirm('Permanently delete this staff account?');">
                <input type="hidden" name="staff_action" value="delete">
                <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                <button type="submit" class="text-xs font-semibold text-red-700 px-2 py-1 border border-red-200 bg-white">Delete</button>
              </form>
              <?php endif; ?>
              <?php if (!$canReset && !$canDel): ?>
              <span class="text-xs text-slate-400">—</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
        <?php if (!$staff): ?>
          <p class="text-sm text-slate-500 py-4">No accounts.</p>
        <?php endif; ?>
      </div>

      <div class="hidden md:block mt-4 overflow-x-auto -mx-1 px-1">
        <table class="w-full text-sm min-w-[520px]">
          <thead><tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-200">
            <th class="py-2.5 pr-2">User</th><th class="py-2.5 pr-2">Role</th><th class="py-2.5 pr-2">Phone</th><th class="py-2.5 pr-2">Created</th><th class="py-2.5 text-right">Actions</th>
          </tr></thead>
          <tbody>
            <?php foreach ($staff as $s): ?>
              <?php
                  $canDel = staff_target_deletable_by_actor($s);
                  $canReset = staff_target_password_resettable_by_actor($s);
              ?>
              <tr class="border-b border-slate-100">
                <td class="py-2.5 pr-2 font-semibold"><?=h((string)$s['username'])?><?php if ((int)$s['id'] === $actorId): ?> <span class="text-xs text-slate-400">(you)</span><?php endif; ?></td>
                <td class="py-2.5 pr-2"><?=h((string)$s['role'])?></td>
                <td class="py-2.5 pr-2 font-mono text-xs"><?=h((string)($s['phone'] ?? '—'))?></td>
                <td class="py-2.5 pr-2 text-xs text-slate-600"><?=h(date('d M Y', strtotime((string)$s['created_at'])))?></td>
                <td class="py-2.5 text-right whitespace-nowrap">
                  <?php if ($canReset): ?>
                  <form method="post" class="inline">
                    <input type="hidden" name="staff_action" value="reset_password">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="text-xs font-semibold text-indigo-700 hover:underline">Reset password</button>
                  </form>
                  <?php endif; ?>
                  <?php if ($canDel): ?>
                  <?php if ($canReset): ?><span class="text-slate-300 mx-1">|</span><?php endif; ?>
                  <form method="post" class="inline" onsubmit="return confirm('Permanently delete this staff account?');">
                    <input type="hidden" name="staff_action" value="delete">
                    <input type="hidden" name="target_id" value="<?=(int)$s['id']?>">
                    <button type="submit" class="text-xs font-semibold text-red-700 hover:underline">Delete</button>
                  </form>
                  <?php endif; ?>
                  <?php if (!$canReset && !$canDel): ?>
                  <span class="text-xs text-slate-400">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$staff): ?>
              <tr><td colspan="5" class="py-6 text-slate-500">No accounts.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php render_layout_end(); ?>
