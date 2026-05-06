<?php
require 'layout.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) redirect('admin.php');
?>
<?php render_layout_start('Member Details', 'dashboard'); ?>
<div class="max-w-4xl mx-auto">
  <a href="admin.php" class="text-sm text-slate-600"><i class="fa-solid fa-arrow-left mr-1"></i>Back to dashboard</a>
  <div class="bg-white rounded-3xl border p-6 md:p-8 mt-4">
    <div class="flex gap-6 flex-col md:flex-row">
      <?php if($m['photo_path']): ?><img src="<?=h($m['photo_path'])?>" class="w-40 h-40 rounded-2xl object-cover border"><?php endif; ?>
      <div>
        <h1 class="text-3xl font-black"><?=h($m['first_name'].' '.$m['other_names'].' '.$m['last_name'])?></h1>
        <p class="text-slate-500 mt-1"><?=h($m['membership_id'])?></p>
        <?php if($m['pdf_path']): ?>
          <div class="mt-4 flex gap-3">
            <a target="_blank" class="px-4 py-2 rounded-xl bg-slate-950 text-white" href="view_pdf.php?id=<?=$id?>"><i class="fa-solid fa-eye mr-1"></i>View PDF</a>
            <a class="px-4 py-2 rounded-xl bg-white border" href="view_pdf.php?id=<?=$id?>&download=1"><i class="fa-solid fa-download mr-1"></i>Download PDF</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <dl class="grid md:grid-cols-2 gap-4 mt-8"><?php foreach(['gender'=>'Gender','date_of_birth'=>'Date of Birth','phone'=>'Phone','email'=>'Email','community'=>'Community','electoral_area'=>'Electoral Area','voter_id'=>'Voter ID','ghana_card'=>'Ghana Card','occupation'=>'Occupation','created_at'=>'Submitted At'] as $key=>$label): ?><div class="bg-slate-50 rounded-2xl p-4"><dt class="text-slate-500 text-sm"><?=$label?></dt><dd class="font-bold mt-1"><?=h($m[$key] ?: 'N/A')?></dd></div><?php endforeach; ?></dl>
  </div>
</div>
<?php render_layout_end(); ?>
