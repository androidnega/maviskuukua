<?php
require 'layout.php';
require_admin();
require_audit_access();

$pdo = db();
$notice = flash('admin_notice');

$tab = strtolower(trim((string)($_GET['tab'] ?? 'activity')));
if (!in_array($tab, ['activity', 'tokens', 'archive'], true)) {
    $tab = 'activity';
}

$download = strtolower(trim((string)($_GET['download'] ?? '')));
$fmt = strtolower(trim((string)($_GET['fmt'] ?? 'csv')));
if ($download === 'activity' && ($fmt === 'csv' || $fmt === 'json')) {
    $logs = $pdo->query("SELECT * FROM audit_logs WHERE action <> 'chat_post' ORDER BY id DESC LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);
    if ($fmt === 'json') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.json"');
        echo json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'actor_admin_id', 'action', 'entity_type', 'entity_id', 'details_json', 'ip', 'created_at']);
    foreach ($logs as $row) {
        fputcsv($out, [
            $row['id'],
            $row['actor_admin_id'],
            $row['action'],
            $row['entity_type'],
            $row['entity_id'],
            $row['details_json'],
            $row['ip'],
            $row['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

$logs = $pdo->query("SELECT audit_logs.*, admins.username AS actor_username FROM audit_logs LEFT JOIN admins ON admins.id = audit_logs.actor_admin_id WHERE audit_logs.action <> 'chat_post' ORDER BY audit_logs.id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

$sqlTokens = 'SELECT t.*, m.membership_id,
    f.ip AS reg_ip, f.registration_started_at, f.submitted_at, f.success_page_viewed_at,
    f.pdf_downloaded_at, f.pdf_inline_viewed_at, f.visit_count AS funnel_visits
    FROM registration_tokens t
    LEFT JOIN members m ON m.id = t.member_id
    LEFT JOIN registration_funnel f ON f.member_id = t.member_id
    ORDER BY t.id DESC LIMIT 200';
$tokens = $pdo->query($sqlTokens)->fetchAll(PDO::FETCH_ASSOC);

$deleted = $pdo->query('SELECT id, membership_id, firstname, surname, phone_no, deleted_at, deleted_by_admin_id FROM members WHERE deleted_at IS NOT NULL ORDER BY datetime(deleted_at) DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
$snapCount = (int)$pdo->query('SELECT COUNT(*) AS c FROM member_audit_snapshots')->fetch()['c'];

$tabLink = function (string $t) use ($tab): string {
    $active = $t === $tab;

    return 'inline-flex items-center gap-2 px-4 py-2.5 rounded-t-lg border border-b-0 text-sm font-semibold transition-colors '
        . ($active
            ? 'bg-white border-slate-200 text-slate-900 relative top-[1px]'
            : 'border-transparent text-slate-500 hover:text-slate-700');
};
?>
<?php render_layout_start('Audit & Logs', 'audit'); ?>
<div class="max-w-7xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Audit &amp; logs</h1>
    <p class="text-slate-500 mt-1 text-sm">Exports exclude legacy chat actions.</p>
    <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100"><?=h($notice)?></div><?php endif; ?>
    <div class="mt-4 flex flex-wrap gap-2 items-center justify-between">
      <div class="flex flex-wrap gap-1 border-b border-slate-200 w-full md:w-auto">
        <a href="audit.php?tab=activity" class="<?=$tabLink('activity')?>">Recent activity</a>
        <a href="audit.php?tab=tokens" class="<?=$tabLink('tokens')?>">Registration tokens</a>
        <a href="audit.php?tab=archive" class="<?=$tabLink('archive')?>">Archived members</a>
      </div>
      <div class="flex flex-wrap gap-2 mt-2 md:mt-0">
        <a class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold" href="audit.php?download=activity&amp;fmt=csv">CSV</a>
        <a class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700" href="audit.php?download=activity&amp;fmt=json">JSON</a>
      </div>
    </div>
    <p class="text-xs text-slate-400 mt-3">Snapshots stored: <?=$snapCount?> · Registrations list hides archived rows by default.</p>
  </div>

  <div class="<?= $tab === 'activity' ? '' : 'hidden' ?>">
    <section class="bg-white rounded-2xl border border-slate-200 p-5 overflow-x-auto">
      <table class="w-full text-sm min-w-[720px]">
        <thead><tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-100">
          <th class="py-2 pr-2">When</th><th class="py-2 pr-2">Actor</th><th class="py-2 pr-2">Action</th><th class="py-2 pr-2">Entity</th><th class="py-2">Details</th>
        </tr></thead>
        <tbody>
          <?php foreach ($logs as $L): ?>
            <tr class="border-b border-slate-50 align-top">
              <td class="py-2 whitespace-nowrap text-xs text-slate-600"><?=h(date('d M Y H:i', strtotime((string)$L['created_at'])))?></td>
              <td class="py-2 text-xs"><?=h((string)($L['actor_username'] ?? '—'))?> <span class="text-slate-400">#<?=h((string)($L['actor_admin_id'] ?? ''))?></span></td>
              <td class="py-2 font-medium text-slate-800"><?=h((string)$L['action'])?></td>
              <td class="py-2 text-xs text-slate-600"><?=h((string)$L['entity_type'])?> #<?=h((string)($L['entity_id'] ?? ''))?></td>
              <td class="py-2 text-xs text-slate-500 max-w-md break-all"><?=h((string)($L['details_json'] ?? ''))?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$logs): ?>
            <tr><td colspan="5" class="py-8 text-center text-slate-500">No log entries yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>

  <div class="<?= $tab === 'tokens' ? '' : 'hidden' ?>">
    <section class="bg-white rounded-2xl border border-slate-200 p-5 overflow-x-auto">
      <p class="text-xs text-slate-500 mb-4">Per-applicant funnel when available (IP, timings, PDF view/download on public pages).</p>
      <table class="w-full text-sm min-w-[960px]">
        <thead><tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-100">
          <th class="py-2 pr-2">Token</th><th class="py-2 pr-2">Membership</th><th class="py-2 pr-2">Phone</th><th class="py-2 pr-2">IP</th>
          <th class="py-2 pr-2">Started</th><th class="py-2 pr-2">Submitted</th><th class="py-2 pr-2">Success view</th>
          <th class="py-2 pr-2">PDF open</th><th class="py-2 pr-2">PDF download</th><th class="py-2">Visits (form)</th>
        </tr></thead>
        <tbody>
          <?php foreach ($tokens as $t): ?>
            <tr class="border-b border-slate-50">
              <td class="py-2 font-mono font-semibold text-slate-800"><?=h((string)$t['token'])?></td>
              <td class="py-2"><?=h((string)($t['membership_id'] ?? ''))?></td>
              <td class="py-2 font-mono text-xs"><?=h((string)$t['phone_normalized'])?></td>
              <td class="py-2 font-mono text-xs"><?=h((string)($t['reg_ip'] ?? ''))?></td>
              <td class="py-2 text-xs text-slate-600 whitespace-nowrap"><?= $t['registration_started_at'] ? h(date('d M H:i', strtotime((string)$t['registration_started_at']))) : '—' ?></td>
              <td class="py-2 text-xs text-slate-600 whitespace-nowrap"><?= $t['submitted_at'] ? h(date('d M H:i', strtotime((string)$t['submitted_at']))) : '—' ?></td>
              <td class="py-2 text-xs"><?= $t['success_page_viewed_at'] ? h(date('d M H:i', strtotime((string)$t['success_page_viewed_at']))) : '—' ?></td>
              <td class="py-2 text-xs"><?= $t['pdf_inline_viewed_at'] ? h(date('d M H:i', strtotime((string)$t['pdf_inline_viewed_at']))) : '—' ?></td>
              <td class="py-2 text-xs"><?= $t['pdf_downloaded_at'] ? h(date('d M H:i', strtotime((string)$t['pdf_downloaded_at']))) : '—' ?></td>
              <td class="py-2 text-xs"><?=h((string)($t['funnel_visits'] ?? ''))?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$tokens): ?>
            <tr><td colspan="10" class="py-8 text-center text-slate-500">No tokens yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>

  <div class="<?= $tab === 'archive' ? '' : 'hidden' ?>">
    <section class="bg-white rounded-2xl border border-slate-200 p-5 overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-100">
          <th class="py-2">ID</th><th class="py-2">Name</th><th class="py-2">Membership</th><th class="py-2">Deleted</th>
        </tr></thead>
        <tbody>
          <?php foreach ($deleted as $d): ?>
            <tr class="border-b border-slate-50">
              <td class="py-2"><?= (int)$d['id'] ?></td>
              <td class="py-2"><?=h(trim((string)$d['firstname'] . ' ' . (string)$d['surname']))?></td>
              <td class="py-2 font-mono"><?=h((string)$d['membership_id'])?></td>
              <td class="py-2 text-xs text-slate-600"><?=h(date('d M Y H:i', strtotime((string)$d['deleted_at'])))?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$deleted): ?>
            <tr><td colspan="4" class="py-8 text-center text-slate-500">No archived rows.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>
</div>
<?php render_layout_end(); ?>
