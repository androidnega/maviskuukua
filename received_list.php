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
$whereSql = '';
$params = [];
if ($search !== '') {
    $whereSql = ' WHERE membership_id LIKE ? OR phone_no LIKE ? ';
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
$unreadCount = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE viewed_at IS NULL")->fetch()['total'];

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
<?php render_layout_start('List Received', 'received_list'); ?>
<div class="max-w-7xl mx-auto">
  <div class="flex items-start justify-between gap-4 flex-wrap">
    <div>
      <h1 class="text-3xl font-black">List Received</h1>
      <p class="text-slate-500 mt-1">All submitted membership forms.</p>
    </div>
    <span class="px-3 py-1 rounded-full bg-emerald-600 text-white text-sm font-semibold"><?=$unreadCount?> new</span>
  </div>
  <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>
  <form method="get" class="mt-5 grid grid-cols-1 md:grid-cols-4 gap-3">
    <input type="text" name="search" value="<?=h($search)?>" placeholder="Search by Membership ID or Phone" class="md:col-span-3 rounded-xl border p-3">
    <select name="sort" class="rounded-xl border p-3">
      <option value="newest" <?=$sort === 'newest' ? 'selected' : ''?>>Sort: Newest first</option>
      <option value="oldest" <?=$sort === 'oldest' ? 'selected' : ''?>>Sort: Oldest first</option>
      <option value="membership_asc" <?=$sort === 'membership_asc' ? 'selected' : ''?>>Sort: Membership ID A-Z</option>
      <option value="membership_desc" <?=$sort === 'membership_desc' ? 'selected' : ''?>>Sort: Membership ID Z-A</option>
    </select>
    <button class="md:col-span-4 w-full md:w-auto px-5 py-3 rounded-xl bg-slate-950 text-white font-bold">Apply Search / Sort</button>
  </form>
  <div class="bg-white rounded-3xl border mt-8 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm leading-tight">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
          <tr>
            <th class="text-left py-2 px-3 font-semibold">Member</th>
            <th class="text-left py-2 px-3 font-semibold">Membership ID</th>
            <th class="text-left py-2 px-3 font-semibold whitespace-nowrap">Submitted</th>
            <th class="text-right py-2 px-3 font-semibold w-[1%]">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($members as $m): ?>
            <?php $photoUrl = photo_url_for_member($m); ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50/70">
              <td class="py-2 px-3 align-middle">
                <div class="flex items-center gap-2 min-w-0">
                  <?php if($photoUrl): ?>
                    <img src="<?=h($photoUrl)?>" alt="" class="w-8 h-8 shrink-0 rounded-lg object-cover border border-slate-200">
                  <?php else: ?>
                    <div class="w-8 h-8 shrink-0 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 flex items-center justify-center text-xs"><i class="fa-solid fa-user"></i></div>
                  <?php endif; ?>
                  <span class="font-semibold text-sm truncate"><?=h($m['firstname'].' '.$m['surname'])?><?php if(is_new_member($m)): ?> <span class="text-[10px] px-1 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold align-middle">NEW</span><?php endif; ?></span>
                </div>
              </td>
              <td class="py-2 px-3 align-middle font-bold text-sm whitespace-nowrap"><?=h($m['membership_id'])?></td>
              <td class="py-2 px-3 align-middle text-slate-600 text-xs whitespace-nowrap"><?=h(date('d M Y', strtotime($m['created_at'])))?></td>
              <td class="py-2 px-3 align-middle text-right">
                <div class="inline-flex flex-nowrap items-center justify-end gap-1 max-w-[min(100%,22rem)] overflow-x-auto">
                <a class="shrink-0 px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-semibold" href="member.php?id=<?=$m['id']?>">Details</a>
                <?php if($m['pdf_path']): ?>
                  <a class="shrink-0 px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold" target="_blank" href="view_pdf.php?id=<?=$m['id']?>">PDF</a>
                  <a class="shrink-0 px-2 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-semibold" target="_blank" href="print_pdf.php?id=<?=$m['id']?>">Print</a>
                <?php endif; ?>
                <form method="post" action="regenerate_pdf.php" class="inline shrink-0">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="px-2 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">Regen</button>
                </form>
                <form method="post" action="delete_member.php" class="inline shrink-0" onsubmit="return confirm('Delete this member and related files?');">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="px-2 py-1 rounded-md bg-red-50 text-red-700 text-xs font-semibold">Del</button>
                </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$members): ?>
            <tr><td colspan="4" class="p-8 text-center text-slate-500">No submissions yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if($totalPages > 1): ?>
  <div class="mt-4 flex items-center justify-between gap-3 text-sm">
    <p class="text-slate-500">Showing page <?=$page?> of <?=$totalPages?> (<?=$totalRecords?> records)</p>
    <div class="flex items-center gap-2">
      <?php if($page > 1): ?><a class="px-3 py-1.5 rounded-lg border bg-white" href="<?=h(page_url($page - 1, $search, $sort))?>">Previous</a><?php endif; ?>
      <?php if($page < $totalPages): ?><a class="px-3 py-1.5 rounded-lg border bg-white" href="<?=h(page_url($page + 1, $search, $sort))?>">Next</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php render_layout_end(); ?>
