<?php
require 'layout.php';
if (is_admin()) redirect('admin.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        redirect('admin.php');
    }
    $error = 'Invalid username or password.';
}
?>
<?php render_layout_start('Admin Login', 'login'); ?>
<div class="min-h-[75vh] flex items-center justify-center px-4">
  <form method="post" class="bg-white w-full max-w-md rounded-3xl border p-8 shadow-sm">
    <h1 class="text-2xl font-black"><i class="fa-solid fa-user-shield mr-2 text-emerald-600"></i>Admin Login</h1>
    <p class="text-slate-500 mt-1">Default: admin / admin123</p>
    <?php if ($error): ?><div class="mt-4 p-3 rounded-xl bg-red-50 text-red-700"><?=h($error)?></div><?php endif; ?>
    <label class="block mt-6">Username<input name="username" class="w-full mt-1 rounded-xl border p-3" required></label>
    <label class="block mt-4">Password<input type="password" name="password" class="w-full mt-1 rounded-xl border p-3" required></label>
    <button class="mt-6 w-full bg-slate-950 text-white rounded-xl p-3 font-bold">Login</button>
  </form>
</div>
<?php render_layout_end(); ?>
