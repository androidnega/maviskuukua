<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tracking.php';
require_once __DIR__ . '/news_lib.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: news.php');

    exit;
}

$pdo = db();
$post = news_post_by_slug($pdo, $slug);
if (!$post || empty($post['published'])) {
    header('Location: news.php');

    exit;
}

tracking_public_hit('/news_post.php');

require_once __DIR__ . '/public_layout.php';

$title = (string) ($post['title'] ?? 'News');
$pageTitle = $title . ' | News';
$desc = trim((string) ($post['excerpt'] ?? ''));
if ($desc === '') {
    $plain = strip_tags((string) ($post['body_html'] ?? ''));
    $desc = mb_strlen($plain) > 180 ? mb_substr($plain, 0, 177) . '…' : $plain;
}

render_public_layout_start($pageTitle, 'news', $desc);
?>
<main class="public-main">
  <article class="section-padding border-t border-line">
    <div class="<?= h(public_page_container_class()) ?>">
      <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700 reveal"><?= h((string) ($post['category'] ?? 'General')) ?></p>
      <h1 class="section-title font-display mt-3 text-3xl font-bold leading-tight text-slate-950 md:text-5xl reveal"><?= h($title) ?></h1>
      <p class="mt-4 text-sm text-slate-500 reveal">
        <?= h((string) ($post['created_at'] ?? '')) ?>
        <?php if (!empty($post['author_username'])): ?>
          <span class="text-slate-400"> · </span> <?= h((string) $post['author_username']) ?>
        <?php endif; ?>
      </p>

      <?php
        $feat = (string) ($post['featured_image_path'] ?? '');
      if ($feat !== '' && is_file(BASE_DIR . '/' . str_replace('\\', '/', $feat))): ?>
        <div class="mt-10 reveal overflow-hidden rounded-2xl border border-line bg-slate-100 shadow-sm">
          <img src="<?= h($feat) ?>" alt="" class="w-full object-cover max-h-[min(70vh,520px)]">
        </div>
      <?php endif; ?>

      <div class="news-body mt-10 reveal">
        <?= $post['body_html'] ?? '' ?>
      </div>

      <p class="mt-14 reveal">
        <a href="news.php" class="btn-secondary inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
          <i class="fa-solid fa-arrow-left"></i> All news
        </a>
      </p>
    </div>
  </article>
</main>
<?php render_public_layout_end(); ?>
