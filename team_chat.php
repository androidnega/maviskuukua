<?php
require 'layout.php';
require_admin();

if (!can_access_team_chat()) {
    flash('admin_notice', 'You do not have access to team chat.');
    redirect('admin.php');
}

$pdo = db();
$notice = flash('admin_notice');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim((string)($_POST['body'] ?? ''));
    if ($body !== '') {
        $pdo->prepare('INSERT INTO chat_messages (admin_id, body, created_at) VALUES (?,?,?)')->execute([(int)$_SESSION['admin_id'], $body, date('c')]);
        log_admin_action($pdo, 'chat_post', 'chat', null, ['bytes' => strlen($body)]);
    }
    redirect('team_chat.php');
}

$stmt = $pdo->query('SELECT chat_messages.id, chat_messages.body, chat_messages.created_at, admins.username AS author FROM chat_messages INNER JOIN admins ON admins.id = chat_messages.admin_id ORDER BY chat_messages.id DESC LIMIT 150');
$rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
<?php render_layout_start('Team Chat', 'team_chat'); ?>
<div class="max-w-3xl mx-auto">
  <h1 class="text-3xl font-black text-slate-900">Team Chat</h1>
  <p class="text-slate-500 mt-1 text-sm">Coordinators and super admins only. Messages are retained in the database.</p>
  <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <form method="post" class="mt-6 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
    <label class="block text-sm font-semibold text-slate-700">New message</label>
    <textarea name="body" rows="3" class="w-full rounded-xl border border-slate-200 p-3 text-sm" placeholder="Share an update with the team…" required></textarea>
    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-500">Send</button>
  </form>

  <div class="mt-8 space-y-3">
    <?php foreach ($rows as $row): ?>
      <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
          <span class="font-bold text-slate-800"><?=h((string)$row['author'])?></span>
          <time datetime="<?=h((string)$row['created_at'])?>"><?=h(date('d M Y H:i', strtotime((string)$row['created_at'])))?></time>
        </div>
        <p class="mt-2 text-slate-700 whitespace-pre-wrap"><?=h((string)$row['body'])?></p>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <p class="text-slate-500 text-sm">No messages yet.</p>
    <?php endif; ?>
  </div>
</div>
<?php render_layout_end(); ?>
