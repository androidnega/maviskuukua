<?php
require_once 'config.php';

function render_layout_start(string $title, string $active = 'home'): void {
    $menu = [
        ['key' => 'home', 'label' => 'Home', 'href' => 'index.php', 'icon' => 'fa-house'],
        ['key' => 'register', 'label' => 'Register', 'href' => 'register.php', 'icon' => 'fa-id-card'],
    ];

    if (is_admin()) {
        $menu[] = ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'admin.php', 'icon' => 'fa-chart-line'];
        $menu[] = ['key' => 'logout', 'label' => 'Logout', 'href' => 'logout.php', 'icon' => 'fa-right-from-bracket'];
    } else {
        $menu[] = ['key' => 'login', 'label' => 'Admin Login', 'href' => 'login.php', 'icon' => 'fa-user-shield'];
    }

    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=h($title)?></title>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-900">
  <div class="min-h-screen md:flex">
    <aside class="w-full md:w-72 bg-slate-950 text-slate-100 p-6 md:sticky md:top-0 md:h-screen">
      <div class="flex items-center gap-3 mb-8">
        <div class="h-11 w-11 rounded-2xl bg-emerald-400 text-slate-950 flex items-center justify-center">
          <i class="fa-solid fa-users"></i>
        </div>
        <div>
          <p class="font-black text-lg leading-tight">Mavis System</p>
          <p class="text-xs text-slate-400">Membership Portal</p>
        </div>
      </div>
      <nav class="space-y-2">
        <?php foreach ($menu as $item): ?>
          <?php $isActive = $item['key'] === $active; ?>
          <a href="<?=h($item['href'])?>" class="flex items-center gap-3 rounded-xl px-4 py-3 transition <?= $isActive ? 'bg-emerald-400 text-slate-950 font-bold' : 'hover:bg-white/10 text-slate-200' ?>">
            <i class="fa-solid <?=h($item['icon'])?> w-5"></i>
            <span><?=h($item['label'])?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>
    <main class="flex-1 p-4 md:p-8">
<?php
}

function render_layout_end(): void {
    ?>
    </main>
  </div>
</body>
</html>
<?php
}
