<?php
require 'layout.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) redirect('admin.php');

$photoUrl = null;
if (!empty($m['photo_path']) && is_file(BASE_DIR . '/' . ltrim((string)$m['photo_path'], '/'))) {
    $photoUrl = (string)$m['photo_path'];
} else {
    $fallback = 'storage/photos/photo_' . (int)$m['id'] . '.jpg';
    if (is_file(BASE_DIR . '/' . ltrim($fallback, '/'))) {
        $photoUrl = $fallback;
    }
}
?>
<?php render_layout_start('Member Details', 'dashboard'); ?>
<div class="max-w-6xl mx-auto">
  <a href="received_list.php" class="text-sm text-slate-600"><i class="fa-solid fa-arrow-left mr-1"></i>Back to list</a>
  <div class="bg-white rounded-3xl border p-6 md:p-8 mt-4 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-center gap-5">
      <?php if($photoUrl): ?>
        <img src="<?=h($photoUrl)?>" class="w-28 h-28 md:w-36 md:h-36 rounded-2xl object-cover border" alt="Member photo">
      <?php else: ?>
        <div class="w-28 h-28 md:w-36 md:h-36 rounded-2xl border bg-slate-100 text-slate-500 flex items-center justify-center text-3xl"><i class="fa-solid fa-user"></i></div>
      <?php endif; ?>
      <div class="flex-1">
        <h1 class="text-3xl font-black"><?=h($m['firstname'].' '.$m['surname'])?></h1>
        <p class="text-slate-500 mt-1"><?=h($m['membership_id'])?></p>
        <div class="mt-4 flex flex-wrap gap-2">
          <?php if($photoUrl): ?><a target="_blank" class="px-3 py-2 rounded-xl bg-pink-50 text-pink-700 font-semibold" href="<?=h($photoUrl)?>"><i class="fa-solid fa-image mr-1"></i>View Photo</a><?php endif; ?>
          <?php if($m['pdf_path']): ?>
            <a target="_blank" class="px-3 py-2 rounded-xl bg-slate-900 text-white font-semibold" href="view_pdf.php?id=<?=$id?>"><i class="fa-solid fa-eye mr-1"></i>View PDF</a>
            <a class="px-3 py-2 rounded-xl bg-white border font-semibold" href="view_pdf.php?id=<?=$id?>&download=1"><i class="fa-solid fa-download mr-1"></i>Download PDF</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <dl class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3 mt-8"><?php foreach(['firstname'=>'Firstname','surname'=>'Surname','place_of_birth'=>'Place of birth','date_of_birth'=>'Date of birth','branch'=>'Branch','phone_no'=>'Phone no','year_joined'=>'Year joined','voter_id_no'=>'Voters ID no','ghana_card_no'=>'Ghana card no','positions_held'=>'Positions held','languages'=>'Languages','profession'=>'Profession','membership_id'=>'Membership ID','created_at'=>'Submitted At'] as $key=>$label): ?><div class="bg-slate-50 rounded-xl p-3 border border-slate-100"><dt class="text-slate-500 text-xs"><?=$label?></dt><dd class="font-semibold mt-1 text-sm leading-tight"><?=h($m[$key] ?: 'N/A')?></dd></div><?php endforeach; ?></dl>
  </div>
</div>
<?php render_layout_end(); ?>
