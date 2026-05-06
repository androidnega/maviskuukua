<?php
require 'layout.php';
require_admin();
$pdo = db();
$notice = flash('admin_notice');
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$orderBy = 'created_at DESC';
if ($sort === 'oldest') {
    $orderBy = 'created_at ASC';
} elseif ($sort === 'membership_asc') {
    $orderBy = 'membership_id ASC';
} elseif ($sort === 'membership_desc') {
    $orderBy = 'membership_id DESC';
}

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE membership_id LIKE ? OR phone_no LIKE ? ORDER BY $orderBy");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $members = $stmt->fetchAll();
} else {
    $members = $pdo->query("SELECT * FROM members ORDER BY $orderBy")->fetchAll();
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
    <span class="px-3 py-1 rounded-full bg-slate-900 text-white text-sm font-semibold"><?=count($members)?> records</span>
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
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="text-left p-4">Member</th>
            <th class="text-left p-4">Membership ID</th>
            <th class="text-left p-4">Phone</th>
            <th class="text-left p-4">Submitted</th>
            <th class="text-left p-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($members as $m): ?>
            <?php $photoUrl = photo_url_for_member($m); ?>
            <tr class="border-t hover:bg-slate-50/70">
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <?php if($photoUrl): ?>
                    <img src="<?=h($photoUrl)?>" alt="Photo" class="w-10 h-10 rounded-xl object-cover border">
                  <?php else: ?>
                    <div class="w-10 h-10 rounded-xl border bg-slate-100 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-user"></i></div>
                  <?php endif; ?>
                  <div>
                    <p class="font-semibold"><?=h($m['firstname'].' '.$m['surname'])?></p>
                    <p class="text-xs text-slate-500"><?=h($m['branch'])?></p>
                  </div>
                </div>
              </td>
              <td class="p-4 font-bold"><?=h($m['membership_id'])?></td>
              <td class="p-4"><?=h($m['phone_no'])?></td>
              <td class="p-4"><?=h(date('d M Y, H:i', strtotime($m['created_at'])))?></td>
              <td class="p-4">
                <div class="flex flex-wrap items-center gap-2">
                <a class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold" href="member.php?id=<?=$m['id']?>">Details</a>
                <?php if($m['pdf_path']): ?>
                  <a class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 font-semibold" target="_blank" href="view_pdf.php?id=<?=$m['id']?>">PDF</a>
                <?php endif; ?>
                <form method="post" action="regenerate_pdf.php" class="inline">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-semibold">Regenerate</button>
                </form>
                <form method="post" action="delete_member.php" class="inline" onsubmit="return confirm('Delete this member and related files?');">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 font-semibold">Delete</button>
                </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$members): ?>
            <tr><td colspan="6" class="p-8 text-center text-slate-500">No submissions yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php render_layout_end(); ?>
