<?php
require 'layout.php';
require_admin();
$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) AS total FROM members')->fetch()['total'];
$today = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE date(created_at) = date('now')")->fetch()['total'];
$thisMonth = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['total'];
$members = $pdo->query('SELECT * FROM members ORDER BY id DESC')->fetchAll();
?>
<?php render_layout_start('Dashboard', 'dashboard'); ?>
<div class="max-w-7xl mx-auto">
  <div>
    <h1 class="text-3xl font-black">Admin Dashboard</h1>
    <p class="text-slate-500">Membership submissions</p>
  </div>
  <div class="grid md:grid-cols-3 gap-4 mt-8">
    <div class="bg-white rounded-2xl border p-6">
      <p class="text-slate-500"><i class="fa-solid fa-file-signature mr-2 text-emerald-600"></i>Total Forms</p>
      <h2 class="text-4xl font-black mt-2"><?=$total?></h2>
    </div>
    <div class="bg-white rounded-2xl border p-6">
      <p class="text-slate-500"><i class="fa-solid fa-calendar-day mr-2 text-blue-600"></i>New Today</p>
      <h2 class="text-4xl font-black mt-2"><?=$today?></h2>
    </div>
    <div class="bg-white rounded-2xl border p-6">
      <p class="text-slate-500"><i class="fa-solid fa-chart-column mr-2 text-violet-600"></i>This Month</p>
      <h2 class="text-4xl font-black mt-2"><?=$thisMonth?></h2>
    </div>
  </div>

  <div class="bg-white rounded-3xl border mt-8 overflow-hidden">
    <div class="p-6 border-b"><h2 class="font-bold text-xl">All Submissions</h2></div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="text-left p-4">ID</th>
            <th class="text-left p-4">Name</th>
            <th class="text-left p-4">Phone</th>
            <th class="text-left p-4">Community</th>
            <th class="text-left p-4">Submitted</th>
            <th class="text-left p-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($members as $m): ?>
            <tr class="border-t">
              <td class="p-4 font-bold"><?=h($m['membership_id'])?></td>
              <td class="p-4"><?=h($m['first_name'].' '.$m['last_name'])?></td>
              <td class="p-4"><?=h($m['phone'])?></td>
              <td class="p-4"><?=h($m['community'])?></td>
              <td class="p-4"><?=h(date('d M Y, H:i', strtotime($m['created_at'])))?></td>
              <td class="p-4 space-x-3">
                <a class="text-emerald-700 font-bold" href="member.php?id=<?=$m['id']?>"><i class="fa-solid fa-circle-info mr-1"></i>Details</a>
                <?php if($m['pdf_path']): ?>
                  <a class="text-blue-700 font-bold" target="_blank" href="view_pdf.php?id=<?=$m['id']?>"><i class="fa-solid fa-eye mr-1"></i>View PDF</a>
                  <a class="text-slate-700 font-bold" href="view_pdf.php?id=<?=$m['id']?>&download=1"><i class="fa-solid fa-download mr-1"></i>Download</a>
                <?php endif; ?>
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
