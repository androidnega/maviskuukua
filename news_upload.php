<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/news_lib.php';

require_admin();
require_news_management();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

    exit;
}

$imagesOnly = isset($_GET['images_only']) && $_GET['images_only'] === '1';
$file = $_FILES['file'] ?? null;
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);

    exit;
}

$maxBytes = $imagesOnly ? 12 * 1024 * 1024 : 48 * 1024 * 1024;
if ((int) ($file['size'] ?? 0) > $maxBytes) {
    http_response_code(400);
    echo json_encode(['error' => 'File is too large.']);

    exit;
}

$name = (string) ($file['name'] ?? '');
$allowed = news_upload_allowed($name, $imagesOnly);
if ($allowed === null) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed.']);

    exit;
}
[$ext, $expectedMime] = $allowed;

$tmp = (string) ($file['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid upload.']);

    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$detected = $finfo->file($tmp);
$okMimes = [$expectedMime];
if ($ext === 'mp4') {
    $okMimes[] = 'video/mp4';
}
if ($ext === 'm4a') {
    $okMimes[] = 'audio/x-m4a';
    $okMimes[] = 'audio/m4a';
    $okMimes[] = 'audio/mp4';
}
if ($ext === 'ogg') {
    $okMimes[] = 'audio/ogg';
    $okMimes[] = 'video/ogg';
    $okMimes[] = 'application/ogg';
}
if ($ext === 'webm') {
    $okMimes[] = 'video/webm';
    $okMimes[] = 'audio/webm';
}
if ($ext === 'wav') {
    $okMimes[] = 'audio/wav';
    $okMimes[] = 'audio/x-wav';
}
if ($detected === false || !in_array($detected, $okMimes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'File content does not match allowed type.']);

    exit;
}

$base = bin2hex(random_bytes(12)) . '.' . $ext;
$destFs = NEWS_DIR . '/uploads/' . $base;
$destRel = 'storage/news/uploads/' . $base;

if (!move_uploaded_file($tmp, $destFs)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save file.']);

    exit;
}

echo json_encode(['location' => $destRel]);
