<?php
require 'config.php';
if (is_admin()) redirect('admin.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = (string)$admin['username'];
        $role = trim((string)($admin['role'] ?? ''));
        if ($role !== ROLE_SUPER_ADMIN && $role !== ROLE_COORDINATOR && $role !== ROLE_FIELD_OFFICER) {
            $role = ROLE_SUPER_ADMIN;
        }
        $_SESSION['admin_role'] = $role;
        redirect('admin.php');
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Login</title><link rel="icon" type="image/svg+xml" href="assets/favicon.svg"><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100"><main class="min-h-screen flex items-center justify-center px-4"><form method="post" class="bg-white w-full max-w-md  border border-slate-200 p-8"><h1 class="text-2xl font-black">Admin Login</h1><?php if($error):?><div class="mt-4 p-3  bg-red-50 text-red-700"><?=h($error)?></div><?php endif;?><label class="block mt-6">Username<input name="username" class="w-full mt-1  border p-3" required></label><label class="block mt-4">Password<input type="password" name="password" class="w-full mt-1  border p-3" required></label><button class="mt-6 w-full bg-slate-950 text-white  p-3 font-bold">Login</button><a href="index.php" class="block text-center mt-4 text-sm text-slate-500">Back home</a></form></main></body></html>
