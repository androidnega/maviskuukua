<?php
function pdf_escape(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function create_member_pdf(array $member): string {
    $filename = 'member_' . $member['id'] . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $member['membership_id']) . '.pdf';
    $path = PDF_DIR . '/' . $filename;
    $lines = [
        'Mavis Kuukua Bissue Membership Registration',
        'Member of Parliament, Ahanta West',
        '',
        'Membership ID: ' . $member['membership_id'],
        'Name: ' . trim($member['first_name'] . ' ' . ($member['other_names'] ?? '') . ' ' . $member['last_name']),
        'Gender: ' . $member['gender'],
        'Date of Birth: ' . $member['date_of_birth'],
        'Phone: ' . $member['phone'],
        'Email: ' . ($member['email'] ?: 'N/A'),
        'Community: ' . $member['community'],
        'Electoral Area: ' . ($member['electoral_area'] ?: 'N/A'),
        'Voter ID: ' . $member['voter_id'],
        'Ghana Card: ' . ($member['ghana_card'] ?: 'N/A'),
        'Occupation: ' . ($member['occupation'] ?: 'N/A'),
        'Submitted At: ' . $member['created_at'],
    ];

    $content = "BT\n/F1 16 Tf\n50 790 Td\n";
    foreach ($lines as $i => $line) {
        $size = $i === 0 ? 16 : 11;
        if ($i === 1) $size = 12;
        $content .= "/F1 {$size} Tf\n(" . pdf_escape($line) . ") Tj\n0 -24 Td\n";
    }
    $content .= "ET";

    $objects = [];
    $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n";
    $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $objects[] = "5 0 obj << /Length " . strlen($content) . " >> stream\n$content\nendstream endobj\n";

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
    if (@file_put_contents($path, $pdf) === false) {
        throw new RuntimeException('Failed to write PDF file.');
    }
    return $filename;
}
