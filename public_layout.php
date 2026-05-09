<?php
declare(strict_types=1);
/**
 * Shared chrome for public marketing pages (index, about, vision, …).
 * Requires config.php (and typically tracking.php) loaded first.
 */

require_once __DIR__ . '/public_header.php';

function public_site_hero_image_src(): string {
    $candidates = [
        'assets/kuukuacares.jpg',
        'assets/kuukuabissuesidepicture.jpg',
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
    :root { --nav-h: 5rem; }

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
      padding-top: calc(var(--nav-h) + 1.75rem);
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
      filter: drop-shadow(0 6px 20px rgba(15, 23, 42, 0.08));
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
      transition: background-color 180ms ease, transform 180ms ease, box-shadow 180ms ease;
    }

    .float-menu.open #floatToggle {
      background-color: #475569;
      box-shadow: 0 2px 10px rgba(15, 23, 42, 0.1);
    }

    #floatToggle:active {
      transform: scale(0.96);
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

    .news-body {
      font-size: 1.0625rem;
      line-height: 1.75;
      color: #334155;
    }
    .news-body > * + * {
      margin-top: 1.25em;
    }
    .news-body h1, .news-body h2, .news-body h3, .news-body h4 {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: #0f172a;
      margin-top: 1.5em;
      margin-bottom: 0.5em;
      line-height: 1.25;
    }
    .news-body h1 { font-size: 2rem; }
    .news-body h2 { font-size: 1.65rem; }
    .news-body h3 { font-size: 1.35rem; }
    .news-body ul, .news-body ol {
      padding-left: 1.5rem;
    }
    .news-body li + li { margin-top: 0.35em; }
    .news-body a {
      color: #087F5B;
      font-weight: 600;
      text-decoration: underline;
      text-underline-offset: 3px;
    }
    .news-body img, .news-body video, .news-body audio {
      max-width: 100%;
      height: auto;
    }
    .news-body video { border-radius: 0.75rem; background: #0f172a; }
    .news-body blockquote {
      border-left: 4px solid #087F5B;
      padding-left: 1rem;
      margin: 1.25rem 0;
      color: #475569;
      font-style: italic;
    }
    .news-body table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }
    .news-body th, .news-body td {
      border: 1px solid #e5e7eb;
      padding: 0.5rem 0.75rem;
      text-align: left;
    }
  </style>
</head>
<body>
  <div id="floatMenuBackdrop" class="float-menu-backdrop fixed inset-0 z-[90] bg-neutral-950/20 backdrop-blur-[2px]" aria-hidden="true"></div>

  <?php render_public_site_header($active); ?>
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
      </div>
    </div>
  </footer>

  <div id="floatMenu" class="float-menu fixed bottom-5 right-4 z-[110] flex flex-col items-end gap-2.5 sm:bottom-6 sm:right-6">
    <div class="float-menu-panel flex flex-col items-end gap-1.5">
      <?php foreach (public_site_nav_items() as $item): ?>
      <a href="<?= h($item['href']) ?>" tabindex="-1" class="float-menu-item float-menu-focusable rounded-xl border border-slate-200/90 bg-white/95 px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm outline-none ring-slate-400/40 focus-visible:ring-2 <?= $item['key'] === 'membership' ? ' border-emerald-200 bg-emerald-50/90 text-emerald-900 font-semibold' : '' ?>"><?= h($item['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <button id="floatToggle" class="grid h-11 w-11 place-items-center rounded-full bg-slate-700 text-white shadow-md ring-1 ring-slate-900/10 sm:h-12 sm:w-12" type="button" aria-label="Quick links" aria-expanded="false" aria-controls="floatMenu">
      <i id="plusIcon" class="fa-solid fa-plus text-base opacity-95" aria-hidden="true"></i>
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
          mobileMenuBtn.innerHTML = '<i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>';
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
          mobileMenuBtn.innerHTML = isOpen
            ? '<i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>';
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
