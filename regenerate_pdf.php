<?php
require 'config.php';
require 'pdf.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('received_list.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('admin_notice', 'Invalid member selected.');
    redirect('received_list.php');
}

try {
    $stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
    $stmt->execute([$id]);
    $member = $stmt->fetch();

    if (!$member) {
        flash('admin_notice', 'Member not found.');
        redirect('received_list.php');
    }

    $pdfOverrides = load_member_pdf_payload($id);
    if (isset($_SESSION['pdf_overrides'][$id]) && is_array($_SESSION['pdf_overrides'][$id])) {
        $pdfOverrides = array_merge($pdfOverrides, $_SESSION['pdf_overrides'][$id]);
    }
    $filename = create_member_pdf($member, $pdfOverrides);
    $update = db()->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
    $update->execute([$filename, $id]);
    flash('admin_notice', 'PDF regenerated successfully.');
} catch (Throwable $e) {
    flash('admin_notice', 'Could not regenerate PDF. Please try again.');
}

redirect('received_list.php');
