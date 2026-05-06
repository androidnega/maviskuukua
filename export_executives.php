<?php
require 'config.php';
require_admin();

$format = strtolower(trim((string)($_GET['format'] ?? 'csv')));
$allowed = ['csv', 'excel', 'pdf'];
if (!in_array($format, $allowed, true)) {
    http_response_code(400);
    exit('Invalid export format.');
}

$rows = db()->query("SELECT firstname, surname, phone_no, branch, voter_id_no, membership_id, positions_held, created_at FROM members ORDER BY firstname ASC, surname ASC")->fetchAll();
$now = date('Ymd_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="branch_executives_' . $now . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Contact', 'Branch', 'Voter ID', 'Membership ID', 'Position Held', 'Submitted At']);
    foreach ($rows as $r) {
        fputcsv($out, [
            trim((string)$r['firstname'] . ' ' . (string)$r['surname']),
            (string)$r['phone_no'],
            (string)$r['branch'],
            (string)$r['voter_id_no'],
            (string)$r['membership_id'],
            (string)$r['positions_held'],
            date('d M Y H:i', strtotime((string)$r['created_at']))
        ]);
    }
    fclose($out);
    exit;
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="branch_executives_' . $now . '.xls"');
    echo '<table border="1">';
    echo '<tr><th>Name</th><th>Contact</th><th>Branch</th><th>Voter ID</th><th>Membership ID</th><th>Position Held</th><th>Submitted At</th></tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . h(trim((string)$r['firstname'] . ' ' . (string)$r['surname'])) . '</td>';
        echo '<td>' . h((string)$r['phone_no']) . '</td>';
        echo '<td>' . h((string)$r['branch']) . '</td>';
        echo '<td>' . h((string)$r['voter_id_no']) . '</td>';
        echo '<td>' . h((string)$r['membership_id']) . '</td>';
        echo '<td>' . h((string)$r['positions_held']) . '</td>';
        echo '<td>' . h(date('d M Y H:i', strtotime((string)$r['created_at']))) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

function pdf_escape_export(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

// simple multi-page text PDF export
$pageW = 595;
$pageH = 842;
$margin = 42;
$lineHeight = 16;
$maxLinesPerPage = 44;
$pages = [];
$currentLines = [];
$currentLines[] = 'BRANCH EXECUTIVE EXPORT';
$currentLines[] = 'Generated: ' . date('d M Y H:i');
$currentLines[] = '';
$currentLines[] = 'Name | Contact | Branch | Voter ID | Membership ID';
$currentLines[] = str_repeat('-', 105);
foreach ($rows as $r) {
    $line = trim((string)$r['firstname'] . ' ' . (string)$r['surname']);
    $line .= ' | ' . (string)$r['phone_no'];
    $line .= ' | ' . (string)$r['branch'];
    $line .= ' | ' . (string)$r['voter_id_no'];
    $line .= ' | ' . (string)$r['membership_id'];
    if (strlen($line) > 150) {
        $line = substr($line, 0, 147) . '...';
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
    $pages[] = ['BRANCH EXECUTIVE EXPORT', 'No records found.'];
}

$objects = [];
$objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
$kids = [];
$objIndex = 3;
foreach ($pages as $idx => $lines) {
    $pageObj = $objIndex++;
    $contentObj = $objIndex++;
    $kids[] = $pageObj . ' 0 R';

    $contentLines = [];
    $y = $pageH - $margin;
    foreach ($lines as $line) {
        $contentLines[] = '0.12 0.12 0.12 rg';
        $contentLines[] = 'BT';
        $contentLines[] = '/F1 10 Tf';
        $contentLines[] = sprintf('1 0 0 1 %.2f %.2f Tm', $margin, $y);
        $contentLines[] = '(' . pdf_escape_export($line) . ') Tj';
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
header('Content-Disposition: attachment; filename="branch_executives_' . $now . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
