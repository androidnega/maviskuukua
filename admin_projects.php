<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_admin();
require_site_content_management();
require_once __DIR__ . '/site_content_lib.php';

$pdo = db();
$rows = projects_list_all_admin($pdo);
$notice = flash('admin_notice');

render_layout_start('Projects', 'projects_admin');
?>
<div class="w-full max-w-6xl">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900">Public projects</h1>
      <p class="text-sm text-slate-500 mt-1">Edit initiative pages, gallery photos, and videos shown on the website.</p>
    </div>
    <a href="admin_projects_edit.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
      <i class="fa-solid fa-plus"></i> New project
    </a>
  </div>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <div class="mt-8 bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200 text-left">
        <tr>
          <th class="px-4 py-3 font-bold text-slate-700">Project</th>
          <th class="px-4 py-3 font-bold text-slate-700">Slug</th>
          <th class="px-4 py-3 font-bold text-slate-700">Status</th>
          <th class="px-4 py-3 font-bold text-slate-700">Order</th>
          <th class="px-4 py-3 font-bold text-slate-700 w-36"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($rows) === 0): ?>
          <tr>
            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No projects yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr class="border-b border-slate-100 hover:bg-slate-50/80">
              <td class="px-4 py-3 font-semibold text-slate-900">
                <?= h((string) ($r['short_title'] ?? $r['title'] ?? '')) ?>
                <?php if (!empty($r['published'])): ?>
                  <a href="project_detail.php?slug=<?= h(urlencode((string) ($r['slug'] ?? ''))) ?>" target="_blank" rel="noopener" class="ml-2 text-emerald-600 font-normal text-xs">View</a>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600 font-mono text-xs"><?= h((string) ($r['slug'] ?? '')) ?></td>
              <td class="px-4 py-3">
                <?php if (!empty($r['published'])): ?>
                  <span class="inline-flex px-2 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-bold">Live</span>
                <?php else: ?>
                  <span class="inline-flex px-2 py-0.5 bg-amber-100 text-amber-900 text-xs font-bold">Hidden</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600"><?= (int) ($r['sort_order'] ?? 0) ?></td>
              <td class="px-4 py-3">
                <a href="admin_projects_edit.php?id=<?= (int) ($r['id'] ?? 0) ?>" class="text-indigo-600 font-semibold hover:underline">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_layout_end(); ?>
