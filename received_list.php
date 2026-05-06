<?php
require 'layout.php';
require_admin();
$pdo = db();
$notice = flash('admin_notice');
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$orderBy = 'created_at DESC';
if ($sort === 'oldest') {
    $orderBy = 'created_at ASC';
} elseif ($sort === 'membership_asc') {
    $orderBy = 'membership_id ASC';
} elseif ($sort === 'membership_desc') {
    $orderBy = 'membership_id DESC';
}
$active = members_active_clause();
$whereSql = ' WHERE ' . $active . ' ';
$params = [];
if ($search !== '') {
    $whereSql = ' WHERE ' . $active . ' AND (membership_id LIKE ? OR phone_no LIKE ?) ';
    $like = '%' . $search . '%';
    $params = [$like, $like];
}
$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM members $whereSql");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetch()['total'];
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}
$query = "SELECT * FROM members $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE viewed_at IS NULL AND $active")->fetch()['total'];

$totalAll = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active")->fetch()['total'];
$todayAll = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active AND date(created_at) = date('now')")->fetch()['total'];
$thisMonthAll = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['total'];

function page_url(int $targetPage, string $search, string $sort): string {
    $qs = http_build_query(['search' => $search, 'sort' => $sort, 'page' => $targetPage]);

    return 'received_list.php?' . $qs;
}
function is_new_member(array $member): bool {
    return trim((string)($member['viewed_at'] ?? '')) === '';
}

