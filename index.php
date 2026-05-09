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
  <section class="section-padding min-h-[calc(100vh-7rem)]">
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
          <a href="vision.php" class="btn-primary inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
            Explore Vision
            <i class="fa-solid fa-arrow-right"></i>
          </a>
          <a href="contact.php" class="btn-secondary inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
            Contact Office
            <i class="fa-regular fa-envelope"></i>
          </a>
          <a href="register.php" class="inline-flex items-center justify-center gap-2 rounded-full border border-emerald-700 bg-white px-6 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50">
            Register for membership
            <i class="fa-solid fa-id-card"></i>
          </a>
        </div>
      </div>

      <div class="reveal">
        <div class="rounded-[2rem] border border-line bg-white p-3">
          <?php if ($heroSrc !== ''): ?>
            <img src="<?= h($heroSrc) ?>" alt="Mavis Kuukua Bissue official portrait" class="h-[420px] w-full rounded-[1.5rem] object-cover object-center md:h-[560px]" />
          <?php else: ?>
            <div class="flex h-[420px] w-full flex-col items-center justify-center gap-3 rounded-[1.5rem] bg-slate-100 text-slate-500 md:h-[560px]">
              <i class="fa-solid fa-image text-4xl text-slate-400"></i>
              <p class="max-w-xs px-4 text-center text-sm">Add <code class="rounded bg-white px-1.5 py-0.5 text-xs">assets/hero-campaign.png</code> for the hero image.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
