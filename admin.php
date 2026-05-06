<?php
require 'layout.php';
require_admin();
$pdo = db();
$active = members_active_clause();

$total = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active")->fetch()['total'];
$today = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active AND date(created_at) = date('now')")->fetch()['total'];
$thisMonth = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['total'];
$last7Days = $pdo->query("SELECT date(created_at) AS day, COUNT(*) AS total FROM members WHERE $active AND date(created_at) >= date('now', '-6 days') GROUP BY date(created_at) ORDER BY day ASC")->fetchAll();
$lastWeek = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active AND date(created_at) BETWEEN date('now', '-13 days') AND date('now', '-7 days')")->fetch()['total'];
$currentWeek = (int)$pdo->query("SELECT COUNT(*) AS total FROM members WHERE $active AND date(created_at) >= date('now', '-6 days')")->fetch()['total'];
$trendPercent = $lastWeek > 0 ? round((($currentWeek - $lastWeek) / $lastWeek) * 100, 1) : ($currentWeek > 0 ? 100 : 0);
$dailyMap = [];
foreach ($last7Days as $row) {
    $dailyMap[$row['day']] = (int)$row['total'];
}
$chartLabels = [];
$chartTotals = [];
$chartCumulative = [];
$runningTotal = 0;
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $count = $dailyMap[$day] ?? 0;
    $runningTotal += $count;
    $chartLabels[] = date('D', strtotime($day));
    $chartTotals[] = $count;
    $chartCumulative[] = $runningTotal;
}

$pieLabels = [];
$pieValues = [];
$pieColors = [];
if (is_coordinator() || is_super_admin()) {
    $branchRows = $pdo->query("SELECT branch, COUNT(*) AS c FROM members WHERE $active GROUP BY branch ORDER BY c DESC")->fetchAll();
    $palette = ['#10b981', '#6366f1', '#f59e0b', '#ec4899', '#0ea5e9', '#a855f7', '#14b8a6', '#f97316'];
    $i = 0;
    foreach ($branchRows as $br) {
        $pieLabels[] = (string)$br['branch'];
        $pieValues[] = (int)$br['c'];
        $pieColors[] = $palette[$i % count($palette)];
        $i++;
    }
}

