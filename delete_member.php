<?php
require 'config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('received_list.php');
}

if (!can_delete_members()) {
    flash('admin_notice', 'You do not have permission to delete records.');
    redirect('received_list.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('admin_notice', 'Invalid member selected for deletion.');
    redirect('received_list.php');
}

$pdo = db();

try {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $member = $stmt->fetch();

    if (!$member) {
        flash('admin_notice', 'Member not found or already removed.');
        redirect('received_list.php');
    }

    $snapshot = json_encode($member, JSON_UNESCAPED_UNICODE);
    $insSnap = $pdo->prepare('INSERT INTO member_audit_snapshots (member_id, snapshot_json, reason, actor_admin_id, created_at) VALUES (?,?,?,?,?)');
    $insSnap->execute([$id, $snapshot, 'soft_delete', (int)$_SESSION['admin_id'], date('c')]);

    $del = $pdo->prepare('UPDATE members SET deleted_at = ?, deleted_by_admin_id = ? WHERE id = ? AND deleted_at IS NULL');
    $del->execute([date('c'), (int)$_SESSION['admin_id'], $id]);

    log_admin_action($pdo, 'member_soft_delete', 'member', $id, ['membership_id' => $member['membership_id'] ?? '']);

    flash('admin_notice', 'Member record archived (soft delete). Files are retained for audit.');
} catch (Throwable $e) {
    flash('admin_notice', 'Delete failed. Please try again.');
}

redirect('received_list.php');
