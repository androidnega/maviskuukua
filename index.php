<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
$heroSrc = public_site_hero_image_src();
render_public_layout_start('Mavis Kuukua Bissue | Official Website', 'home');
?>
<main class="public-main">
  <!-- Page 1: Welcome -->
  <section id="welcome" class="section-padding flex min-h-[calc(100vh-7rem)] flex-col justify-center scroll-mt-24">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-2 lg:px-8">
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
          <?php if ($heroSrc !== ''): ?>
            <div class="h-[min(68vh,520px)] w-full max-w-sm overflow-hidden rounded-[1.5rem] border border-line shadow-sm sm:max-w-md lg:h-[min(72vh,580px)] lg:max-w-none">
              <img src="<?= h($heroSrc) ?>" alt="Kuukua Cares — Mavis Kuukua Bissue" class="h-full w-full object-cover object-center" />
            </div>
          <?php else: ?>
            <div class="flex h-[420px] w-full max-w-md flex-col items-center justify-center gap-3 rounded-[1.5rem] bg-slate-100 text-slate-500 md:h-[560px]">
              <i class="fa-solid fa-image text-4xl text-slate-400"></i>
              <p class="max-w-xs px-4 text-center text-sm">Add <code class="rounded bg-white px-1.5 py-0.5 text-xs">assets/kuukuacares.jpg</code> for the hero image.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Page 2: Explore -->
  <section id="explore" class="section-padding flex min-h-[calc(100vh-7rem)] flex-col justify-center border-t border-line bg-slate-50/80 scroll-mt-24">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="reveal mx-auto max-w-2xl text-center">
        <p class="text-sm font-bold uppercase tracking-widest text-emerald-700">Explore</p>
        <h2 class="section-title mt-3 font-display text-3xl font-bold text-slate-950 md:text-4xl">Explore the site</h2>
        <p class="mt-4 text-lg text-slate-600">Vision, projects, news, and how to reach the office — each on its own page for clarity.</p>
      </div>
      <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
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
  <section id="membership" class="section-padding flex min-h-[calc(100vh-7rem)] flex-col justify-center border-t border-line scroll-mt-24">
    <div class="mx-auto max-w-3xl px-5 text-center lg:px-8">
      <div class="reveal">
        <p class="text-sm font-bold uppercase tracking-widest text-emerald-700">Get involved</p>
        <h2 class="section-title mt-3 font-display text-3xl font-bold text-slate-950 md:text-4xl">Membership &amp; contact</h2>
        <p class="mt-4 text-lg text-slate-600">
          Register securely for membership records, or get in touch with the office directly.
        </p>
        <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
          <a href="register.php" class="btn-primary inline-flex items-center justify-center gap-2 rounded-full px-8 py-4 text-base font-bold">
            Register for membership
            <i class="fa-solid fa-id-card"></i>
          </a>
          <a href="contact.php" class="btn-secondary inline-flex items-center justify-center gap-2 rounded-full px-8 py-4 text-base font-bold">
            Contact office
            <i class="fa-regular fa-envelope"></i>
          </a>
        </div>
        <p class="mt-8 text-sm text-slate-500">
          <a href="#welcome" class="font-semibold text-emerald-700 hover:underline">Back to top</a>
        </p>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
