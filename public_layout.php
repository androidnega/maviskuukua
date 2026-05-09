<?php
declare(strict_types=1);
/**
 * Shared chrome for public marketing pages (index, about, vision, …).
 * Requires config.php (and typically tracking.php) loaded first.
 */

/**
 * @return list<array{key:string,label:string,href:string}>
 */
function public_site_nav_items(): array {
    return [
        ['key' => 'home', 'label' => 'Home', 'href' => 'index.php'],
        ['key' => 'about', 'label' => 'About Us', 'href' => 'about.php'],
        ['key' => 'vision', 'label' => 'Vision', 'href' => 'vision.php'],
        ['key' => 'projects', 'label' => 'Projects', 'href' => 'projects.php'],
        ['key' => 'news', 'label' => 'News', 'href' => 'news.php'],
        ['key' => 'membership', 'label' => 'Membership', 'href' => 'register.php'],
        ['key' => 'contact', 'label' => 'Contact Us', 'href' => 'contact.php'],
    ];
}

function public_site_hero_image_src(): string {
    $candidates = [
        'assets/hero-campaign.png',
        'assets/hero-campaign.jpg',
        'assets/hero.jpg',
        'Screenshot 2026-05-06 at 3.33.08 AM.png',
    ];
    foreach ($candidates as $rel) {
        if (is_file(BASE_DIR . '/' . $rel)) {
            return $rel;
        }
    }

    return '';
}

/**
 * @param string $active One of: home, about, vision, projects, news, contact (not membership)
 */
function render_public_layout_start(string $title, string $active, ?string $metaDescription = null): void {
    $desc = $metaDescription ?? 'Official website of Mavis Kuukua Bissue: leadership, service, community development, and progress.';
    ?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="<?= h($desc) ?>" />
  <title><?= h($title) ?></title>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            display: ['Playfair Display', 'serif']
          },
          colors: {
            official: '#087F5B',
            officialDark: '#065F46',
            line: '#E5E7EB'
          }
        }
      }
    };
  </script>
  <style>
    :root { --nav-h: 76px; }

    html, body, main, section, header, footer {
      background: #ffffff !important;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: #334155;
      overflow-x: hidden;
    }

    body.float-menu-open {
      overflow: hidden;
    }

    .public-main {
      padding-top: 7rem;
    }

    .section-padding {
      padding-top: 5rem;
      padding-bottom: 5rem;
    }

    .section-title {
      letter-spacing: -0.035em;
    }

    .simple-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 1.25rem;
      transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
    }

    .simple-card:hover {
      transform: translateY(-4px);
      border-color: rgba(8, 127, 91, 0.35);
      box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    }

    .nav-link {
      color: #475569;
      font-weight: 600;
      font-size: 0.92rem;
      transition: color 180ms ease;
    }

    .nav-link:hover,
    .nav-link.active {
      color: #087F5B;
    }

    .btn-primary {
      background: #087F5B;
      color: #ffffff;
      border: 1px solid #087F5B;
      transition: background 180ms ease, transform 180ms ease;
    }

    .btn-primary:hover {
      background: #065F46;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: #ffffff;
      color: #065F46;
      border: 1px solid rgba(8, 127, 91, 0.35);
      transition: border-color 180ms ease, transform 180ms ease;
    }

    .btn-secondary:hover {
      border-color: #087F5B;
      transform: translateY(-2px);
    }

    .reveal {
      opacity: 0;
      transform: translateY(16px);
      transition: opacity 500ms ease, transform 500ms ease;
    }

    .reveal.show {
      opacity: 1;
      transform: translateY(0);
    }

    .float-menu-backdrop {
      opacity: 0;
      pointer-events: none;
      transition: opacity 200ms ease;
    }

    .float-menu-backdrop.is-open {
      opacity: 1;
      pointer-events: auto;
    }

    .float-menu-panel {
      position: relative;
      z-index: 51;
      filter: drop-shadow(0 12px 32px rgba(15, 23, 42, 0.12));
    }

    .float-menu-item {
      opacity: 0;
      transform: translateY(12px) scale(0.96);
      pointer-events: none;
      transition: opacity 180ms ease, transform 180ms ease;
    }

    .float-menu.open .float-menu-item {
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: auto;
    }

    .float-menu.open #plusIcon {
      transform: rotate(45deg);
    }

    #plusIcon {
      transition: transform 180ms ease;
    }

    #floatToggle {
      position: relative;
      z-index: 51;
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        scroll-behavior: auto !important;
        transition: none !important;
        animation: none !important;
      }
      .reveal {
        opacity: 1;
        transform: none;
      }
    }
  </style>
