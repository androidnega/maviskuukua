<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tracking.php';
require_once __DIR__ . '/news_lib.php';

tracking_public_hit('/news.php');
require_once __DIR__ . '/public_layout.php';

$pdo = db();
$items = news_list_published($pdo, 60);

render_public_layout_start('News | Mavis Kuukua Bissue', 'news', 'Latest official updates and public notices.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="mx-auto max-w-3xl text-center reveal">
        <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">News</p>
        <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Latest official updates.</h1>
        <p class="mt-5 text-base leading-8 text-slate-600">Announcements, community work, and public notices from the office.</p>
      </div>

      <?php if (count($items) === 0): ?>
        <div class="mx-auto mt-16 max-w-lg rounded-2xl border border-line bg-slate-50 px-8 py-12 text-center reveal">
          <i class="fa-solid fa-newspaper text-3xl text-emerald-600"></i>
          <p class="mt-4 text-lg font-semibold text-slate-900">No published stories yet</p>
          <p class="mt-2 text-sm text-slate-600">Check back soon. Staff can add posts from the admin <strong>News &amp; Posts</strong> area.</p>
        </div>
      <?php else: ?>
        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($items as $row): ?>
            <?php
            $slug = (string) ($row['slug'] ?? '');
            $feat = (string) ($row['featured_image_path'] ?? '');
            $ex = trim((string) ($row['excerpt'] ?? ''));
            if ($ex === '') {
                $plain = strip_tags((string) ($row['body_html'] ?? ''));
                $ex = mb_strlen($plain) > 160 ? mb_substr($plain, 0, 157) . '…' : $plain;
            }
            ?>
            <article class="simple-card reveal flex flex-col overflow-hidden">
              <a href="news_post.php?slug=<?= h(urlencode($slug)) ?>" class="block shrink-0 border-b border-line bg-slate-100 aspect-[16/10] overflow-hidden no-underline">
                <?php if ($feat !== '' && is_file(BASE_DIR . '/' . str_replace('\\', '/', $feat))): ?>
                  <img src="<?= h($feat) ?>" alt="" class="h-full w-full object-cover transition duration-300 hover:scale-[1.02]">
                <?php else: ?>
                  <div class="flex h-full w-full items-center justify-center text-emerald-700/40">
                    <i class="fa-regular fa-image text-5xl"></i>
                  </div>
                <?php endif; ?>
              </a>
              <div class="flex flex-1 flex-col p-6">
                <div class="text-xs font-bold uppercase tracking-[0.15em] text-emerald-700"><?= h((string) ($row['category'] ?? 'General')) ?></div>
                <h2 class="mt-3 text-xl font-bold text-slate-950 leading-snug">
                  <a href="news_post.php?slug=<?= h(urlencode($slug)) ?>" class="text-inherit no-underline hover:text-emerald-800"><?= h((string) ($row['title'] ?? '')) ?></a>
                </h2>
                <p class="mt-3 text-sm leading-7 text-slate-600 flex-1"><?= h($ex) ?></p>
                <p class="mt-4 text-xs text-slate-400"><?= h((string) ($row['created_at'] ?? '')) ?></p>
                <a href="news_post.php?slug=<?= h(urlencode($slug)) ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:underline">Read more <i class="fa-solid fa-arrow-right text-xs"></i></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
