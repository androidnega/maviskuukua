<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/news.php');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
render_public_layout_start('News | Mavis Kuukua Bissue', 'news', 'Latest official updates and public notices.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="mx-auto max-w-3xl text-center reveal">
        <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">News</p>
        <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Latest official updates.</h1>
        <p class="mt-5 text-base leading-8 text-slate-600">Clean update cards for announcements, community work, and public notices.</p>
      </div>

      <div class="mt-12 grid gap-5 md:grid-cols-3">
        <article class="simple-card reveal overflow-hidden">
          <div class="border-b border-line px-6 py-4 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">Community</div>
          <div class="p-6">
            <p class="text-sm text-slate-500">Official Update</p>
            <h2 class="mt-3 text-xl font-bold text-slate-950">Community engagement visit completed</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">A public engagement session focused on listening to community priorities and strengthening local collaboration.</p>
          </div>
        </article>
        <article class="simple-card reveal overflow-hidden">
          <div class="border-b border-line px-6 py-4 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">Youth</div>
          <div class="p-6">
            <p class="text-sm text-slate-500">Official Update</p>
            <h2 class="mt-3 text-xl font-bold text-slate-950">Youth skills support initiative announced</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">A planned initiative aimed at improving access to mentorship, training, and youth development opportunities.</p>
          </div>
        </article>
        <article class="simple-card reveal overflow-hidden">
          <div class="border-b border-line px-6 py-4 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">Office</div>
          <div class="p-6">
            <p class="text-sm text-slate-500">Public Notice</p>
            <h2 class="mt-3 text-xl font-bold text-slate-950">Office contact channels updated</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">Members of the public can use the official contact details on the contact page for enquiries and correspondence.</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
