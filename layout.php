<?php
require_once 'config.php';
require_once __DIR__ . '/contact_lib.php';

/**
 * @param string $active Menu key: dashboard, branch_executive, received_list, contact_inbox, manage_staff, audit, settings
 */
function render_layout_start(string $title, string $active = 'home'): void {
    if (!is_admin()) {
        redirect('login.php');
    }

    $skin = admin_role();

    $unreadCount = 0;
    try {
        $clause = members_active_clause();
        $unreadCount = (int)db()->query("SELECT COUNT(*) AS total FROM members WHERE viewed_at IS NULL AND $clause")->fetch()['total'];
    } catch (Throwable $e) {
        $unreadCount = 0;
    }

    $staffRemovalPending = 0;
    try {
        if (can_manage_staff_accounts() && is_coordinator()) {
            $staffRemovalPending = staff_pending_removal_count_for_coordinator(db(), (int)($_SESSION['admin_id'] ?? 0));
        }
    } catch (Throwable $e) {
        $staffRemovalPending = 0;
    }

    $contactInboxUnread = contact_unread_count(db());

    $menu = [];
    $menu[] = ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'admin.php', 'icon' => 'fa-chart-line'];
    if (can_access_branch_executive_data()) {
        $menu[] = ['key' => 'branch_executive', 'label' => 'Branch Executive', 'href' => 'membership_database.php', 'icon' => 'fa-user-tie'];
    }
    $menu[] = ['key' => 'received_list', 'label' => 'Registrations', 'href' => 'received_list.php', 'icon' => 'fa-table-list'];
    $menu[] = ['key' => 'contact_inbox', 'label' => 'Contact messages', 'href' => 'admin_contact_messages.php', 'icon' => 'fa-envelope-open-text'];
    if (can_manage_news()) {
        $menu[] = ['key' => 'news_admin', 'label' => 'News & Posts', 'href' => 'admin_news.php', 'icon' => 'fa-newspaper'];
        $menu[] = ['key' => 'projects_admin', 'label' => 'Projects', 'href' => 'admin_projects.php', 'icon' => 'fa-hammer'];
        $menu[] = ['key' => 'slideshow_admin', 'label' => 'Home Slideshow', 'href' => 'admin_slideshow.php', 'icon' => 'fa-images'];
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
  <?php require_once __DIR__ . '/public_header.php'; site_favicon_links(); ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    @keyframes mavis-surveillance-icon-breathe {
      0%, 100% { opacity: 0.75; transform: scale(1); }
      50% { opacity: 1; transform: scale(1.12); filter: drop-shadow(0 0 6px rgba(220, 38, 38, 0.45)); }
    }
    .mavis-surveillance-icon-breathe {
      animation: mavis-surveillance-icon-breathe 2.4s ease-in-out infinite;
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 role-<?=h($skin)?> md:h-screen md:overflow-hidden">
  <div class="flex min-h-screen flex-col md:h-full md:min-h-0 md:overflow-hidden">
    <header class="sticky top-0 z-[60] flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 shadow-sm md:hidden">
      <button type="button" id="mavis-nav-open" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" aria-controls="mavis-sidebar" aria-expanded="false" aria-label="Open menu">
        <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
      </button>
      <div class="min-w-0 flex-1">
        <p class="truncate text-base font-black leading-tight text-slate-900">Mavis System</p>
        <p class="truncate text-[11px] text-slate-500"><?=h($who)?></p>
      </div>
      <div class="h-10 w-10 shrink-0" aria-hidden="true"></div>
    </header>

    <div id="mavis-nav-backdrop" class="fixed inset-0 z-[55] bg-slate-900/40 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden" aria-hidden="true"></div>

    <aside id="mavis-sidebar" class="fixed inset-y-0 left-0 z-[70] flex w-[min(100vw,18rem)] max-w-[85vw] -translate-x-full flex-col overflow-y-auto border-b border-slate-200 bg-white p-6 shadow-xl transition-transform duration-200 ease-out md:z-30 md:h-screen md:w-72 md:max-w-none md:translate-x-0 md:border-b-0 md:border-r md:border-slate-200 md:shadow-none">
      <div class="flex items-center gap-3 mb-8">
        <div class="h-11 w-11  bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <i class="fa-solid fa-users"></i>
        </div>
        <div>
          <p class="font-black text-lg leading-tight text-slate-900">Mavis System</p>
          <p class="text-xs text-slate-500">Membership Portal</p>
          <p class="text-[11px] text-slate-400 mt-1"><?=h($who)?> · <?=h($_SESSION['admin_username'] ?? '')?> · ID <?= (int)($_SESSION['admin_id'] ?? 0) ?></p>
        </div>
      </div>
      <nav class="space-y-2">
        <?php foreach ($menu as $item): ?>
          <?php $isActive = $item['key'] === $active; ?>
          <a href="<?=h($item['href'])?>" class="flex items-center justify-between  px-4 py-3 transition <?= $isActive ? 'bg-emerald-500 text-white font-bold' : 'hover:bg-slate-100 text-slate-700' ?>">
            <span class="flex items-center gap-3">
            <i class="fa-solid <?=h($item['icon'])?> w-5"></i>
            <span><?=h($item['label'])?></span>
            </span>
            <?php if ($item['key'] === 'received_list' && $unreadCount > 0): ?>
              <span class="min-w-6 h-6 px-2  bg-red-500 text-white text-xs flex items-center justify-center font-bold"><?=$unreadCount?></span>
            <?php endif; ?>
            <?php if ($item['key'] === 'contact_inbox' && $contactInboxUnread > 0): ?>
              <span class="min-w-6 h-6 px-2  bg-amber-500 text-white text-xs flex items-center justify-center font-bold"><?=$contactInboxUnread?></span>
            <?php endif; ?>
            <?php if ($item['key'] === 'manage_staff' && $staffRemovalPending > 0): ?>
              <span class="min-w-6 h-6 px-2  bg-amber-500 text-white text-xs flex items-center justify-center font-bold"><?=$staffRemovalPending?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>
    <main class="relative z-0 min-h-0 min-w-0 flex-1 overflow-x-hidden p-4 md:ml-72 md:h-full md:overflow-y-auto md:overscroll-y-contain md:p-8">
<?php
}

function render_layout_end(): void {
    ?>
    </main>
  </div>
  <script>
  (function () {
    var openBtn = document.getElementById('mavis-nav-open');
    var sidebar = document.getElementById('mavis-sidebar');
    var backdrop = document.getElementById('mavis-nav-backdrop');
    if (!sidebar || !backdrop) return;

    function isMobileNav() {
      return window.matchMedia('(max-width: 767px)').matches;
    }

    function setOpen(open) {
      if (open) {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        backdrop.classList.add('opacity-100');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
      } else {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
        backdrop.classList.remove('opacity-100');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
      }
    }

    function closeNav() {
      if (isMobileNav()) setOpen(false);
    }

    function toggleNav() {
      if (!isMobileNav()) return;
      var closed = sidebar.classList.contains('-translate-x-full');
      setOpen(closed);
    }

    openBtn && openBtn.addEventListener('click', function (e) {
      e.preventDefault();
      toggleNav();
    });

    backdrop.addEventListener('click', closeNav);

    sidebar.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (isMobileNav()) closeNav();
      });
    });

    window.addEventListener('resize', function () {
      if (!isMobileNav()) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNav();
    });
  })();
  </script>
</body>
</html>
<?php
}
