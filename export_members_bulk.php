<?php
require 'config.php';
require_admin();

if (!can_export_bulk_members()) {
    flash('admin_notice', 'You do not have access to bulk export.');
    redirect('admin.php');
}

$pdo = db();
$active = members_active_clause();

$format = strtolower(trim((string)($_GET['format'] ?? 'csv')));
$allowed = ['csv', 'excel', 'pdf'];
if (!in_array($format, $allowed, true)) {
    http_response_code(400);
    exit('Invalid export format.');
}

$rows = $pdo->query("SELECT * FROM members WHERE $active ORDER BY datetime(created_at) DESC")->fetchAll(PDO::FETCH_ASSOC);
log_admin_action($pdo, 'bulk_export_members', 'members', null, ['format' => $format, 'rows' => count($rows)]);

$cols = [
    'id', 'firstname', 'surname', 'place_of_birth', 'date_of_birth', 'branch', 'phone_no', 'year_joined',
    'voter_id_no', 'ghana_card_no', 'positions_held', 'languages', 'profession',
    'proposer_name', 'proposer_party_id', 'proposer_phone_no', 'membership_id',
    'photo_path', 'pdf_path', 'created_at', 'viewed_at',
];

$now = date('Ymd_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="members_export_' . $now . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $cols);
    foreach ($rows as $r) {
        $line = [];
        foreach ($cols as $c) {
            $line[] = (string)($r[$c] ?? '');
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="members_export_' . $now . '.xls"');
    echo '<table border="1"><tr>';
    foreach ($cols as $c) {
        echo '<th>' . h($c) . '</th>';
    }
    echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($cols as $c) {
            echo '<td>' . h((string)($r[$c] ?? '')) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

function pdf_escape_bulk(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

$pageW = 595;
$pageH = 842;
$margin = 42;
$lineHeight = 14;
$maxLinesPerPage = 48;
$pages = [];
$currentLines = [];
$currentLines[] = 'MEMBERSHIP EXPORT (ACTIVE)';
$currentLines[] = 'Generated: ' . date('d M Y H:i');
$currentLines[] = '';
$currentLines[] = 'Name | Membership ID | Phone | Branch | Submitted';
$currentLines[] = str_repeat('-', 115);
foreach ($rows as $r) {
    $line = trim((string)($r['firstname'] ?? '') . ' ' . (string)($r['surname'] ?? ''));
    $line .= ' | ' . (string)($r['membership_id'] ?? '');
    $line .= ' | ' . (string)($r['phone_no'] ?? '');
    $line .= ' | ' . (string)($r['branch'] ?? '');
    $line .= ' | ' . date('d M Y H:i', strtotime((string)($r['created_at'] ?? '')));
    if (strlen($line) > 155) {
        $line = substr($line, 0, 152) . '...';
    }
    $currentLines[] = $line;
    if (count($currentLines) >= $maxLinesPerPage) {
        $pages[] = $currentLines;
        $currentLines = [];
    }
}
if ($currentLines) {
    $pages[] = $currentLines;
}
if (!$pages) {
    $pages[] = ['MEMBERSHIP EXPORT', 'No active records found.'];
}

$objects = [];
$objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
$kids = [];
$objIndex = 3;
foreach ($pages as $lines) {
    $pageObj = $objIndex++;
    $contentObj = $objIndex++;
    $kids[] = $pageObj . ' 0 R';

    $contentLines = [];
    $y = $pageH - $margin;
    foreach ($lines as $line) {
        $contentLines[] = '0.12 0.12 0.12 rg';
        $contentLines[] = 'BT';
        $contentLines[] = '/F1 9 Tf';
        $contentLines[] = sprintf('1 0 0 1 %.2f %.2f Tm', $margin, $y);
        $contentLines[] = '(' . pdf_escape_bulk($line) . ') Tj';
        $contentLines[] = 'ET';
        $y -= $lineHeight;
    }
    $content = implode("\n", $contentLines);
    $objects[] = $pageObj . " 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << /Font << /F1 4 0 R >> >> /Contents $contentObj 0 R >> endobj\n";
    $objects[] = $contentObj . " 0 obj << /Length " . strlen($content) . " >> stream\n$content\nendstream endobj\n";
}
$objects[] = "2 0 obj << /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($kids) . " >> endobj\n";
$objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $obj) {
    $offsets[] = strlen($pdf);
    $pdf .= $obj;
}
$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) {
    $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}
$pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="members_export_' . $now . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
