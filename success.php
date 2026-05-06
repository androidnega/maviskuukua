<?php
require 'layout.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) redirect('register.php');
?>
<?php render_layout_start('Success', 'register'); ?>
<div class="min-h-[75vh] flex items-center justify-center px-4">
  <div class="bg-white max-w-lg w-full rounded-3xl border p-8 text-center">
    <div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl"><i class="fa-solid fa-check"></i></div>
    <h1 class="text-3xl font-black mt-6">Registration Submitted</h1>
    <p class="text-slate-500 mt-2">Your registration has been received successfully.</p>
    <div class="mt-6 flex justify-center gap-3">
      <?php if($m['pdf_path']): ?>
        <a target="_blank" class="px-4 py-2 rounded-xl bg-slate-950 text-white" href="view_pdf.php?id=<?=$id?>"><i class="fa-solid fa-eye mr-1"></i>View PDF</a>
        <a class="px-4 py-2 rounded-xl bg-white border" href="view_pdf.php?id=<?=$id?>&download=1"><i class="fa-solid fa-download mr-1"></i>Download PDF</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php render_layout_end(); ?>
