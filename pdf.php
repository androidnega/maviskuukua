<?php
function pdf_escape(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdf_date(string $value): string {
    $time = strtotime($value);
    return $time ? date('d M Y', $time) : $value;
}

function member_value(array $member, string $key): string {
    $value = trim((string)($member[$key] ?? ''));
    return $value !== '' ? $value : 'N/A';
}

function create_photo_jpeg_binary(array $member): ?string {
    $photoPath = trim((string)($member['photo_path'] ?? ''));
    if ($photoPath === '') {
        return null;
    }
    $fullPath = BASE_DIR . '/' . ltrim($photoPath, '/');
    if (!is_file($fullPath)) {
        return null;
    }
    $raw = @file_get_contents($fullPath);
    if ($raw === false) {
        return null;
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor')) {
        return null;
    }

    $src = @imagecreatefromstring($raw);
    if (!$src) {
        return null;
    }

    $targetW = 120;
    $targetH = 140;
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($srcW <= 0 || $srcH <= 0) {
        imagedestroy($src);
        return null;
    }

    $scale = max($targetW / $srcW, $targetH / $srcH);
    $cropW = (int)round($targetW / $scale);
    $cropH = (int)round($targetH / $scale);
    $srcX = (int)max(0, floor(($srcW - $cropW) / 2));
    $srcY = (int)max(0, floor(($srcH - $cropH) / 2));

    $target = imagecreatetruecolor($targetW, $targetH);
    imagecopyresampled($target, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $cropW, $cropH);

    ob_start();
    imagejpeg($target, null, 88);
    $jpeg = (string)ob_get_clean();

    imagedestroy($target);
    imagedestroy($src);

    return $jpeg !== '' ? $jpeg : null;
}

function create_member_pdf(array $member): string {
    $filename = 'member_' . $member['id'] . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $member['membership_id']) . '.pdf';
    $path = PDF_DIR . '/' . $filename;

    $sections = [
        'Personal Information' => [
            ['Full Name', trim(member_value($member, 'firstname') . ' ' . member_value($member, 'surname'))],
            ['Date of Birth', pdf_date(member_value($member, 'date_of_birth'))],
            ['Place of Birth', member_value($member, 'place_of_birth')],
        ],
        'Contact Information' => [
            ['Phone Number', member_value($member, 'phone_no')],
            ['Branch', member_value($member, 'branch')],
            ['Languages', member_value($member, 'languages')],
        ],
        'Identification Information' => [
            ['Voters ID No', member_value($member, 'voter_id_no')],
            ['Ghana Card No', member_value($member, 'ghana_card_no')],
            ['Membership ID', member_value($member, 'membership_id')],
        ],
        'Membership Information' => [
            ['Year Joined', member_value($member, 'year_joined')],
            ['Position Held', member_value($member, 'positions_held')],
            ['Profession', member_value($member, 'profession')],
        ],
        'Residential Address' => [
            ['Residential Address', 'N/A'],
            ['City', member_value($member, 'branch')],
        ],
        'Emergency Contact' => [
            ['Contact Name', member_value($member, 'proposer_name')],
            ['Contact Phone', member_value($member, 'proposer_phone_no')],
            ['Contact Party ID', member_value($member, 'proposer_party_id')],
        ],
    ];

    $pageW = 595;
    $pageH = 842;
    $margin = 42;
    $accentBlue = '0.08 0.20 0.78';
    $darkText = '0.12 0.12 0.12';
    $lightBorder = '0.86 0.88 0.91';

    $commands = [];
    $textBlock = [];
    $photoBinary = create_photo_jpeg_binary($member);
    $hasPhoto = $photoBinary !== null;

    $addText = static function(array &$tb, float $x, float $y, string $font, int $size, string $color, string $text): void {
        $tb[] = "$color rg";
        $tb[] = "BT";
        $tb[] = "/$font $size Tf";
        $tb[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $y);
        $tb[] = '(' . pdf_escape($text) . ') Tj';
        $tb[] = 'ET';
    };

    // Header
    $addText($textBlock, $margin + 92, 792, 'F2', 20, $accentBlue, 'Membership Registration Form');
    $addText($textBlock, $margin, 770, 'F1', 11, $darkText, 'Hon. Mavis Kuukua Bissue | Ahanta West');
    $addText($textBlock, $margin, 754, 'F2', 10, $darkText, 'Reference Number: ' . member_value($member, 'membership_id'));
    $addText($textBlock, $margin + 260, 754, 'F1', 10, $darkText, 'Date Submitted: ' . pdf_date(member_value($member, 'created_at')));

    // Passport photo box
    $photoW = 96;
    $photoH = 112;
    $photoX = $pageW - $margin - $photoW;
    $photoY = 708;
    $commands[] = '0.65 0.68 0.72 RG';
    $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $photoX, $photoY, $photoW, $photoH);
    if (!$hasPhoto) {
        $addText($textBlock, $photoX + 18, $photoY + ($photoH / 2) - 4, 'F1', 10, '0.35 0.35 0.35', 'No Photo');
    }

    // Form-like sections
    $y = 686;
    $lineEnd = $pageW - $margin;
    $sectionGap = 13;
    foreach ($sections as $title => $rows) {
        $addText($textBlock, $margin, $y, 'F2', 11, $darkText, $title);
        $y -= 8;
        foreach ($rows as [$label, $value]) {
            $y -= 16;
            $safeValue = $value !== '' ? $value : 'N/A';
            $text = $label . ': ' . $safeValue;
            $addText($textBlock, $margin + 8, $y + 2, 'F1', 10, $darkText, $text);
            $commands[] = $lightBorder . ' RG';
            $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $margin + 6, $y - 3, $lineEnd, $y - 3);
        }
        $y -= $sectionGap;
    }

    // Declaration and footer
    $addText($textBlock, $margin, $y, 'F2', 11, $accentBlue, 'Declaration');
    $y -= 16;
    $addText($textBlock, $margin, $y, 'F1', 10, $darkText, 'I confirm that the information provided above is true and correct.');
    $y -= 22;
    $addText($textBlock, $margin, $y, 'F1', 10, $darkText, 'Applicant Signature: ______________________');
    $addText($textBlock, $margin + 300, $y, 'F1', 10, $darkText, 'Date: ' . pdf_date(member_value($member, 'created_at')));
    $addText($textBlock, $margin, 22, 'F1', 9, '0.45 0.45 0.45', 'Generated Registration PDF');

    if ($hasPhoto) {
        $commands[] = 'q';
        $commands[] = sprintf('%.2f 0 0 %.2f %.2f %.2f cm', $photoW, $photoH, $photoX, $photoY);
        $commands[] = '/Im1 Do';
        $commands[] = 'Q';
    }

    $content = implode("\n", array_merge($commands, $textBlock));

    $objects = [];
    $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $resources = '/Font << /F1 4 0 R /F2 6 0 R >>';
    if ($hasPhoto) {
        $resources .= ' /XObject << /Im1 7 0 R >>';
    }
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << $resources >> /Contents 5 0 R >> endobj\n";
    $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $objects[] = "5 0 obj << /Length " . strlen($content) . " >> stream\n$content\nendstream endobj\n";
    $objects[] = "6 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n";
    if ($hasPhoto) {
        $objects[] = "7 0 obj << /Type /XObject /Subtype /Image /Width 120 /Height 140 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($photoBinary) . " >> stream\n" . $photoBinary . "\nendstream endobj\n";
    }

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