function photo_url_for_member(array $member): ?string {
    $path = trim((string)($member['photo_path'] ?? ''));
    if ($path !== '' && is_file(BASE_DIR . '/' . ltrim($path, '/'))) {
        return $path;
    }
    $fallback = 'storage/photos/photo_' . (int)$member['id'] . '.jpg';
    if (is_file(BASE_DIR . '/' . ltrim($fallback, '/'))) {
        return $fallback;
    }

    return null;
}
?>
<?php render_layout_start('Registrations', 'received_list'); ?>
<div class="max-w-7xl mx-auto space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Registrations</h1>
      <p class="text-slate-500 text-sm mt-0.5">Submitted membership applications.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (can_export_bulk_members()): ?>
      <div class="flex flex-wrap gap-1.5">
        <a href="export_members_bulk.php?format=csv" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">Bulk CSV</a>
        <a href="export_members_bulk.php?format=excel" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">Bulk Excel</a>
        <a href="export_members_bulk.php?format=pdf" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">Bulk PDF</a>
      </div>
      <?php endif; ?>
      <?php if ($unreadCount > 0): ?>
      <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-100"><?=$unreadCount?> new to review</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid sm:grid-cols-3 gap-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total registrations</p>
      <p class="text-2xl font-bold text-slate-800 mt-1"><?=$totalAll?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">New today</p>
      <p class="text-2xl font-bold text-slate-800 mt-1"><?=$todayAll?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">This month</p>
      <p class="text-2xl font-bold text-slate-800 mt-1"><?=$thisMonthAll?></p>
    </div>
  </div>

  <?php if ($notice): ?><div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100 text-sm"><?=h($notice)?></div><?php endif; ?>

  <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 md:p-5">
    <form method="get" class="flex flex-col lg:flex-row lg:items-end gap-3 lg:gap-4">
      <div class="flex-1 min-w-0">
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Search</label>
        <input type="text" name="search" value="<?=h($search)?>" placeholder="Membership ID or phone" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400">
      </div>
      <div class="w-full lg:w-52">
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Sort</label>
        <select name="sort" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800">
          <option value="newest" <?=$sort === 'newest' ? 'selected' : ''?>>Newest first</option>
          <option value="oldest" <?=$sort === 'oldest' ? 'selected' : ''?>>Oldest first</option>
          <option value="membership_asc" <?=$sort === 'membership_asc' ? 'selected' : ''?>>Membership ID A–Z</option>
          <option value="membership_desc" <?=$sort === 'membership_desc' ? 'selected' : ''?>>Membership ID Z–A</option>
        </select>
      </div>
      <button type="submit" class="w-full lg:w-auto px-5 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-semibold hover:bg-slate-700">Apply</button>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm leading-tight">
        <thead class="bg-slate-50/90 text-xs text-slate-600">
          <tr>
            <th class="text-left py-2.5 px-3 font-semibold">Member</th>
            <th class="text-left py-2.5 px-3 font-semibold">Membership ID</th>
            <th class="text-left py-2.5 px-3 font-semibold whitespace-nowrap">Submitted</th>
            <th class="text-right py-2.5 px-3 font-semibold w-[1%]">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m): ?>
            <?php $photoUrl = photo_url_for_member($m); ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50/80">
              <td class="py-2.5 px-3 align-middle">
                <div class="flex items-center gap-2 min-w-0">
                  <?php if ($photoUrl): ?>
                    <img src="<?=h($photoUrl)?>" alt="" class="w-8 h-8 shrink-0 rounded-lg object-cover border border-slate-200">
                  <?php else: ?>
                    <div class="w-8 h-8 shrink-0 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 flex items-center justify-center text-xs"><i class="fa-solid fa-user"></i></div>
                  <?php endif; ?>
                  <span class="font-semibold text-sm text-slate-800 truncate"><?=h($m['firstname'].' '.$m['surname'])?><?php if (is_new_member($m)): ?> <span class="text-[10px] px-1 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold align-middle border border-emerald-100">NEW</span><?php endif; ?></span>
                </div>
              </td>
              <td class="py-2.5 px-3 align-middle font-semibold text-sm text-slate-800 whitespace-nowrap"><?=h($m['membership_id'])?></td>
              <td class="py-2.5 px-3 align-middle text-slate-600 text-xs whitespace-nowrap"><?=h(date('d M Y H:i', strtotime($m['created_at'])))?></td>
              <td class="py-2.5 px-3 align-middle text-right">
                <div class="inline-flex flex-nowrap items-center justify-end gap-1 max-w-[min(100%,22rem)] overflow-x-auto">
                <a class="shrink-0 px-2 py-1 rounded-md bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-100" href="member.php?id=<?=$m['id']?>">Details</a>
                <?php if ($m['pdf_path']): ?>
                  <a class="shrink-0 px-2 py-1 rounded-md bg-slate-50 text-slate-700 text-xs font-semibold border border-slate-200" target="_blank" href="view_pdf.php?id=<?=$m['id']?>">PDF</a>
                  <a class="shrink-0 px-2 py-1 rounded-md bg-slate-50 text-slate-700 text-xs font-semibold border border-slate-200" target="_blank" href="print_pdf.php?id=<?=$m['id']?>">Print</a>
                <?php endif; ?>
                <?php if (!is_field_officer()): ?>
                <form method="post" action="regenerate_pdf.php" class="inline shrink-0">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="px-2 py-1 rounded-md bg-indigo-50 text-indigo-800 text-xs font-semibold border border-indigo-100">Regen</button>
                </form>
                <?php endif; ?>
                <?php if (can_delete_members()): ?>
                <form method="post" action="delete_member.php" class="inline shrink-0" onsubmit="return confirm('Archive this member record? Files are kept for audit.');">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="px-2 py-1 rounded-md bg-red-50 text-red-800 text-xs font-semibold border border-red-100">Delete</button>
                </form>
                <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$members): ?>
            <tr><td colspan="4" class="p-10 text-center text-slate-500 text-sm">No submissions yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
    <p>Page <?=$page?> of <?=$totalPages?> · <?=$totalRecords?> match<?=$totalRecords !== 1 ? 'es' : ''?></p>
    <div class="flex items-center gap-2">
      <?php if ($page > 1): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700" href="<?=h(page_url($page - 1, $search, $sort))?>">Previous</a><?php endif; ?>
      <?php if ($page < $totalPages): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700" href="<?=h(page_url($page + 1, $search, $sort))?>">Next</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php render_layout_end(); ?>
