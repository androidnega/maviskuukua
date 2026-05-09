<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_admin();
require_news_management();
require_once __DIR__ . '/news_lib.php';

$pdo = db();
$rows = news_list_all($pdo);
$notice = flash('admin_notice');

render_layout_start('News & Posts', 'news_admin');
?>
<div class="w-full max-w-6xl">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900">News &amp; posts</h1>
      <p class="text-sm text-slate-500 mt-1">Super admins and coordinators can publish updates with images, video, and audio.</p>
    </div>
    <a href="admin_news_edit.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
      <i class="fa-solid fa-plus"></i> New post
    </a>
  </div>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <div class="mt-8 bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200 text-left">
        <tr>
          <th class="px-4 py-3 font-bold text-slate-700">Title</th>
          <th class="px-4 py-3 font-bold text-slate-700">Category</th>
          <th class="px-4 py-3 font-bold text-slate-700">Status</th>
          <th class="px-4 py-3 font-bold text-slate-700">Updated</th>
          <th class="px-4 py-3 font-bold text-slate-700">Author</th>
          <th class="px-4 py-3 font-bold text-slate-700 w-40"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($rows) === 0): ?>
          <tr>
            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No posts yet. Create one to show on the public news page.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr class="border-b border-slate-100 hover:bg-slate-50/80">
              <td class="px-4 py-3 font-semibold text-slate-900">
                <?= h((string) ($r['title'] ?? '')) ?>
                <?php if (!empty($r['published'])): ?>
                  <a href="news_post.php?slug=<?= h(urlencode((string) ($r['slug'] ?? ''))) ?>" target="_blank" rel="noopener" class="ml-2 text-emerald-600 font-normal text-xs">View</a>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600"><?= h((string) ($r['category'] ?? '')) ?></td>
              <td class="px-4 py-3">
                <?php if (!empty($r['published'])): ?>
                  <span class="inline-flex px-2 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-bold">Live</span>
                <?php else: ?>
                  <span class="inline-flex px-2 py-0.5 bg-amber-100 text-amber-900 text-xs font-bold">Draft</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= h((string) ($r['updated_at'] ?? '')) ?></td>
              <td class="px-4 py-3 text-slate-600"><?= h((string) ($r['author_username'] ?? '')) ?></td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                  <a href="admin_news_edit.php?id=<?= (int) ($r['id'] ?? 0) ?>" class="text-indigo-600 font-semibold hover:underline">Edit</a>
                  <form method="post" action="news_delete.php" class="inline" onsubmit="return confirm('Delete this post permanently?');">
                    <input type="hidden" name="id" value="<?= (int) ($r['id'] ?? 0) ?>">
                    <button type="submit" class="text-red-600 font-semibold hover:underline bg-transparent border-0 cursor-pointer p-0">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_layout_end(); ?>
