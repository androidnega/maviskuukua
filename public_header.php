<?php
declare(strict_types=1);

/**
 * Reusable public site header (marketing pages).
 * Requires config.php loaded first (for h()).
 */

if (!function_exists('h')) {
    require_once __DIR__ . '/config.php';
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
      <a href="index.php" class="group flex min-w-0 shrink-0 items-center gap-3.5 py-2 outline-none focus-visible:rounded-lg focus-visible:ring-2 focus-visible:ring-emerald-600/40 focus-visible:ring-offset-2" aria-label="Mavis Kuukua Bissue home">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-slate-900 text-white shadow-md ring-1 ring-slate-900/10 transition group-hover:bg-slate-800">
          <i class="fa-solid fa-leaf text-[0.95rem]" aria-hidden="true"></i>
        </span>
        <span class="min-w-0 leading-tight">
          <span class="font-display block text-[0.95rem] font-bold tracking-tight text-slate-900 sm:text-base">Mavis Kuukua Bissue</span>
          <span class="mt-0.5 block text-[11px] font-medium leading-none text-slate-500 sm:text-xs">Member of Parliament · Ahanta West</span>
        </span>
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
          <a href="contact.php" class="inline-flex items-center justify-center rounded-lg border border-slate-200 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">Contact</a>
          <a href="register.php" class="inline-flex items-center justify-center rounded-lg bg-emerald-800 py-3 text-center text-sm font-bold text-white hover:bg-emerald-900">Register</a>
        </div>
      </div>
    </div>
  </header>
    <?php
}
