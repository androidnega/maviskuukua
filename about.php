<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/about.php');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
render_public_layout_start('About Us | Mavis Kuukua Bissue', 'about', 'About Hon. Mavis Kuukua Bissue — service, integrity, unity, and development.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
        <div class="reveal">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">About Us</p>
          <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Community-focused leadership with a clear sense of duty.</h1>
          <p class="mt-5 text-base leading-8 text-slate-600">
            Mavis Kuukua Bissue is presented as a community-focused leader committed to service, integrity, unity, and practical development. This official website shares her public vision, community work, project updates, and contact information.
          </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-hand-holding-heart mb-5 text-2xl text-emerald-700"></i>
            <h2 class="text-lg font-bold text-slate-950">Service</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">Putting people first through consistent community engagement and responsive leadership.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-shield-halved mb-5 text-2xl text-emerald-700"></i>
            <h2 class="text-lg font-bold text-slate-950">Integrity</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">Promoting honest, accountable, and transparent public service.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-people-group mb-5 text-2xl text-emerald-700"></i>
            <h2 class="text-lg font-bold text-slate-950">Unity</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">Bringing people together around shared values and common progress.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-chart-line mb-5 text-2xl text-emerald-700"></i>
            <h2 class="text-lg font-bold text-slate-950">Development</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">Supporting practical projects that improve lives and strengthen communities.</p>
          </article>
        </div>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
