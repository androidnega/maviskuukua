<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/projects.php');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
render_public_layout_start('Projects | Mavis Kuukua Bissue', 'projects', 'Community projects and development priorities — Ahanta West.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
        <div class="reveal max-w-2xl">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Projects</p>
          <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Community work and development priorities.</h1>
        </div>
        <p class="reveal max-w-md text-sm leading-7 text-slate-600">Project cards below are prepared as official examples and can be replaced with real office updates.</p>
      </div>

      <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <article class="simple-card reveal p-6">
          <i class="fa-solid fa-handshake-angle text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-5 text-xl font-bold text-slate-950">Community Outreach</h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">Engagement programs focused on listening, support, and practical community needs.</p>
        </article>
        <article class="simple-card reveal p-6">
          <i class="fa-solid fa-lightbulb text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-5 text-xl font-bold text-slate-950">Youth Empowerment</h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">Mentorship, skills training, and leadership opportunities for young people.</p>
        </article>
        <article class="simple-card reveal p-6">
          <i class="fa-solid fa-ribbon text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-5 text-xl font-bold text-slate-950">Women Support</h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">Programs that promote women’s welfare, enterprise, confidence, and inclusion.</p>
        </article>
        <article class="simple-card reveal p-6">
          <i class="fa-solid fa-book-open-reader text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-5 text-xl font-bold text-slate-950">Education Support</h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">Learning resources, school support, and initiatives that help students progress.</p>
        </article>
        <article class="simple-card reveal p-6">
          <i class="fa-solid fa-road text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-5 text-xl font-bold text-slate-950">Local Development</h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">Community improvement efforts built around local priorities and shared progress.</p>
        </article>
        <article class="simple-card reveal p-6">
          <i class="fa-solid fa-people-arrows text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-5 text-xl font-bold text-slate-950">Public Engagement</h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">Open communication between the office and the people it serves.</p>
        </article>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
