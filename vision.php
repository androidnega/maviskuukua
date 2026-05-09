<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/vision.php');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
render_public_layout_start('Vision | Mavis Kuukua Bissue', 'vision', 'Vision for education, women, youth, healthcare, and local development.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="mx-auto max-w-3xl text-center reveal">
        <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Vision</p>
        <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">A clear vision for people, opportunity, and local progress.</h1>
        <p class="mt-5 text-base leading-8 text-slate-600">The vision is centered on stronger communities, better support systems, and opportunities that help people grow with dignity.</p>
      </div>

      <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <div class="simple-card reveal p-6 text-center">
          <i class="fa-solid fa-graduation-cap text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-4 font-bold text-slate-950">Education</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">Support for learning, mentorship, and access.</p>
        </div>
        <div class="simple-card reveal p-6 text-center">
          <i class="fa-solid fa-person-dress text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-4 font-bold text-slate-950">Women</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">Empowerment, dignity, and opportunity.</p>
        </div>
        <div class="simple-card reveal p-6 text-center">
          <i class="fa-solid fa-users text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-4 font-bold text-slate-950">Youth</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">Skills, leadership, and job readiness.</p>
        </div>
        <div class="simple-card reveal p-6 text-center">
          <i class="fa-solid fa-briefcase-medical text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-4 font-bold text-slate-950">Healthcare</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">Community wellness and public health support.</p>
        </div>
        <div class="simple-card reveal p-6 text-center">
          <i class="fa-solid fa-seedling text-2xl text-emerald-700" aria-hidden="true"></i>
          <h2 class="mt-4 font-bold text-slate-950">Development</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">Local growth and community improvement.</p>
        </div>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
