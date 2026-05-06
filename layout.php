<?php
require_once 'config.php';

function render_layout_start(string $title, string $active = 'home'): void {
    if (!is_admin()) {
        redirect('login.php');
    }
    $unreadCount = 0;
    try {
        $unreadCount = (int)db()->query("SELECT COUNT(*) AS total FROM members WHERE viewed_at IS NULL")->fetch()['total'];
    } catch (Throwable $e) {
        $unreadCount = 0;
    }
    $menu = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'admin.php', 'icon' => 'fa-chart-line'],
        ['key' => 'branch_executive', 'label' => 'Branch Executive', 'href' => 'membership_database.php', 'icon' => 'fa-user-tie'],
        ['key' => 'received_list', 'label' => 'List Received', 'href' => 'received_list.php', 'icon' => 'fa-table-list'],
        ['key' => 'logout', 'label' => 'Logout', 'href' => 'logout.php', 'icon' => 'fa-right-from-bracket'],
    ];

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
    <aside class="w-full md:w-72 bg-white border-r p-6 md:sticky md:top-0 md:h-screen">
      <div class="flex items-center gap-3 mb-8">
        <div class="h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <i class="fa-solid fa-users"></i>
        </div>
        <div>
          <p class="font-black text-lg leading-tight text-slate-900">Mavis System</p>
          <p class="text-xs text-slate-500">Membership Portal</p>
        </div>
      </div>
      <nav class="space-y-2">
        <?php foreach ($menu as $item): ?>
          <?php $isActive = $item['key'] === $active; ?>
          <a href="<?=h($item['href'])?>" class="flex items-center justify-between rounded-xl px-4 py-3 transition <?= $isActive ? 'bg-emerald-500 text-white font-bold' : 'hover:bg-slate-100 text-slate-700' ?>">
            <span class="flex items-center gap-3">
            <i class="fa-solid <?=h($item['icon'])?> w-5"></i>
            <span><?=h($item['label'])?></span>
            </span>
            <?php if ($item['key'] === 'received_list' && $unreadCount > 0): ?>
              <span class="min-w-6 h-6 px-2 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-bold"><?=$unreadCount?></span>
            <?php endif; ?>
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
