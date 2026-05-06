<?php
require 'layout.php';
require_admin();
require_audit_access();

$pdo = db();
$notice = flash('admin_notice');

$download = strtolower(trim((string)($_GET['download'] ?? '')));
$fmt = strtolower(trim((string)($_GET['fmt'] ?? 'csv')));
if ($download === 'activity' && ($fmt === 'csv' || $fmt === 'json')) {
    $logs = $pdo->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 5000')->fetchAll(PDO::FETCH_ASSOC);
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

$logs = $pdo->query('SELECT audit_logs.*, admins.username AS actor_username FROM audit_logs LEFT JOIN admins ON admins.id = audit_logs.actor_admin_id ORDER BY audit_logs.id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
$tokens = $pdo->query('SELECT registration_tokens.*, members.membership_id FROM registration_tokens LEFT JOIN members ON members.id = registration_tokens.member_id ORDER BY registration_tokens.id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
$deleted = $pdo->query('SELECT id, membership_id, firstname, surname, phone_no, deleted_at, deleted_by_admin_id FROM members WHERE deleted_at IS NOT NULL ORDER BY datetime(deleted_at) DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
$snapCount = (int)$pdo->query('SELECT COUNT(*) AS c FROM member_audit_snapshots')->fetch()['c'];
?>
<?php render_layout_start('Audit & Logs', 'audit'); ?>
<div class="max-w-7xl mx-auto space-y-10">
  <div>
    <h1 class="text-3xl font-black text-slate-900">Audit &amp; logs</h1>
    <p class="text-slate-500 mt-1 text-sm">Activity trail, registration reference tokens, and archived member IDs. Export logs as CSV or JSON.</p>
    <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>
    <div class="mt-4 flex flex-wrap gap-2">
      <a class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-bold" href="audit.php?download=activity&amp;fmt=csv">Download activity CSV</a>
      <a class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold" href="audit.php?download=activity&amp;fmt=json">Download activity JSON</a>
    </div>
    <p class="text-xs text-slate-500 mt-2">Snapshots stored: <?=$snapCount?> · Soft-deleted rows remain queryable here.</p>
  </div>

  <section class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm overflow-x-auto">
    <h2 class="font-bold text-lg text-slate-900">Recent activity</h2>
    <table class="w-full text-sm mt-4 min-w-[720px]">
      <thead><tr class="text-left text-xs uppercase text-slate-500 border-b">
        <th class="py-2">When</th><th class="py-2">Actor</th><th class="py-2">Action</th><th class="py-2">Entity</th><th class="py-2">Details</th>
      </tr></thead>
      <tbody>
        <?php foreach ($logs as $L): ?>
          <tr class="border-b border-slate-100 align-top">
            <td class="py-2 whitespace-nowrap text-xs"><?=h(date('d M Y H:i', strtotime((string)$L['created_at'])))?></td>
            <td class="py-2 text-xs"><?=h((string)($L['actor_username'] ?? '—'))?> <span class="text-slate-400">#<?=h((string)($L['actor_admin_id'] ?? ''))?></span></td>
            <td class="py-2 font-semibold"><?=h((string)$L['action'])?></td>
            <td class="py-2 text-xs"><?=h((string)$L['entity_type'])?> #<?=h((string)($L['entity_id'] ?? ''))?></td>
            <td class="py-2 text-xs text-slate-600 max-w-md break-all"><?=h((string)($L['details_json'] ?? ''))?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
          <tr><td colspan="5" class="py-6 text-slate-500">No log entries yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm overflow-x-auto">
    <h2 class="font-bold text-lg text-slate-900">Registration tokens (5-digit)</h2>
    <table class="w-full text-sm mt-4">
      <thead><tr class="text-left text-xs uppercase text-slate-500 border-b">
        <th class="py-2">Token</th><th class="py-2">Membership</th><th class="py-2">Phone</th><th class="py-2">Created</th>
      </tr></thead>
      <tbody>
        <?php foreach ($tokens as $t): ?>
          <tr class="border-b border-slate-100">
            <td class="py-2 font-mono font-bold"><?=h((string)$t['token'])?></td>
            <td class="py-2"><?=h((string)($t['membership_id'] ?? ''))?></td>
            <td class="py-2 font-mono text-xs"><?=h((string)$t['phone_normalized'])?></td>
            <td class="py-2 text-xs"><?=h(date('d M Y H:i', strtotime((string)$t['created_at'])))?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$tokens): ?>
          <tr><td colspan="4" class="py-6 text-slate-500">No tokens yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm overflow-x-auto">
    <h2 class="font-bold text-lg text-slate-900">Archived members (soft-deleted)</h2>
    <p class="text-xs text-slate-500 mt-1">Row data and snapshots remain for compliance; list received hides these by default.</p>
    <table class="w-full text-sm mt-4">
      <thead><tr class="text-left text-xs uppercase text-slate-500 border-b">
        <th class="py-2">ID</th><th class="py-2">Name</th><th class="py-2">Membership</th><th class="py-2">Deleted</th>
      </tr></thead>
      <tbody>
        <?php foreach ($deleted as $d): ?>
          <tr class="border-b border-slate-100">
            <td class="py-2"><?= (int)$d['id'] ?></td>
            <td class="py-2"><?=h(trim((string)$d['firstname'] . ' ' . (string)$d['surname']))?></td>
            <td class="py-2 font-mono"><?=h((string)$d['membership_id'])?></td>
            <td class="py-2 text-xs"><?=h(date('d M Y H:i', strtotime((string)$d['deleted_at'])))?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$deleted): ?>
          <tr><td colspan="4" class="py-6 text-slate-500">No archived rows.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</div>
<?php render_layout_end(); ?>
