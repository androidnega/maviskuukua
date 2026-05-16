<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_admin();
require_site_content_management();
require_once __DIR__ . '/site_content_lib.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageKey = (string) ($_POST['page_key'] ?? '');
    $action = (string) ($_POST['action'] ?? '');

    if ($pageKey !== '' && isset(public_pages_registry()[$pageKey])) {
        if ($action === 'toggle') {
            $row = public_page_row($pdo, $pageKey);
            $currently = $row !== null && !empty($row['under_update']);
            public_page_set_under_update($pdo, $pageKey, !$currently);
            log_admin_action($pdo, 'public_page_toggle', 'public_page', 0, [
                'page_key' => $pageKey,
                'under_update' => !$currently ? 1 : 0,
            ]);
            flash('admin_notice', (!$currently ? 'Page marked under update.' : 'Page is live again.'));
        } elseif ($action === 'save_notice') {
            $notice = trim((string) ($_POST['notice'] ?? ''));
            $under = !empty(public_page_row($pdo, $pageKey)['under_update']);
            public_page_set_under_update($pdo, $pageKey, $under, $notice !== '' ? $notice : 'This page is being updated. Please check back soon.');
            flash('admin_notice', 'Visitor message saved.');
        }
    }
    redirect('admin_public_pages.php');
}

$pages = public_pages_list_all($pdo);
$notice = flash('admin_notice');

render_layout_start('Public pages', 'public_pages_admin');
?>
<div class="w-full max-w-4xl">
  <h1 class="text-2xl font-black text-slate-900">Public page status</h1>
  <p class="text-sm text-slate-500 mt-1">Mark a page as under update to show visitors a maintenance message. Logged-in staff can still view the full page.</p>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <div class="mt-8 space-y-4">
    <?php foreach ($pages as $page): ?>
      <?php
      $key = (string) ($page['page_key'] ?? '');
      $under = !empty($page['under_update']);
      $href = (string) ($page['href'] ?? '');
      ?>
      <div class="bg-white border border-slate-200 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-bold text-slate-900"><?= h((string) ($page['label'] ?? $key)) ?></p>
            <p class="text-xs text-slate-500 font-mono mt-0.5"><?= h($key) ?> · <?= h($href) ?></p>
          </div>
          <div class="flex items-center gap-2">
            <?php if ($under): ?>
              <span class="inline-flex px-2 py-0.5 bg-amber-100 text-amber-900 text-xs font-bold">Under update</span>
            <?php else: ?>
              <span class="inline-flex px-2 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-bold">Live</span>
            <?php endif; ?>
            <a href="<?= h($href) ?>" target="_blank" rel="noopener" class="text-sm font-semibold text-emerald-700 hover:underline">View</a>
          </div>
        </div>

        <form method="post" class="mt-4 flex flex-wrap items-center gap-2">
          <input type="hidden" name="page_key" value="<?= h($key) ?>">
          <input type="hidden" name="action" value="toggle">
          <button type="submit" class="px-4 py-2 text-sm font-semibold border border-slate-300 hover:bg-slate-50">
            <?= $under ? 'Set live' : 'Mark under update' ?>
          </button>
        </form>

        <form method="post" class="mt-4 border-t border-slate-100 pt-4">
          <input type="hidden" name="page_key" value="<?= h($key) ?>">
          <input type="hidden" name="action" value="save_notice">
          <label class="block text-sm font-semibold text-slate-700">Message shown to visitors</label>
          <textarea name="notice" rows="2" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"><?= h((string) ($page['notice'] ?? '')) ?></textarea>
          <button type="submit" class="mt-2 px-3 py-1.5 text-sm font-semibold text-slate-700 border border-slate-300 hover:bg-slate-50">Save message</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php render_layout_end(); ?>
