<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
$heroSlides = public_site_hero_slides();
$heroSrc = public_site_hero_image_src();
render_public_layout_start('Mavis Kuukua Bissue | Official Website', 'home');
?>
<main class="public-main">
  <!-- Page 1: Welcome -->
  <section id="welcome" class="scroll-mt-24 pt-6 pb-8 md:pt-8 md:pb-10 lg:pt-10 lg:pb-10">
    <div class="<?= h(public_page_container_class()) ?> grid items-start gap-8 lg:grid-cols-2 lg:gap-10">
      <div class="reveal">
        <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-line px-4 py-2 text-sm font-semibold text-emerald-700">
          <i class="fa-solid fa-circle-check"></i>
          Official public website
        </p>
        <h1 class="section-title font-display text-4xl font-bold leading-tight text-slate-950 md:text-6xl">
          Mavis Kuukua Bissue
        </h1>
        <p class="mt-5 max-w-2xl text-xl font-semibold leading-relaxed text-slate-800">
          Leadership rooted in service, community progress, and shared development.
        </p>
        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600 md:text-lg">
          Hon. Mavis Kuukua Bissue is the Member of Parliament for Ahanta West. This site shares vision, projects, and updates — and links to secure membership registration for administrative records.
        </p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
          <a href="#explore" class="btn-primary inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
            Next
            <i class="fa-solid fa-arrow-down"></i>
          </a>
          <a href="vision.php" class="btn-secondary inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
            Explore Vision
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>

      <div class="reveal">
        <div class="flex justify-center rounded-[2rem] border border-line bg-slate-50 p-3 lg:justify-end">
          <?php if (count($heroSlides) > 0): ?>
            <?php
            $slideCount = count($heroSlides);
            $gif1x1 = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
            ?>
            <div
              class="hero-slideshow group relative h-[min(58vh,440px)] w-full max-w-sm overflow-hidden rounded-[1.5rem] border border-line shadow-sm sm:max-w-md lg:h-[min(62vh,480px)] lg:max-w-none"
              data-hero-slideshow
              tabindex="0"
              role="region"
              aria-roledescription="carousel"
              aria-label="Official welcome imagery"
            >
              <div class="hero-slideshow-track relative h-full w-full" aria-live="polite">
                <?php foreach ($heroSlides as $i => $slide): ?>
                  <img
                    class="hero-slideshow-slide pointer-events-none absolute inset-0 h-full w-full object-cover object-center <?= $i === 0 ? 'is-active' : '' ?>"
                    <?= $i === 0 ? 'src="' . h($slide['src']) . '"' : 'src="' . h($gif1x1) . '" data-src="' . h($slide['src']) . '"' ?>
                    width="1200"
                    height="1000"
                    alt="<?= h($slide['alt']) ?>"
                    decoding="async"
                    <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                  />
                <?php endforeach; ?>
              </div>
              <div class="pointer-events-none absolute inset-x-0 bottom-0 z-20 h-24 bg-gradient-to-t from-slate-900/25 to-transparent opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100" aria-hidden="true"></div>
              <button type="button" class="hero-slideshow-prev hero-slideshow-arrow absolute left-2 top-1/2 z-30 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border-0 bg-white/95 text-sm text-emerald-900 shadow-md ring-1 ring-slate-900/5 hover:bg-white focus-visible:opacity-100 focus-visible:outline focus-visible:ring-2 focus-visible:ring-emerald-600/40" aria-label="Previous slide">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
              </button>
              <button type="button" class="hero-slideshow-next hero-slideshow-arrow absolute right-2 top-1/2 z-30 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border-0 bg-white/95 text-sm text-emerald-900 shadow-md ring-1 ring-slate-900/5 hover:bg-white focus-visible:opacity-100 focus-visible:outline focus-visible:ring-2 focus-visible:ring-emerald-600/40" aria-label="Next slide">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </button>
              <div class="absolute bottom-3 left-0 right-0 z-30 flex justify-center gap-1.5" role="tablist" aria-label="Slide selection">
                <?php for ($d = 0; $d < $slideCount; $d++): ?>
                  <button
                    type="button"
                    class="hero-slideshow-dot h-2 rounded-full border-0 p-0 shadow-sm ring-1 ring-slate-900/10 transition-all hover:opacity-100 focus-visible:outline focus-visible:ring-2 focus-visible:ring-emerald-600/50 <?= $d === 0 ? 'w-6 bg-emerald-800 opacity-100' : 'w-2 bg-white/70 opacity-90' ?>"
                    role="tab"
                    aria-selected="<?= $d === 0 ? 'true' : 'false' ?>"
                    aria-label="Show slide <?= $d + 1 ?> of <?= $slideCount ?>"
                    data-slide-to="<?= (int) $d ?>"
                  ></button>
                <?php endfor; ?>
              </div>
            </div>
            <style>
              .hero-slideshow-slide {
                opacity: 0;
                transform: scale(1.045);
                filter: brightness(0.96);
                transition:
                  opacity 0.72s cubic-bezier(0.33, 0.66, 0.28, 1),
                  transform 0.9s cubic-bezier(0.33, 0.66, 0.28, 1),
                  filter 0.65s cubic-bezier(0.33, 0.66, 0.28, 1);
                z-index: 0;
              }
              .hero-slideshow-slide.is-active {
                opacity: 1;
                transform: scale(1);
                filter: brightness(1);
                z-index: 1;
              }
              @media (prefers-reduced-motion: reduce) {
                .hero-slideshow-slide {
                  transition: none;
                  transform: none;
                  filter: none;
                }
                .hero-slideshow-slide.is-active {
                  transform: none;
                  filter: none;
                }
              }
              .hero-slideshow-dot {
                transition:
                  width 0.45s cubic-bezier(0.33, 0.66, 0.28, 1),
                  background-color 0.4s ease,
                  opacity 0.35s ease,
                  box-shadow 0.35s ease;
              }
              .hero-slideshow .hero-slideshow-arrow {
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.22s ease;
              }
              .hero-slideshow:hover .hero-slideshow-arrow,
              .hero-slideshow:focus-within .hero-slideshow-arrow {
                opacity: 1;
                pointer-events: auto;
              }
              @media (hover: none) {
                .hero-slideshow .hero-slideshow-arrow {
                  opacity: 0.88;
                  pointer-events: auto;
                }
              }
              @media (prefers-reduced-motion: reduce) {
                .hero-slideshow .hero-slideshow-arrow {
                  transition: none;
                }
              }
            </style>
            <script>
            (function () {
              var root = document.querySelector('[data-hero-slideshow]');
              if (!root) return;
              var slides = root.querySelectorAll('.hero-slideshow-slide');
              var dots = root.querySelectorAll('.hero-slideshow-dot');
              var prev = root.querySelector('.hero-slideshow-prev');
              var next = root.querySelector('.hero-slideshow-next');
              var n = slides.length;
              if (n < 2) return;

              var index = 0;
              var timer = null;
              var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
              var intervalMs = 6200;
              var pauseHover = false;

              function loadSlide(i) {
                var img = slides[i];
                if (!img) return;
                var ds = img.getAttribute('data-src');
                if (ds && img.getAttribute('src') !== ds) {
                  img.setAttribute('src', ds);
                  img.removeAttribute('data-src');
                }
              }

              function show(i) {
                index = (i + n) % n;
                slides.forEach(function (s, j) {
                  s.classList.toggle('is-active', j === index);
                });
                dots.forEach(function (d, j) {
                  var on = j === index;
                  d.setAttribute('aria-selected', on ? 'true' : 'false');
                  d.classList.toggle('w-6', on);
                  d.classList.toggle('w-2', !on);
                  d.classList.toggle('bg-emerald-800', on);
                  d.classList.toggle('bg-white/70', !on);
                  d.classList.toggle('opacity-100', on);
                  d.classList.toggle('opacity-90', !on);
                });
                loadSlide(index);
                loadSlide((index + 1) % n);
              }

              function nextSlide() { show(index + 1); }
              function prevSlide() { show(index - 1); }

              function clearTimer() {
                if (timer) {
                  window.clearInterval(timer);
                  timer = null;
                }
              }

              function schedule() {
                clearTimer();
                if (reduceMotion || pauseHover) return;
                timer = window.setInterval(nextSlide, intervalMs);
              }

              if (prev) prev.addEventListener('click', function (e) { e.preventDefault(); prevSlide(); schedule(); });
              if (next) next.addEventListener('click', function (e) { e.preventDefault(); nextSlide(); schedule(); });
              dots.forEach(function (d) {
                d.addEventListener('click', function () {
                  var to = parseInt(d.getAttribute('data-slide-to') || '0', 10);
                  show(to);
                  schedule();
                });
              });

              root.addEventListener('mouseenter', function () { pauseHover = true; clearTimer(); });
              root.addEventListener('mouseleave', function () { pauseHover = false; schedule(); });

              root.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') { e.preventDefault(); prevSlide(); schedule(); }
                if (e.key === 'ArrowRight') { e.preventDefault(); nextSlide(); schedule(); }
              });

              var tx = 0;
              root.addEventListener('touchstart', function (e) {
                if (!e.touches.length) return;
                tx = e.touches[0].clientX;
              }, { passive: true });
              root.addEventListener('touchend', function (e) {
                if (!e.changedTouches.length) return;
                var dx = e.changedTouches[0].clientX - tx;
                if (Math.abs(dx) < 48) return;
                if (dx > 0) prevSlide();
                else nextSlide();
                schedule();
              }, { passive: true });

              if (!reduceMotion) schedule();
            })();
            </script>
          <?php elseif ($heroSrc !== ''): ?>
            <div class="h-[min(58vh,440px)] w-full max-w-sm overflow-hidden rounded-[1.5rem] border border-line shadow-sm sm:max-w-md lg:h-[min(62vh,480px)] lg:max-w-none">
              <img src="<?= h($heroSrc) ?>" alt="Kuukua Cares — Mavis Kuukua Bissue" class="h-full w-full object-cover object-center" />
            </div>
          <?php else: ?>
            <div class="flex h-[420px] w-full max-w-md flex-col items-center justify-center gap-3 rounded-[1.5rem] bg-slate-100 text-slate-500 md:h-[560px]">
              <i class="fa-solid fa-image text-4xl text-slate-400"></i>
              <p class="max-w-xs px-4 text-center text-sm">Add slides under <code class="rounded bg-white px-1.5 py-0.5 text-xs">assets/slideshow/</code> or <code class="rounded bg-white px-1.5 py-0.5 text-xs">assets/kuukuacares.jpg</code>.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Page 2: Explore -->
  <section id="explore" class="scroll-mt-24 border-t border-line bg-slate-50/80 pt-6 pb-10 md:pt-8 md:pb-12 lg:pt-8 lg:pb-14">
    <div class="<?= h(public_page_container_class()) ?>">
      <div class="reveal mx-auto max-w-2xl text-center">
        <p class="text-sm font-bold uppercase tracking-widest text-emerald-700">Explore</p>
        <h2 class="section-title mt-3 font-display text-3xl font-bold text-slate-950 md:text-4xl">Explore the site</h2>
        <p class="mt-4 text-lg text-slate-600">Vision, projects, news, and how to reach the office — each on its own page for clarity.</p>
      </div>
      <div class="mt-8 grid gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
        <a href="about.php" class="reveal simple-card block p-6 no-underline">
          <i class="fa-solid fa-user-group text-2xl text-emerald-700"></i>
          <h3 class="mt-4 text-lg font-bold text-slate-900">About Us</h3>
          <p class="mt-2 text-sm text-slate-600">Background and commitment to Ahanta West.</p>
        </a>
        <a href="vision.php" class="reveal simple-card block p-6 no-underline">
          <i class="fa-solid fa-bullseye text-2xl text-emerald-700"></i>
          <h3 class="mt-4 text-lg font-bold text-slate-900">Vision</h3>
          <p class="mt-2 text-sm text-slate-600">Priorities and direction for the constituency.</p>
        </a>
        <a href="projects.php" class="reveal simple-card block p-6 no-underline">
          <i class="fa-solid fa-hammer text-2xl text-emerald-700"></i>
          <h3 class="mt-4 text-lg font-bold text-slate-900">Projects</h3>
          <p class="mt-2 text-sm text-slate-600">Initiatives and community impact.</p>
        </a>
        <a href="news.php" class="reveal simple-card block p-6 no-underline">
          <i class="fa-solid fa-newspaper text-2xl text-emerald-700"></i>
          <h3 class="mt-4 text-lg font-bold text-slate-900">News</h3>
          <p class="mt-2 text-sm text-slate-600">Updates and announcements.</p>
        </a>
      </div>
    </div>
  </section>

  <!-- Page 3: Membership & contact -->
  <section id="membership" class="scroll-mt-24 border-t border-line py-10 pb-14 md:py-12 md:pb-16 lg:py-14 lg:pb-20">
    <div class="<?= h(public_page_container_class()) ?> text-center">
      <div class="reveal">
        <p class="text-sm font-bold uppercase tracking-widest text-emerald-700">Get involved</p>
        <h2 class="section-title mt-3 font-display text-3xl font-bold text-slate-950 md:text-4xl">Membership &amp; contact</h2>
        <p class="mt-4 text-lg text-slate-600">
          Register securely for membership records, or get in touch with the office directly.
        </p>
        <div class="mt-7 flex flex-col items-center justify-center gap-4 sm:mt-8 sm:flex-row">
          <a href="register.php" class="btn-primary inline-flex items-center justify-center gap-2 rounded-full px-8 py-4 text-base font-bold">
            Register for membership
            <i class="fa-solid fa-id-card"></i>
          </a>
          <a href="contact.php" class="btn-secondary inline-flex items-center justify-center gap-2 rounded-full px-8 py-4 text-base font-bold">
            Contact office
            <i class="fa-regular fa-envelope"></i>
          </a>
        </div>
        <p class="mt-6 text-sm text-slate-500 sm:mt-7">
          <a href="#welcome" class="font-semibold text-emerald-700 hover:underline">Back to top</a>
        </p>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
