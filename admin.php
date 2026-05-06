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
?>
<?php render_layout_start('Dashboard', 'dashboard'); ?>
<div class="max-w-7xl mx-auto">
  <div class="flex items-center justify-between gap-3 flex-wrap">
    <div>
      <h1 class="text-3xl font-black text-slate-900">Admin Dashboard</h1>
      <p class="text-slate-500 text-sm">Membership submissions overview</p>
    </div>
    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-sm font-semibold border border-emerald-200">
      <i class="fa-solid fa-arrow-trend-up"></i>
      <?=$trendPercent >= 0 ? '+' : ''?><?=$trendPercent?>% this week
    </span>
  </div>
  <div class="grid md:grid-cols-3 gap-4 mt-6">
    <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 text-sm font-semibold">Total Forms</p>
        <span class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <i class="fa-solid fa-file-signature"></i>
        </span>
      </div>
      <h2 class="text-4xl font-black mt-3 text-slate-900"><?=$total?></h2>
    </div>
    <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 text-sm font-semibold">New Today</p>
        <span class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <i class="fa-solid fa-calendar-day"></i>
        </span>
      </div>
      <h2 class="text-4xl font-black mt-3 text-slate-900"><?=$today?></h2>
    </div>
    <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 text-sm font-semibold">This Month</p>
        <span class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <i class="fa-solid fa-chart-column"></i>
        </span>
      </div>
      <h2 class="text-4xl font-black mt-3 text-slate-900"><?=$thisMonth?></h2>
    </div>
  </div>
  <div class="bg-white rounded-3xl border border-emerald-100 mt-6 p-6 shadow-sm">
    <h2 class="font-bold text-lg text-slate-900">Quick Actions</h2>
    <div class="mt-4 flex flex-wrap gap-3">
      <a href="received_list.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-500">
        <i class="fa-solid fa-table-list"></i>List Received
      </a>
      <a href="membership_database.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50">
        <i class="fa-solid fa-user-tie text-emerald-600"></i>Branch Executive
      </a>
    </div>
  </div>
  <div class="bg-white rounded-3xl border border-emerald-100 mt-6 p-6 shadow-sm">
    <div class="flex items-center justify-between gap-2 flex-wrap">
      <h2 class="font-bold text-lg text-slate-900">Submission Trends (7 days)</h2>
      <p class="text-xs text-slate-500">Current: <?=$currentWeek?> | Previous: <?=$lastWeek?></p>
    </div>
    <div class="mt-4 h-72">
      <canvas id="submissionsChart"></canvas>
    </div>
  </div>
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
          backgroundColor: 'rgba(16, 185, 129, 0.72)',
          borderColor: 'rgba(5, 150, 105, 1)',
          borderWidth: 1.5
        },
        {
          type: 'line',
          label: 'Cumulative total',
          data: cumulative,
          tension: 0.35,
          fill: false,
          borderColor: 'rgba(4, 120, 87, 1)',
          pointBackgroundColor: 'rgba(4, 120, 87, 1)',
          pointRadius: 3,
          borderWidth: 2.5
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 150,
      animation: {
        duration: 1400,
        easing: 'easeOutQuart'
      },
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            boxWidth: 10,
            color: '#334155',
            font: { size: 12, weight: 600 }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#64748b' }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(148, 163, 184, 0.2)' },
          ticks: { color: '#64748b', precision: 0 }
        }
      }
    }
  });
}
</script>
<?php render_layout_end(); ?>
