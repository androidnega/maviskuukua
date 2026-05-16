<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tracking.php';
require_once __DIR__ . '/projects_lib.php';

tracking_public_hit('/projects.php');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';

$items = projects_list();

render_public_layout_start('Projects | Mavis Kuukua Bissue', 'projects', 'Ahanta language, trust fund, heritage months, Sportyfest, and community initiatives.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="<?= h(public_page_container_class()) ?>">
      <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
        <div class="reveal max-w-2xl">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Projects</p>
          <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Ahanta initiatives and community programs.</h1>
        </div>
        <p class="reveal max-w-md text-sm leading-7 text-slate-600">Select a project to read full details, view photos, and watch videos from events and activities.</p>
      </div>

      <div class="mt-12 grid gap-5 md:grid-cols-3">
        <?php foreach ($items as $row): ?>
          <?php
          $slug = (string) ($row['slug'] ?? '');
          $feat = project_featured_src($row);
          $icon = (string) ($row['icon'] ?? 'fa-folder-open');
          $cardTitle = (string) ($row['short_title'] ?? $row['title'] ?? '');
          $ex = trim((string) ($row['excerpt'] ?? ''));
          ?>
          <article class="simple-card reveal flex flex-col overflow-hidden">
            <a href="project_detail.php?slug=<?= h(urlencode($slug)) ?>" class="block shrink-0 border-b border-line bg-slate-100 aspect-[16/10] overflow-hidden no-underline">
              <?php if ($feat !== ''): ?>
                <img src="<?= h($feat) ?>" alt="" class="h-full w-full object-cover transition duration-300 hover:scale-[1.02]">
              <?php else: ?>
                <div class="flex h-full w-full flex-col items-center justify-center gap-3 text-emerald-700/50">
                  <i class="fa-solid <?= h($icon) ?> text-5xl" aria-hidden="true"></i>
                </div>
              <?php endif; ?>
            </a>
            <div class="flex flex-1 flex-col p-6">
              <div class="flex items-start gap-3">
                <i class="fa-solid <?= h($icon) ?> mt-1 text-xl text-emerald-700 shrink-0" aria-hidden="true"></i>
                <div>
                  <h2 class="text-xl font-bold text-slate-950 leading-snug">
                    <a href="project_detail.php?slug=<?= h(urlencode($slug)) ?>" class="text-inherit no-underline hover:text-emerald-800"><?= h($cardTitle) ?></a>
                  </h2>
                  <?php if ($cardTitle !== (string) ($row['title'] ?? '')): ?>
                    <p class="mt-1 text-xs font-medium text-slate-500"><?= h((string) ($row['title'] ?? '')) ?></p>
                  <?php endif; ?>
                </div>
              </div>
              <p class="mt-3 text-sm leading-7 text-slate-600 flex-1"><?= h($ex) ?></p>
              <a href="project_detail.php?slug=<?= h(urlencode($slug)) ?>" class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:underline">
                View details
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
