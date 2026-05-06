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
        <p class="text-sm text-slate-500 mb-2">Submissions movement (7 days)</p>
        <div class="h-64">
          <canvas id="submissionsChart"></canvas>
        </div>
      </div>
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
          borderRadius: 8,
          backgroundColor: 'rgba(16, 185, 129, 0.65)',
          borderColor: 'rgba(5, 150, 105, 1)',
          borderWidth: 1.5
        },
        {
          type: 'line',
          label: 'Cumulative total',
          data: cumulative,
          tension: 0.35,
          fill: false,
          borderColor: 'rgba(37, 99, 235, 1)',
          pointBackgroundColor: 'rgba(37, 99, 235, 1)',
          pointRadius: 3.5,
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
            color: '#334155'
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
