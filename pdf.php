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
    $mime = mime_content_type($fullPath) ?: '';

    // If source is already JPEG, embed directly to guarantee display even without GD.
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        return $raw;
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
        'Proposer Information' => [
            ['Proposer Name', member_value($member, 'proposer_name')],
            ['Proposer Phone', member_value($member, 'proposer_phone_no')],
            ['Proposer Party ID', member_value($member, 'proposer_party_id')],
        ],
    ];

    $pageW = 595;
    $pageH = 842;
    $margin = 42;
    $accentBlue = '0.06 0.52 0.27';
    $darkText = '0.12 0.12 0.12';
    $lightBorder = '0.84 0.88 0.86';

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
    $currentY = 780;
    $addText($textBlock, $margin, $currentY, 'F2', 20, $accentBlue, 'Membership Registration Form');
    $currentY -= 24;
    $addText($textBlock, $margin, $currentY, 'F1', 11, $darkText, 'Hon. Mavis Kuukua Bissue | Ahanta West');
    $currentY -= 18;
    $addText($textBlock, $margin, $currentY, 'F2', 10, $darkText, 'Reference Number: ' . member_value($member, 'membership_id'));
    $addText($textBlock, $margin + 250, $currentY, 'F1', 10, $darkText, 'Date Submitted: ' . pdf_date(member_value($member, 'created_at')));
    $currentY -= 10;
    $commands[] = '0.82 0.90 0.85 RG';
    $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $margin, $currentY, $pageW - $margin, $currentY);

    // Passport photo box (top edge aligned with first header line)
    $photoW = 96;
    $photoH = 112;
    $photoX = $pageW - $margin - $photoW;
    $photoTopY = 780;
    $photoY = $photoTopY - $photoH;
    $commands[] = '0.65 0.68 0.72 RG';
    $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $photoX, $photoY, $photoW, $photoH);
    if (!$hasPhoto) {
        $addText($textBlock, $photoX + 18, $photoY + ($photoH / 2) - 4, 'F1', 10, '0.35 0.35 0.35', 'No Photo');
    }

    // Form-like sections
    $lineStart = $margin;
    $lineEnd = $pageW - $margin;
    $contentWidth = $lineEnd - $lineStart;
    $rowHeight = 22;
    $wellHeight = 30;
    $wellTextPadding = 10;
    $sectionGap = 14;
    $sectionBottomPadding = 8;
    $alternateRow = false;
    $currentY = 680;
    foreach ($sections as $title => $rows) {
        // Section title well
        $wellTop = $currentY;
        $wellBottom = $wellTop - $wellHeight;
        $commands[] = '0.90 0.96 0.92 rg';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $lineStart, $wellBottom, $contentWidth, $wellHeight);
        $commands[] = '0.78 0.90 0.82 RG';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $lineStart, $wellBottom, $contentWidth, $wellHeight);
        $addText($textBlock, $lineStart + $wellTextPadding, $wellTop - $wellTextPadding - 2, 'F2', 12, $accentBlue, $title);
        $currentY = $wellBottom;

        // Section body container
        $bodyHeight = (count($rows) * $rowHeight) + $sectionBottomPadding;
        $bodyTop = $currentY;
        $bodyBottom = $bodyTop - $bodyHeight;
        $commands[] = '0.97 0.99 0.98 rg';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $lineStart, $bodyBottom, $contentWidth, $bodyHeight);
        $commands[] = '0.88 0.93 0.90 RG';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $lineStart, $bodyBottom, $contentWidth, $bodyHeight);
        foreach ($rows as [$label, $value]) {
            $rowTop = $currentY;
            $rowBottom = $rowTop - $rowHeight;
            $dividerY = $rowBottom + 5;
            $textY = $dividerY + 5;

            if ($alternateRow) {
                $commands[] = 'q';
                $commands[] = '/GS1 gs';
                $commands[] = $lightBorder . ' rg';
                $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $lineStart + 1, $rowBottom, $contentWidth - 2, $rowHeight);
                $commands[] = 'Q';
            }

            $safeValue = $value !== '' ? $value : 'N/A';
            $text = $label . ': ' . $safeValue;
            $addText($textBlock, $lineStart + 10, $textY, 'F1', 10, $darkText, $text);
            $commands[] = $lightBorder . ' RG';
            $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $lineStart + 1, $dividerY, $lineEnd - 1, $dividerY);
            $currentY = $rowBottom;
            $alternateRow = !$alternateRow;
        }
        $currentY -= $sectionBottomPadding;
        $currentY -= $sectionGap;
    }

    // Light watermark-style site footer (outside main content hierarchy)
    $addText($textBlock, $pageW - 190, 14, 'F1', 8, '0.72 0.72 0.72', 'www.kuukuacares.com');

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
    $resources = '/Font << /F1 4 0 R /F2 6 0 R >> /ExtGState << /GS1 7 0 R >>';
    if ($hasPhoto) {
        $resources .= ' /XObject << /Im1 8 0 R >>';
    }
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << $resources >> /Contents 5 0 R >> endobj\n";
    $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $objects[] = "5 0 obj << /Length " . strlen($content) . " >> stream\n$content\nendstream endobj\n";
    $objects[] = "6 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n";
    $objects[] = "7 0 obj << /Type /ExtGState /ca 0.22 /CA 1 >> endobj\n";
    if ($hasPhoto) {
        $objects[] = "8 0 obj << /Type /XObject /Subtype /Image /Width 120 /Height 140 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($photoBinary) . " >> stream\n" . $photoBinary . "\nendstream endobj\n";
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
