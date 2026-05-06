<?php
require 'config.php';
require 'pdf.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('PDF not found.');
}

$stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    http_response_code(404);
    exit('PDF not found.');
}

try {
    $pdfOverrides = load_member_pdf_payload($id);
    if (isset($_SESSION['pdf_overrides'][$id]) && is_array($_SESSION['pdf_overrides'][$id])) {
        $pdfOverrides = array_merge($pdfOverrides, $_SESSION['pdf_overrides'][$id]);
    }
    $existingPdf = trim((string)($member['pdf_path'] ?? ''));
    $filePath = $existingPdf !== '' ? PDF_DIR . '/' . $existingPdf : '';
    if ($pdfOverrides || $existingPdf === '' || !is_file($filePath)) {
        $filename = create_member_pdf($member, $pdfOverrides);
        $filePath = PDF_DIR . '/' . $filename;
        $update = db()->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
        $update->execute([$filename, $id]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit('PDF generation failed. Please retry or contact admin.');
}

if (!is_file($filePath)) {
    http_response_code(500);
    exit('PDF generation failed. Please retry or contact admin.');
}

$download = isset($_GET['download']) && $_GET['download'] === '1';
$safeMembershipId = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$member['membership_id']);
$downloadName = 'member_' . $id . '_' . $safeMembershipId . '.pdf';

header('Content-Type: application/pdf');
header('Content-Length: ' . (string)filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $downloadName . '"');
readfile($filePath);
exit;
