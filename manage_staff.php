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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $loginUrl = 'https://kuukuacares.com/login.php';
        $msg = 'Kuukua Cares staff account. OTP: %otp_code%. Username: ' . $username . ' Password: ' . $password . ' Login: ' . $loginUrl . ' — Sponsored by Mavis Kuukua Bissue. Change password after login.';
        $otpResult = arkesel_otp_generate($pdo, $phoneNorm, $msg);

        log_admin_action($pdo, 'staff_account_created', 'admin', (int)$pdo->lastInsertId(), [
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
    $staff = $pdo->query('SELECT id, username, role, phone, created_at, created_by_admin_id FROM admins ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $st = $pdo->prepare('SELECT id, username, role, phone, created_at, created_by_admin_id FROM admins WHERE role = ? ORDER BY id ASC');
    $st->execute([ROLE_FIELD_OFFICER]);
    $staff = $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php render_layout_start('Staff Accounts', 'manage_staff'); ?>
<div class="max-w-5xl mx-auto">
  <h1 class="text-3xl font-black text-slate-900">Staff accounts</h1>
  <p class="text-slate-500 mt-1 text-sm"><?= is_super_admin() ? 'Super admin: manage all roles.' : 'Coordinator: create and view field officers only.' ?></p>
  <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <div class="mt-8 grid lg:grid-cols-2 gap-8">
    <div class="bg-white rounded-3xl border border-slate-200 p-6">
      <h2 class="font-bold text-lg text-slate-900">Create account</h2>
      <p class="text-xs text-slate-500 mt-1">Credentials are sent by Arkesel OTP SMS (message includes %otp_code% plus username/password).</p>
      <form method="post" class="mt-4 space-y-4">
        <?php if (is_super_admin()): ?>
        <label class="block text-sm font-semibold text-slate-700">Role</label>
        <select name="role" class="w-full rounded-xl border border-slate-200 p-3">
          <option value="<?=h(ROLE_FIELD_OFFICER)?>">Field officer</option>
          <option value="<?=h(ROLE_COORDINATOR)?>">Coordinator</option>
        </select>
        <?php else: ?>
        <input type="hidden" name="role" value="<?=h(ROLE_FIELD_OFFICER)?>">
        <p class="text-sm text-slate-600">New accounts are created as <strong>field officers</strong>.</p>
        <?php endif; ?>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Username</span>
          <input name="username" required class="mt-1 w-full rounded-xl border border-slate-200 p-3" autocomplete="off">
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Temporary password</span>
          <input name="password" type="text" required class="mt-1 w-full rounded-xl border border-slate-200 p-3 font-mono text-sm" autocomplete="off">
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Phone (OTP)</span>
          <input name="phone" required placeholder="0241234567" class="mt-1 w-full rounded-xl border border-slate-200 p-3">
        </label>
        <button type="submit" class="px-6 py-3 rounded-xl bg-slate-950 text-white font-bold hover:bg-slate-800">Create &amp; send OTP</button>
      </form>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 p-6 overflow-x-auto">
      <h2 class="font-bold text-lg text-slate-900">Directory</h2>
      <table class="w-full text-sm mt-4">
        <thead><tr class="text-left text-xs uppercase text-slate-500 border-b">
          <th class="py-2">User</th><th class="py-2">Role</th><th class="py-2">Phone</th><th class="py-2">Created</th>
        </tr></thead>
        <tbody>
          <?php foreach ($staff as $s): ?>
            <tr class="border-b border-slate-100">
              <td class="py-2 font-semibold"><?=h((string)$s['username'])?></td>
              <td class="py-2"><?=h((string)$s['role'])?></td>
              <td class="py-2 font-mono text-xs"><?=h((string)($s['phone'] ?? ''))?></td>
              <td class="py-2 text-xs text-slate-600"><?=h(date('d M Y', strtotime((string)$s['created_at'])))?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$staff): ?>
            <tr><td colspan="4" class="py-6 text-slate-500">No accounts.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php render_layout_end(); ?>
