<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_admin();
require_site_content_management();
require_once __DIR__ . '/site_content_lib.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        if ($slideId > 0 && hero_slide_delete($pdo, $slideId)) {
            log_admin_action($pdo, 'hero_slide_delete', 'hero_slide', $slideId, []);
            flash('admin_notice', 'Slide removed.');
        } else {
            flash('admin_notice', 'Slide not found.');
        }
        redirect('admin_slideshow.php');
    }

    if ($action === 'reorder') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        $dir = (string) ($_POST['direction'] ?? '');
        if ($slideId > 0 && ($dir === 'up' || $dir === 'down')) {
            hero_slide_reorder($pdo, $slideId, $dir);
            flash('admin_notice', 'Slide order updated.');
        }
        redirect('admin_slideshow.php');
    }

    if ($action === 'save_alt') {
        $slideId = (int) ($_POST['slide_id'] ?? 0);
        $alt = trim((string) ($_POST['alt_text'] ?? ''));
        if ($slideId > 0) {
            $now = date('c');
            $pdo->prepare('UPDATE hero_slides SET alt_text = ?, updated_at = ? WHERE id = ?')
                ->execute([$alt, $now, $slideId]);
            flash('admin_notice', 'Alt text saved.');
        }
        redirect('admin_slideshow.php');
    }

    if ($action === 'upload') {
        $alt = trim((string) ($_POST['alt_text'] ?? ''));
        if ($alt === '') {
            $alt = 'Mavis Kuukua Bissue — Ahanta West';
        }
        if (empty($_FILES['slide_image']['tmp_name']) || !is_uploaded_file((string) $_FILES['slide_image']['tmp_name'])) {
            flash('admin_notice', 'Choose an image file to upload.');
            redirect('admin_slideshow.php');
        }
        $path = site_content_save_uploaded_image($_FILES['slide_image'], 'assets/slideshow', 'slide');
        if ($path === null) {
            flash('admin_notice', 'Upload failed. Use JPG, PNG, WebP, or GIF under 12MB.');
            redirect('admin_slideshow.php');
        }
        $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM hero_slides')->fetchColumn();
        $now = date('c');
        $pdo->prepare(
            'INSERT INTO hero_slides (image_path, alt_text, sort_order, published, created_at, updated_at) VALUES (?,?,?,1,?,?)'
        )->execute([$path, $alt, $maxSort + 1, $now, $now]);
        $newId = (int) $pdo->lastInsertId();
        log_admin_action($pdo, 'hero_slide_create', 'hero_slide', $newId, ['path' => $path]);
        flash('admin_notice', 'Slide added to homepage slideshow.');
        redirect('admin_slideshow.php');
    }
}

$slides = hero_slides_list_all($pdo);
$notice = flash('admin_notice');

render_layout_start('Home Slideshow', 'slideshow_admin');
?>
<div class="w-full max-w-5xl">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900">Homepage hero slideshow</h1>
      <p class="text-sm text-slate-500 mt-1">Images shown in the hero on the home page. Recommended size: 1200×1000 px (6:5).</p>
    </div>
    <a href="index.php" target="_blank" rel="noopener" class="text-sm font-semibold text-emerald-700 hover:underline">Preview home page</a>
  </div>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <section class="mt-8 bg-white border border-slate-200 p-6">
    <h2 class="text-lg font-bold text-slate-900">Upload new slide</h2>
    <form method="post" enctype="multipart/form-data" class="mt-4 space-y-4 max-w-xl">
      <input type="hidden" name="action" value="upload">
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">Image</span>
        <input type="file" name="slide_image" accept="image/jpeg,image/png,image/webp,image/gif" required class="mt-1 block w-full text-sm">
      </label>
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">Alt text (accessibility)</span>
        <input name="alt_text" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" placeholder="Describe what is shown in the image">
      </label>
      <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm">Add slide</button>
    </form>
  </section>

  <section class="mt-10">
    <h2 class="text-lg font-bold text-slate-900">Current slides</h2>
    <?php if (count($slides) === 0): ?>
      <p class="mt-4 text-sm text-slate-500">No slides yet. Upload an image above.</p>
    <?php else: ?>
      <ul class="mt-6 space-y-6">
        <?php foreach ($slides as $i => $slide): ?>
          <?php
          $sid = (int) ($slide['id'] ?? 0);
          $path = (string) ($slide['image_path'] ?? '');
          $isFirst = $i === 0;
          $isLast = $i === count($slides) - 1;
          ?>
          <li class="bg-white border border-slate-200 p-4 flex flex-col sm:flex-row gap-4">
            <div class="shrink-0 w-full sm:w-48">
              <?php if ($path !== '' && site_content_file_exists($path)): ?>
                <img src="<?= h($path) ?>" alt="" class="w-full aspect-[6/5] object-cover border border-slate-200">
              <?php else: ?>
                <div class="w-full aspect-[6/5] bg-slate-100 border border-slate-200 flex items-center justify-center text-xs text-slate-500">Missing file</div>
              <?php endif; ?>
              <p class="mt-1 text-xs text-slate-400 font-mono truncate" title="<?= h($path) ?>"><?= h($path) ?></p>
            </div>
            <div class="flex-1 min-w-0 space-y-3">
              <form method="post" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="action" value="save_alt">
                <input type="hidden" name="slide_id" value="<?= $sid ?>">
                <label class="flex-1 min-w-[12rem]">
                  <span class="text-xs font-semibold text-slate-600">Alt text</span>
                  <input name="alt_text" class="mt-1 w-full border border-slate-300 px-2 py-1.5 text-sm" value="<?= h((string) ($slide['alt_text'] ?? '')) ?>">
                </label>
                <button type="submit" class="px-3 py-1.5 text-sm font-semibold text-slate-700 border border-slate-300 hover:bg-slate-50">Save</button>
              </form>
              <div class="flex flex-wrap items-center gap-2">
                <?php if (!$isFirst): ?>
                  <form method="post" class="inline">
                    <input type="hidden" name="action" value="reorder">
                    <input type="hidden" name="slide_id" value="<?= $sid ?>">
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="px-3 py-1.5 text-sm font-semibold border border-slate-300 hover:bg-slate-50" title="Move earlier"><i class="fa-solid fa-arrow-up"></i></button>
                  </form>
                <?php endif; ?>
                <?php if (!$isLast): ?>
                  <form method="post" class="inline">
                    <input type="hidden" name="action" value="reorder">
                    <input type="hidden" name="slide_id" value="<?= $sid ?>">
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="px-3 py-1.5 text-sm font-semibold border border-slate-300 hover:bg-slate-50" title="Move later"><i class="fa-solid fa-arrow-down"></i></button>
                  </form>
                <?php endif; ?>
                <span class="text-xs text-slate-400 ml-1">Order <?= (int) ($slide['sort_order'] ?? 0) ?></span>
                <form method="post" class="inline ml-auto" onsubmit="return confirm('Delete this slide and its image file?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="slide_id" value="<?= $sid ?>">
                  <button type="submit" class="px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50 border border-red-200">Delete</button>
                </form>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
<?php render_layout_end(); ?>
