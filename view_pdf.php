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

if (!$member || !$member['pdf_path']) {
    http_response_code(404);
    exit('PDF not found.');
}

$filename = basename((string)$member['pdf_path']);
$filePath = PDF_DIR . '/' . $filename;
if (!is_file($filePath)) {
    $filename = create_member_pdf($member);
    $filePath = PDF_DIR . '/' . $filename;
    $update = db()->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
    $update->execute([$filename, $id]);

    if (!is_file($filePath)) {
        http_response_code(404);
        exit('PDF not found.');
    }
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
