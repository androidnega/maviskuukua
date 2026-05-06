<?php
require 'layout.php';
require_admin();
require_branch_executive_section();
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM members WHERE deleted_at IS NULL AND LOWER(positions_held) LIKE ? ORDER BY firstname ASC, surname ASC");
$stmt->execute(['%executive%']);
$executives = $stmt->fetchAll();
?>
<?php render_layout_start('Branch Executive', 'branch_executive'); ?>
<div class="max-w-7xl mx-auto">
  <h1 class="text-3xl font-black">Branch Executive</h1>
  <p class="text-slate-500 mt-1">Executive member directory with photo and quick profile access.</p>

  <div class="mt-4 flex flex-wrap gap-2">
    <a href="export_executives.php?format=csv" class="px-3 py-2  border border-slate-200 bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-csv mr-1 text-emerald-600"></i>Executive CSV</a>
    <a href="export_executives.php?format=excel" class="px-3 py-2  border border-slate-200 bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Executive Excel</a>
    <a href="export_executives.php?format=pdf" class="px-3 py-2  border border-slate-200 bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-pdf mr-1 text-emerald-600"></i>Executive PDF</a>
    <?php if (can_export_bulk_members()): ?>
    <a href="export_members_bulk.php?format=csv" class="px-3 py-2  border border-slate-200 bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-export mr-1 text-slate-600"></i>All members CSV</a>
    <a href="export_members_bulk.php?format=excel" class="px-3 py-2  border border-slate-200 bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-export mr-1 text-slate-600"></i>All members Excel</a>
    <a href="export_members_bulk.php?format=pdf" class="px-3 py-2  border border-slate-200 bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-export mr-1 text-slate-600"></i>All members PDF</a>
    <?php endif; ?>
  </div>

  <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($executives as $m): ?>
      <div class="bg-white border  p-4">
        <div class="flex items-center gap-3">
          <?php if (!empty($m['photo_path'])): ?>
            <img src="<?=h($m['photo_path'])?>" alt="Executive photo" class="w-14 h-14  object-cover border">
          <?php else: ?>
            <div class="w-14 h-14  bg-slate-100 border flex items-center justify-center text-slate-500"><i class="fa-solid fa-user"></i></div>
          <?php endif; ?>
          <div>
            <p class="font-bold"><?=h($m['firstname'] . ' ' . $m['surname'])?></p>
            <p class="text-xs text-slate-500"><?=h($m['positions_held'])?></p>
          </div>
        </div>
        <div class="mt-4 text-sm text-slate-600 space-y-1">
          <p><span class="font-semibold">Branch:</span> <?=h($m['branch'])?></p>
          <p><span class="font-semibold">Phone:</span> <?=h($m['phone_no'])?></p>
          <p><span class="font-semibold">Membership ID:</span> <?=h($m['membership_id'])?></p>
        </div>
        <a href="member.php?id=<?=$m['id']?>" class="inline-block mt-4 text-emerald-700 font-bold"><i class="fa-solid fa-circle-info mr-1"></i>View Details</a>
      </div>
    <?php endforeach; ?>
    <?php if (!$executives): ?>
      <div class="bg-white border  p-6 text-slate-500 sm:col-span-2 lg:col-span-3">
        No branch executive records found yet. Members with positions containing "executive" will show here.
      </div>
    <?php endif; ?>
  </div>
</div>
<?php render_layout_end(); ?>
