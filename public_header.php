<?php
declare(strict_types=1);

/**
 * Reusable public site header (marketing pages).
 * Requires config.php loaded first (for h()).
 */

if (!function_exists('h')) {
    require_once __DIR__ . '/config.php';
}

/** Favicon + touch icon (portrait mark). */
function site_favicon_links(): void {
    ?>
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png" />
    <?php
}

function site_logo_header_src(): string {
    return 'assets/logo-header.png';
}

/**
 * @return list<array{key:string,label:string,href:string}>
 */
function public_site_nav_items(): array {
    return [
        ['key' => 'home', 'label' => 'Home', 'href' => 'index.php'],
        ['key' => 'about', 'label' => 'About', 'href' => 'about.php'],
        ['key' => 'vision', 'label' => 'Vision', 'href' => 'vision.php'],
        ['key' => 'projects', 'label' => 'Projects', 'href' => 'projects.php'],
        ['key' => 'news', 'label' => 'News', 'href' => 'news.php'],
        ['key' => 'membership', 'label' => 'Membership', 'href' => 'register.php'],
        ['key' => 'contact', 'label' => 'Contact', 'href' => 'contact.php'],
    ];
}

/**
 * @param string $active One of: home, about, vision, projects, news, membership, contact
 */
function render_public_site_header(string $active): void {
    $items = public_site_nav_items();
    ?>
  <header class="site-header fixed left-0 right-0 top-0 z-[100] border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur-md">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-600/25 to-transparent" aria-hidden="true"></div>

    <div class="mx-auto flex min-h-[var(--nav-h)] max-w-7xl items-center gap-4 px-4 sm:px-6 lg:gap-6 lg:px-10">
      <a href="index.php" class="group flex min-w-0 shrink-0 items-center py-2 outline-none focus-visible:rounded-lg focus-visible:ring-2 focus-visible:ring-emerald-600/40 focus-visible:ring-offset-2" aria-label="Mavis Kuukua Bissue home">
        <img
          src="<?= h(site_logo_header_src()) ?>"
          alt="Mavis Kuukua Bissue — Member of Parliament, Ahanta West"
          class="h-9 w-auto max-w-[10.5rem] object-contain object-left transition group-hover:opacity-90 sm:h-11 sm:max-w-[13rem]"
          width="204"
          height="52"
          decoding="async"
        />
      </a>

      <nav class="hidden flex-1 items-center justify-center gap-0.5 lg:flex" aria-label="Primary">
        <?php foreach ($items as $item):
            $isActive = $item['key'] === $active;
            $base = '-mb-px inline-flex items-center border-b-2 border-transparent px-3 py-3.5 text-[13px] font-semibold tracking-tight text-slate-600 transition-colors outline-none focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-emerald-600/35 focus-visible:ring-offset-2';
            $state = $isActive
                ? ' border-emerald-600 text-emerald-900'
                : ' hover:border-slate-300 hover:text-slate-900';
            ?>
          <a class="<?= h($base . $state) ?>" href="<?= h($item['href']) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= h($item['label']) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
        <?php if (function_exists('is_admin') && is_admin()): ?>
          <a href="admin.php" class="hidden rounded-md border border-slate-200 bg-white px-3.5 py-2 text-[13px] font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 md:inline-flex">
            Dashboard
          </a>
        <?php endif; ?>
        <a href="contact.php" class="hidden rounded-md border border-slate-200 bg-white px-3.5 py-2 text-[13px] font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 xl:inline-flex">
          Contact
        </a>
        <a href="register.php" class="inline-flex items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-[13px] font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
          Register
        </a>
        <button id="mobileMenuBtn" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-800 shadow-sm transition hover:bg-slate-50 lg:hidden" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
          <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
        </button>
      </div>
    </div>

    <div id="mobileMenu" class="mobile-nav-panel hidden border-t border-slate-100 bg-white lg:hidden">
      <div class="mx-auto max-w-7xl space-y-0.5 px-4 py-4 sm:px-6">
        <?php foreach ($items as $item):
            $isActive = $item['key'] === $active;
            $mbase = 'flex items-center rounded-lg px-4 py-3.5 text-[0.95rem] font-semibold transition-colors';
            $mstate = $isActive
                ? ' bg-slate-900 text-white'
                : ' text-slate-800 hover:bg-slate-100';
            ?>
          <a class="<?= h($mbase . $mstate) ?>" href="<?= h($item['href']) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= h($item['label']) ?></a>
        <?php endforeach; ?>
        <div class="mt-4 grid gap-2 border-t border-slate-100 pt-4 sm:grid-cols-2">
          <?php if (function_exists('is_admin') && is_admin()): ?>
            <a href="admin.php" class="inline-flex items-center justify-center rounded-lg border border-slate-200 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50 sm:col-span-2">Dashboard</a>
          <?php endif; ?>
          <a href="contact.php" class="inline-flex items-center justify-center rounded-lg border border-slate-200 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">Contact</a>
          <a href="register.php" class="inline-flex items-center justify-center rounded-lg bg-emerald-800 py-3 text-center text-sm font-bold text-white hover:bg-emerald-900">Register</a>
        </div>
      </div>
    </div>
  </header>
    <?php
}