</head>
<body>
  <div id="floatMenuBackdrop" class="float-menu-backdrop fixed inset-0 z-[90] bg-slate-900/35 backdrop-blur-md" aria-hidden="true"></div>

  <header class="fixed left-0 right-0 top-0 z-[100] border-b border-line bg-white/95 backdrop-blur">
    <nav class="mx-auto flex h-[var(--nav-h)] max-w-7xl items-center justify-between px-5 lg:px-8" aria-label="Main navigation">
      <a href="index.php" class="flex items-center gap-3" aria-label="Mavis Kuukua Bissue home">
        <span class="grid h-10 w-10 place-items-center rounded-full border border-emerald-700 text-emerald-700">
          <i class="fa-solid fa-leaf"></i>
        </span>
        <span class="leading-tight">
          <span class="block text-sm font-bold text-slate-900">Mavis Kuukua Bissue</span>
          <span class="block text-xs font-medium text-slate-500">Official Website</span>
        </span>
      </a>

      <div class="hidden items-center gap-6 lg:flex">
        <?php foreach (public_site_nav_items() as $item):
            $isActive = $item['key'] === $active;
            $cls = 'nav-link' . ($isActive ? ' active' : '');
            ?>
          <a class="<?= h($cls) ?>" href="<?= h($item['href']) ?>"><?= h($item['label']) ?></a>
        <?php endforeach; ?>
      </div>

      <button id="mobileMenuBtn" class="grid h-10 w-10 place-items-center rounded-lg border border-line text-slate-700 lg:hidden" type="button" aria-label="Open mobile menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
      </button>
    </nav>

    <div id="mobileMenu" class="hidden border-t border-line bg-white px-5 py-4 lg:hidden">
      <div class="mx-auto grid max-w-7xl gap-2">
        <?php foreach (public_site_nav_items() as $item):
            $isActive = $item['key'] === $active;
            $cls = 'nav-link rounded-lg px-3 py-3' . ($isActive ? ' active' : '');
            ?>
          <a class="<?= h($cls) ?>" href="<?= h($item['href']) ?>"><?= h($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </header>
<?php
}

function render_public_layout_end(): void {
    ?>
  <footer class="border-t border-line py-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 text-sm text-slate-500 md:flex-row md:items-center md:justify-between lg:px-8">
      <p>&copy; <span id="year"></span> Mavis Kuukua Bissue. All rights reserved.</p>
      <div class="flex flex-wrap gap-4">
        <a href="index.php" class="hover:text-emerald-700">Home</a>
        <a href="about.php" class="hover:text-emerald-700">About</a>
        <a href="register.php" class="hover:text-emerald-700">Membership</a>
        <a href="contact.php" class="hover:text-emerald-700">Contact</a>
        <a href="login.php" class="hover:text-emerald-700">Staff login</a>
      </div>
    </div>
  </footer>

  <div id="floatMenu" class="float-menu fixed bottom-6 right-6 z-[110] flex flex-col items-end gap-3">
    <div class="float-menu-panel flex flex-col items-end gap-2">
      <a href="index.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none ring-emerald-600 focus-visible:ring-2">Home Page</a>
      <a href="about.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none ring-emerald-600 focus-visible:ring-2">About Us</a>
      <a href="vision.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none ring-emerald-600 focus-visible:ring-2">Vision</a>
      <a href="projects.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none ring-emerald-600 focus-visible:ring-2">Projects</a>
      <a href="news.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none ring-emerald-600 focus-visible:ring-2">News</a>
      <a href="contact.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none ring-emerald-600 focus-visible:ring-2">Contact Us</a>
      <a href="register.php" tabindex="-1" class="float-menu-item float-menu-focusable rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 outline-none ring-emerald-600 focus-visible:ring-2">Membership</a>
    </div>
    <button id="floatToggle" class="grid h-14 w-14 place-items-center rounded-full bg-emerald-700 text-white shadow-lg" type="button" aria-label="Open page shortcuts" aria-expanded="false" aria-controls="floatMenu">
      <i id="plusIcon" class="fa-solid fa-plus text-lg"></i>
    </button>
  </div>

  <script>
    (function () {
      document.getElementById('year').textContent = new Date().getFullYear();

      var mobileMenuBtn = document.getElementById('mobileMenuBtn');
      var mobileMenu = document.getElementById('mobileMenu');
      var floatMenu = document.getElementById('floatMenu');
      var floatToggle = document.getElementById('floatToggle');
      var backdrop = document.getElementById('floatMenuBackdrop');
      var focusables = floatMenu ? floatMenu.querySelectorAll('.float-menu-focusable') : [];

      function setFloatOpen(open) {
        if (!floatMenu || !backdrop || !floatToggle) return;
        if (open && mobileMenu && mobileMenuBtn && !mobileMenu.classList.contains('hidden')) {
          mobileMenu.classList.add('hidden');
          mobileMenuBtn.setAttribute('aria-expanded', 'false');
          mobileMenuBtn.innerHTML = '<i class="fa-solid fa-bars"></i>';
        }
        floatMenu.classList.toggle('open', open);
        backdrop.classList.toggle('is-open', open);
        backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
        floatToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('float-menu-open', open);
        focusables.forEach(function (el) {
          el.setAttribute('tabindex', open ? '0' : '-1');
        });
        if (open && focusables.length) {
          setTimeout(function () { focusables[0].focus(); }, 50);
        } else if (!open) {
          floatToggle.focus();
        }
      }

      if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function () {
          var isOpen = !mobileMenu.classList.contains('hidden');
          mobileMenu.classList.toggle('hidden');
          mobileMenuBtn.setAttribute('aria-expanded', String(!isOpen));
          mobileMenuBtn.innerHTML = isOpen ? '<i class="fa-solid fa-bars"></i>' : '<i class="fa-solid fa-xmark"></i>';
        });
      }

      if (floatToggle && floatMenu && backdrop) {
        floatToggle.addEventListener('click', function (e) {
          e.stopPropagation();
          setFloatOpen(!floatMenu.classList.contains('open'));
        });

        backdrop.addEventListener('click', function () {
          setFloatOpen(false);
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && floatMenu.classList.contains('open')) {
            setFloatOpen(false);
          }
        });
      }

      var revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('show');
            revealObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });

      document.querySelectorAll('.reveal').forEach(function (item) {
        revealObserver.observe(item);
      });
    })();
  </script>
</body>
</html>
<?php
}