$dashTitle = 'Admin Dashboard';
$accentBar = 'bg-emerald-50 text-emerald-700 border-emerald-200';
$cardBorder = 'border-emerald-100';
$chartBar = 'rgba(16, 185, 129, 0.72)';
$chartLine = 'rgba(4, 120, 87, 1)';
if (is_coordinator()) {
    $dashTitle = 'Coordinator Dashboard';
    $accentBar = 'bg-indigo-50 text-indigo-800 border-indigo-200';
    $cardBorder = 'border-indigo-100';
    $chartBar = 'rgba(99, 102, 241, 0.75)';
    $chartLine = 'rgba(67, 56, 202, 1)';
}
if (is_field_officer()) {
    $dashTitle = 'Field Officer Dashboard';
    $accentBar = 'bg-sky-50 text-sky-900 border-sky-200';
    $cardBorder = 'border-sky-100';
    $chartBar = 'rgba(14, 165, 233, 0.72)';
    $chartLine = 'rgba(3, 105, 161, 1)';
}
?>
<?php render_layout_start('Dashboard', 'dashboard'); ?>
<div class="max-w-7xl mx-auto">
  <div class="flex items-center justify-between gap-3 flex-wrap">
    <div>
      <h1 class="text-3xl font-black text-slate-900"><?=h($dashTitle)?></h1>
      <p class="text-slate-500 text-sm">Membership submissions overview</p>
    </div>
    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full <?=h($accentBar)?> text-sm font-semibold border">
      <i class="fa-solid fa-arrow-trend-up"></i>
      <?=$trendPercent >= 0 ? '+' : ''?><?=$trendPercent?>% this week
    </span>
  </div>
  <div class="grid md:grid-cols-3 gap-4 mt-6">
    <div class="bg-white rounded-2xl border <?=h($cardBorder)?> p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 text-sm font-semibold">Total Forms</p>
        <span class="h-9 w-9 rounded-xl <?= is_field_officer() ? 'bg-sky-50 text-sky-600' : (is_coordinator() ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600') ?> flex items-center justify-center">
          <i class="fa-solid fa-file-signature"></i>
        </span>
      </div>
      <h2 class="text-4xl font-black mt-3 text-slate-900"><?=$total?></h2>
    </div>
    <div class="bg-white rounded-2xl border <?=h($cardBorder)?> p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 text-sm font-semibold">New Today</p>
        <span class="h-9 w-9 rounded-xl <?= is_field_officer() ? 'bg-sky-50 text-sky-600' : (is_coordinator() ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600') ?> flex items-center justify-center">
          <i class="fa-solid fa-calendar-day"></i>
        </span>
      </div>
      <h2 class="text-4xl font-black mt-3 text-slate-900"><?=$today?></h2>
    </div>
    <div class="bg-white rounded-2xl border <?=h($cardBorder)?> p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 text-sm font-semibold">This Month</p>
        <span class="h-9 w-9 rounded-xl <?= is_field_officer() ? 'bg-sky-50 text-sky-600' : (is_coordinator() ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600') ?> flex items-center justify-center">
          <i class="fa-solid fa-chart-column"></i>
        </span>
      </div>
      <h2 class="text-4xl font-black mt-3 text-slate-900"><?=$thisMonth?></h2>
    </div>
  </div>
  <div class="bg-white rounded-3xl border <?=h($cardBorder)?> mt-6 p-6 shadow-sm">
    <h2 class="font-bold text-lg text-slate-900">Quick Actions</h2>
    <div class="mt-4 flex flex-wrap gap-3">
      <a href="received_list.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl <?= is_field_officer() ? 'bg-sky-600 hover:bg-sky-500' : (is_coordinator() ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-emerald-600 hover:bg-emerald-500') ?> text-white font-semibold">
        <i class="fa-solid fa-table-list"></i>List Received
      </a>
      <?php if (can_access_branch_executive_data()): ?>
      <a href="membership_database.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50">
        <i class="fa-solid fa-user-tie text-emerald-600"></i>Branch Executive
      </a>
      <?php endif; ?>
      <?php if (can_export_bulk_members()): ?>
      <a href="export_members_bulk.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50">
        <i class="fa-solid fa-file-export text-slate-600"></i>Bulk export
      </a>
      <?php endif; ?>
      <?php if (can_access_team_chat()): ?>
      <a href="team_chat.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50">
        <i class="fa-solid fa-comments text-indigo-600"></i>Team chat
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ((is_coordinator() || is_super_admin()) && count($pieValues) > 0): ?>
  <div class="grid lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-3xl border <?=h($cardBorder)?> p-6 shadow-sm">
      <h2 class="font-bold text-lg text-slate-900">Submissions by branch</h2>
      <p class="text-xs text-slate-500 mt-1">Active registrations only</p>
      <div class="mt-4 h-72 flex items-center justify-center">
        <canvas id="branchPie"></canvas>
      </div>
    </div>
    <div class="bg-white rounded-3xl border <?=h($cardBorder)?> p-6 shadow-sm">
      <div class="flex items-center justify-between gap-2 flex-wrap">
        <h2 class="font-bold text-lg text-slate-900">Submission Trends (7 days)</h2>
        <p class="text-xs text-slate-500">Current: <?=$currentWeek?> | Previous: <?=$lastWeek?></p>
      </div>
      <div class="mt-4 h-72">
        <canvas id="submissionsChart"></canvas>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="bg-white rounded-3xl border <?=h($cardBorder)?> mt-6 p-6 shadow-sm">
    <div class="flex items-center justify-between gap-2 flex-wrap">
      <h2 class="font-bold text-lg text-slate-900">Submission Trends (7 days)</h2>
      <p class="text-xs text-slate-500">Current: <?=$currentWeek?> | Previous: <?=$lastWeek?></p>
    </div>
    <div class="mt-4 h-72">
      <canvas id="submissionsChart"></canvas>
    </div>
  </div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartEl = document.getElementById('submissionsChart');
if (chartEl) {
  const labels = <?=json_encode($chartLabels)?>;
  const totals = <?=json_encode($chartTotals)?>;
  const cumulative = <?=json_encode($chartCumulative)?>;
  new Chart(chartEl, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          type: 'bar',
          label: 'Daily submissions',
          data: totals,
          borderRadius: 10,
          backgroundColor: <?=json_encode($chartBar)?>,
          borderColor: <?=json_encode($chartLine)?>,
          borderWidth: 1.5
        },
        {
          type: 'line',
          label: 'Cumulative total',
          data: cumulative,
          tension: 0.35,
          fill: false,
          borderColor: <?=json_encode($chartLine)?>,
          pointBackgroundColor: <?=json_encode($chartLine)?>,
          pointRadius: 3,
          borderWidth: 2.5
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 150,
      animation: { duration: 1400, easing: 'easeOutQuart' },
      plugins: {
        legend: {
          position: 'bottom',
          labels: { usePointStyle: true, boxWidth: 10, color: '#334155', font: { size: 12, weight: 600 } }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { color: '#64748b', precision: 0 } }
      }
    }
  });
}

const pieEl = document.getElementById('branchPie');
if (pieEl) {
  new Chart(pieEl, {
    type: 'doughnut',
    data: {
      labels: <?=json_encode($pieLabels)?>,
      datasets: [{
        data: <?=json_encode($pieValues)?>,
        backgroundColor: <?=json_encode($pieColors)?>,
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#334155', font: { size: 11, weight: 600 } } }
      }
    }
  });
}
</script>
<?php render_layout_end(); ?>
