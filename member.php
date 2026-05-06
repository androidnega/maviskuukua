<?php
require 'layout.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) redirect('admin.php');
if (!empty($m['deleted_at']) && is_field_officer()) {
    flash('admin_notice', 'This record is not available.');
    redirect('received_list.php');
}
if (empty($m['viewed_at'])) {
    $markViewed = db()->prepare('UPDATE members SET viewed_at = ? WHERE id = ?');
    $markViewed->execute([date('c'), $id]);
    $m['viewed_at'] = date('c');
}

$photoUrl = null;
if (!empty($m['photo_path']) && is_file(BASE_DIR . '/' . ltrim((string)$m['photo_path'], '/'))) {
    $photoUrl = (string)$m['photo_path'];
} else {
    $fallback = 'storage/photos/photo_' . (int)$m['id'] . '.jpg';
    if (is_file(BASE_DIR . '/' . ltrim($fallback, '/'))) {
        $photoUrl = $fallback;
    }
}
$safeMembershipId = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$m['membership_id']);
$photoDownloadName = 'member_' . (int)$m['id'] . '_' . $safeMembershipId . '_photo.jpg';
?>
<?php render_layout_start('Member Details', 'received_list'); ?>
<div class="max-w-6xl mx-auto">
  <a href="received_list.php" class="text-sm text-slate-600"><i class="fa-solid fa-arrow-left mr-1"></i>Back to registrations</a>
  <div class="bg-white  border border-slate-200 p-6 md:p-8 mt-4">
    <?php if (!empty($m['deleted_at'])): ?>
      <div class="mb-5 px-4 py-3  bg-amber-50 border border-amber-200 text-amber-900 text-sm font-semibold">
        <i class="fa-solid fa-box-archive mr-1"></i> This record is archived (soft-deleted). Files may still be available for audit.
      </div>
    <?php endif; ?>
    <div class="flex flex-col md:flex-row md:items-center gap-5">
      <?php if($photoUrl): ?>
        <div class="w-28 h-36 md:w-36 md:h-44  p-1.5 border border-slate-300 bg-white">
          <img src="<?=h($photoUrl)?>" class="w-full h-full  object-cover object-top bg-slate-50" alt="Member photo">
        </div>
      <?php else: ?>
        <div class="w-28 h-36 md:w-36 md:h-44  border border-slate-300 bg-slate-100 text-slate-500 flex items-center justify-center text-3xl"><i class="fa-solid fa-user"></i></div>
      <?php endif; ?>
      <div class="flex-1">
        <h1 class="text-3xl font-black"><?=h($m['firstname'].' '.$m['surname'])?></h1>
        <p class="text-slate-500 mt-1"><?=h($m['membership_id'])?></p>
        <div class="mt-4 flex flex-wrap gap-2">
          <?php if($photoUrl): ?>
            <a target="_blank" class="px-3 py-2  border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50" href="<?=h($photoUrl)?>"><i class="fa-solid fa-image mr-1"></i>View Photo</a>
            <a class="px-3 py-2  border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50" href="<?=h($photoUrl)?>" download="<?=h($photoDownloadName)?>"><i class="fa-solid fa-download mr-1"></i>Download Photo</a>
          <?php endif; ?>
          <?php if($m['pdf_path']): ?>
            <a target="_blank" class="px-3 py-2  bg-slate-900 text-white text-sm font-medium hover:bg-slate-800" href="view_pdf.php?id=<?=$id?>"><i class="fa-solid fa-eye mr-1"></i>View PDF</a>
            <a class="px-3 py-2  border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50" href="view_pdf.php?id=<?=$id?>&download=1"><i class="fa-solid fa-download mr-1"></i>Download PDF</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
    $detailFields = [
        ['firstname', 'Firstname'],
        ['surname', 'Surname'],
        ['place_of_birth', 'Place of birth'],
        ['date_of_birth', 'Date of birth', 'date'],
        ['branch', 'Branch'],
        ['phone_no', 'Phone no'],
        ['year_joined', 'Year joined'],
        ['voter_id_no', 'Voters ID no'],
        ['ghana_card_no', 'Ghana card no'],
        ['positions_held', 'Positions held'],
        ['languages', 'Languages'],
        ['profession', 'Profession'],
        ['membership_id', 'Membership ID'],
        ['proposer_name', "Proposer's name"],
        ['proposer_party_id', "Proposer's party ID"],
        ['proposer_phone_no', "Proposer's phone"],
        ['created_at', 'Submitted at', 'datetime'],
    ];
    ?>
    <dl class="grid sm:grid-cols-2 xl:grid-cols-3 gap-2.5 mt-8">
    <?php foreach ($detailFields as $row):
        [$key, $label] = $row;
        $fmt = $row[2] ?? 'text';
        $raw = trim((string)($m[$key] ?? ''));
        if ($fmt === 'date' && $raw !== '' && strtotime($raw)) {
            $display = date('d M Y', strtotime($raw));
        } elseif ($fmt === 'datetime' && $raw !== '') {
            $t = strtotime($raw);
            $display = $t ? date('d M Y, H:i', $t) : $raw;
        } else {
            $display = $raw !== '' ? $raw : 'N/A';
        }
        ?>
    <div class="bg-slate-50  px-3 py-2 border border-slate-100"><dt class="text-slate-500 text-[11px] leading-tight"><?=h($label)?></dt><dd class="font-semibold mt-0.5 text-sm leading-snug break-words"><?=h($display)?></dd></div>
    <?php endforeach; ?>
    </dl>
  </div>
</div>
<?php render_layout_end(); ?>
