<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/contact_lib.php';

require_admin();
$pdo = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        flash('admin_notice', 'Message not found.');
        redirect('admin_contact_messages.php');
    }
    $pdo->prepare('UPDATE contact_messages SET read_at = COALESCE(read_at, ?) WHERE id = ?')->execute([date('c'), $id]);
    $row['read_at'] = $row['read_at'] ?? date('c');

    render_layout_start('Contact message', 'contact_inbox');
    ?>
<div class="w-full max-w-3xl">
  <a href="admin_contact_messages.php" class="text-sm font-semibold text-slate-600 hover:text-slate-900"><i class="fa-solid fa-arrow-left"></i> All messages</a>
  <div class="mt-4 bg-white border border-slate-200 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-black text-slate-900"><?= h((string) ($row['subject'] ?? '')) ?></h1>
        <p class="mt-2 text-sm text-slate-500"><?= h((string) ($row['created_at'] ?? '')) ?></p>
      </div>
      <?php if (empty($row['read_at'])): ?>
        <span class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-900">Unread</span>
      <?php else: ?>
        <span class="shrink-0 text-xs text-slate-400">Read</span>
      <?php endif; ?>
    </div>
    <div class="mt-6 border-t border-slate-100 pt-6">
      <p class="text-sm font-bold text-slate-700"><?= h((string) ($row['full_name'] ?? '')) ?></p>
      <p class="mt-1 text-sm">
        <a href="mailto:<?= h((string) ($row['email'] ?? '')) ?>" class="text-emerald-700 font-semibold hover:underline"><?= h((string) ($row['email'] ?? '')) ?></a>
      </p>
      <?php if (!empty($row['ip'])): ?>
        <p class="mt-2 text-xs text-slate-400">IP: <?= h((string) $row['ip']) ?></p>
      <?php endif; ?>
    </div>
    <div class="mt-6 whitespace-pre-wrap text-sm leading-relaxed text-slate-800"><?= h((string) ($row['body'] ?? '')) ?></div>
  </div>
</div>
    <?php
    render_layout_end();

    exit;
}

$rows = $pdo->query('SELECT id, full_name, email, subject, created_at, read_at FROM contact_messages ORDER BY datetime(created_at) DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
$notice = flash('admin_notice');

render_layout_start('Contact messages', 'contact_inbox');
?>
<div class="w-full max-w-6xl">
  <h1 class="text-2xl font-black text-slate-900">Contact messages</h1>
  <p class="text-sm text-slate-500 mt-1">Submissions from the public contact form.</p>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <div class="mt-8 bg-white border border-slate-200 overflow-x-auto">
    <?php if (count($rows) === 0): ?>
      <p class="p-8 text-center text-slate-500 text-sm">No messages yet.</p>
    <?php else: ?>
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-left">
          <tr>
            <th class="px-4 py-3 font-bold text-slate-700 w-28">Status</th>
            <th class="px-4 py-3 font-bold text-slate-700">From</th>
            <th class="px-4 py-3 font-bold text-slate-700">Subject</th>
            <th class="px-4 py-3 font-bold text-slate-700 whitespace-nowrap">Received</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-b border-slate-100 hover:bg-slate-50/80">
              <td class="px-4 py-3">
                <?php if (empty($r['read_at'])): ?>
                  <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-900">New</span>
                <?php else: ?>
                  <span class="text-slate-400 text-xs">Read</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <a href="admin_contact_messages.php?id=<?= (int) $r['id'] ?>" class="font-semibold text-slate-900 hover:text-emerald-700"><?= h((string) ($r['full_name'] ?? '')) ?></a>
                <p class="text-xs text-slate-500"><?= h((string) ($r['email'] ?? '')) ?></p>
              </td>
              <td class="px-4 py-3 text-slate-700 max-w-md truncate"><?= h((string) ($r['subject'] ?? '')) ?></td>
              <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-xs"><?= h((string) ($r['created_at'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php render_layout_end(); ?>
