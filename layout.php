<?php
require_once 'config.php';

/**
 * @param string $active Menu key: dashboard, branch_executive, received_list, bulk_export, team_chat, manage_staff, audit, settings
 */
function render_layout_start(string $title, string $active = 'home'): void {
    if (!is_admin()) {
        redirect('login.php');
    }

    $skin = admin_role();
    $skinClasses = [
        ROLE_SUPER_ADMIN => 'from-emerald-500/90 to-teal-600/90',
        ROLE_COORDINATOR => 'from-indigo-500/90 to-violet-600/90',
        ROLE_FIELD_OFFICER => 'from-slate-600/90 to-sky-700/90',
    ];
    $stripGradient = $skinClasses[$skin] ?? $skinClasses[ROLE_SUPER_ADMIN];

    $unreadCount = 0;
    try {
        $clause = members_active_clause();
        $unreadCount = (int)db()->query("SELECT COUNT(*) AS total FROM members WHERE viewed_at IS NULL AND $clause")->fetch()['total'];
    } catch (Throwable $e) {
        $unreadCount = 0;
    }

    $menu = [];
    $menu[] = ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'admin.php', 'icon' => 'fa-chart-line'];
    if (can_access_branch_executive_data()) {
        $menu[] = ['key' => 'branch_executive', 'label' => 'Branch Executive', 'href' => 'membership_database.php', 'icon' => 'fa-user-tie'];
    }
    $menu[] = ['key' => 'received_list', 'label' => 'List Received', 'href' => 'received_list.php', 'icon' => 'fa-table-list'];
    if (can_export_bulk_members()) {
        $menu[] = ['key' => 'bulk_export', 'label' => 'Bulk Export', 'href' => 'export_members_bulk.php', 'icon' => 'fa-file-export'];
    }
    if (can_access_team_chat()) {
        $menu[] = ['key' => 'team_chat', 'label' => 'Team Chat', 'href' => 'team_chat.php', 'icon' => 'fa-comments'];
    }
    if (can_manage_staff_accounts()) {
        $menu[] = ['key' => 'manage_staff', 'label' => 'Staff Accounts', 'href' => 'manage_staff.php', 'icon' => 'fa-user-shield'];
    }
    if (can_view_audit_and_logs()) {
        $menu[] = ['key' => 'audit', 'label' => 'Audit & Logs', 'href' => 'audit.php', 'icon' => 'fa-clipboard-list'];
    }
    if (is_super_admin()) {
        $menu[] = ['key' => 'settings', 'label' => 'SMS / API Settings', 'href' => 'settings.php', 'icon' => 'fa-gear'];
    }
    $menu[] = ['key' => 'logout', 'label' => 'Logout', 'href' => 'logout.php', 'icon' => 'fa-right-from-bracket'];

    $roleLabel = [
        ROLE_SUPER_ADMIN => 'Super Admin',
        ROLE_COORDINATOR => 'Coordinator',
        ROLE_FIELD_OFFICER => 'Field Officer',
    ];
    $who = $roleLabel[$skin] ?? 'Staff';

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
  <style>
    @keyframes mavis-surveillance-breathe {
      0%, 100% { opacity: 0.88; transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.35); }
      50% { opacity: 1; transform: scale(1.015); box-shadow: 0 0 22px 2px rgba(239, 68, 68, 0.45); }
    }
    .mavis-surveillance-breathe { animation: mavis-surveillance-breathe 2.6s ease-in-out infinite; }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 role-<?=h($skin)?>">
  <div class="bg-gradient-to-r <?=h($stripGradient)?> text-white px-4 py-2.5 flex flex-wrap items-center justify-between gap-2 text-sm font-semibold mavis-surveillance-breathe border-b border-white/10">
    <span class="inline-flex items-center gap-2">
      <i class="fa-solid fa-video"></i>
      This session is subject to <strong>24/7 surveillance</strong> monitoring for security and compliance.
    </span>
    <span class="text-xs opacity-90"><?=h($who)?> · <?=h($_SESSION['admin_username'] ?? '')?></span>
  </div>
  <div class="min-h-screen md:flex">
    <aside class="w-full md:w-72 bg-white border-b md:border-b-0 md:border-r p-6 md:sticky md:top-0 md:h-screen md:overflow-y-auto">
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
