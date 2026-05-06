<?php
require 'layout.php';
require_admin();
$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) AS total FROM members')->fetch()['total'];
$today = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE date(created_at) = date('now')")->fetch()['total'];
$thisMonth = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['total'];
$last7Days = $pdo->query("SELECT date(created_at) AS day, COUNT(*) AS total FROM members WHERE date(created_at) >= date('now', '-6 days') GROUP BY date(created_at) ORDER BY day ASC")->fetchAll();
$lastWeek = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE date(created_at) BETWEEN date('now', '-13 days') AND date('now', '-7 days')")->fetch()['total'];
$currentWeek = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE date(created_at) >= date('now', '-6 days')")->fetch()['total'];
$trendPercent = $lastWeek > 0 ? round((($currentWeek - $lastWeek) / $lastWeek) * 100, 1) : ($currentWeek > 0 ? 100 : 0);
$recentMembers = $pdo->query("SELECT * FROM members ORDER BY id DESC LIMIT 5")->fetchAll();
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
  <div class="bg-white rounded-3xl border mt-8 p-6">
    <h2 class="font-bold text-xl">Quick Actions</h2>
    <p class="text-slate-500 mt-2">Manage forms or open the Branch Executive database.</p>
    <div class="mt-5 flex flex-wrap gap-3">
      <a href="received_list.php" class="inline-block px-5 py-3 rounded-xl bg-slate-950 text-white font-bold">
        <i class="fa-solid fa-table-list mr-2"></i>Go to List Received
      </a>
      <a href="membership_database.php" class="inline-block px-5 py-3 rounded-xl bg-white border font-bold text-slate-800">
        <i class="fa-solid fa-user-tie mr-2"></i>Open Branch Executive
      </a>
    </div>
  </div>
  <div class="bg-white rounded-3xl border mt-8 p-6">
    <h2 class="font-bold text-xl">System Trends</h2>
    <p class="text-slate-500 mt-2">Quick trend view for the last 7 days.</p>
    <div class="mt-5 grid md:grid-cols-2 gap-4">
      <div class="rounded-2xl border p-4 bg-slate-50">
        <p class="text-sm text-slate-500">Week-over-week trend</p>
        <p class="text-2xl font-black mt-1 <?=$trendPercent >= 0 ? 'text-emerald-600' : 'text-red-600'?>">
          <?=$trendPercent >= 0 ? '+' : ''?><?=$trendPercent?>%
        </p>
        <p class="text-sm text-slate-500 mt-1">Current 7 days: <?=$currentWeek?> | Previous 7 days: <?=$lastWeek?></p>
      </div>
      <div class="rounded-2xl border p-4 bg-slate-50">
        <p class="text-sm text-slate-500 mb-2">Daily submissions (last 7 days)</p>
        <div class="space-y-2">
          <?php foreach ($last7Days as $day): ?>
            <div class="flex items-center justify-between text-sm">
              <span class="text-slate-600"><?=h(date('D, d M', strtotime($day['day'])))?></span>
              <span class="font-bold"><?=h($day['total'])?></span>
            </div>
          <?php endforeach; ?>
          <?php if (!$last7Days): ?><p class="text-sm text-slate-500">No trend data yet.</p><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="bg-white rounded-3xl border mt-8 p-6">
    <h2 class="font-bold text-xl">Recent Submissions</h2>
    <p class="text-slate-500 mt-1">Quick manage actions from dashboard.</p>
    <div class="mt-4 space-y-3">
      <?php foreach ($recentMembers as $m): ?>
        <div class="border rounded-xl p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <p class="font-bold"><?=h($m['membership_id'])?> - <?=h($m['firstname'].' '.$m['surname'])?></p>
            <p class="text-sm text-slate-500"><?=h($m['phone_no'])?> | <?=h($m['branch'])?></p>
          </div>
          <div class="flex flex-wrap gap-3 text-sm">
            <a class="text-emerald-700 font-bold" href="member.php?id=<?=$m['id']?>">Details</a>
            <?php if(!empty($m['photo_path'])): ?><a class="text-pink-700 font-bold" target="_blank" href="<?=h($m['photo_path'])?>">Photo</a><?php endif; ?>
            <a class="text-blue-700 font-bold" target="_blank" href="view_pdf.php?id=<?=$m['id']?>">PDF</a>
            <form method="post" action="delete_member.php" onsubmit="return confirm('Delete this member and related files?');">
              <input type="hidden" name="id" value="<?=$m['id']?>">
              <button type="submit" class="text-red-700 font-bold">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$recentMembers): ?><p class="text-slate-500">No submissions yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php render_layout_end(); ?>
