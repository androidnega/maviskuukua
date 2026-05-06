<?php
require 'config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('received_list.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('admin_notice', 'Invalid member selected for deletion.');
    redirect('received_list.php');
}

try {
    $stmt = db()->prepare('SELECT pdf_path, photo_path FROM members WHERE id = ?');
    $stmt->execute([$id]);
    $member = $stmt->fetch();

    if (!$member) {
        flash('admin_notice', 'Member not found.');
        redirect('received_list.php');
    }

    if (!empty($member['pdf_path'])) {
        $pdfFile = PDF_DIR . '/' . basename((string)$member['pdf_path']);
        if (is_file($pdfFile)) {
            @unlink($pdfFile);
        }
    }

    if (!empty($member['photo_path'])) {
        $photoFile = BASE_DIR . '/' . ltrim((string)$member['photo_path'], '/');
        if (is_file($photoFile)) {
            @unlink($photoFile);
        }
    }

    $delete = db()->prepare('DELETE FROM members WHERE id = ?');
    $delete->execute([$id]);
    flash('admin_notice', 'Member and related files deleted successfully.');
} catch (Throwable $e) {
    flash('admin_notice', 'Delete failed. Please try again.');
}

redirect('received_list.php');
