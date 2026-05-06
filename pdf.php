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

function pdf_overrides_dir(): string {
    $dir = STORAGE_DIR . '/pdf_overrides';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function pdf_overrides_path(int $memberId): string {
    return pdf_overrides_dir() . '/member_' . $memberId . '.json';
}

function save_member_pdf_payload(int $memberId, array $payload): void {
    $safe = [
        'firstname' => trim((string)($payload['firstname'] ?? '')),
        'surname' => trim((string)($payload['surname'] ?? '')),
        'place_of_birth' => trim((string)($payload['place_of_birth'] ?? '')),
        'date_of_birth' => trim((string)($payload['date_of_birth'] ?? '')),
        'branch' => trim((string)($payload['branch'] ?? '')),
        'phone_no' => trim((string)($payload['phone_no'] ?? '')),
        'year_joined' => trim((string)($payload['year_joined'] ?? '')),
        'voter_id_no' => trim((string)($payload['voter_id_no'] ?? '')),
        'ghana_card_no' => trim((string)($payload['ghana_card_no'] ?? '')),
        'positions_held' => trim((string)($payload['positions_held'] ?? '')),
        'languages' => trim((string)($payload['languages'] ?? '')),
        'profession' => trim((string)($payload['profession'] ?? '')),
        'proposer_name' => trim((string)($payload['proposer_name'] ?? '')),
        'proposer_party_id' => trim((string)($payload['proposer_party_id'] ?? '')),
        'proposer_phone_no' => trim((string)($payload['proposer_phone_no'] ?? '')),
        'membership_id' => trim((string)($payload['membership_id'] ?? '')),
        'created_at' => trim((string)($payload['created_at'] ?? '')),
    ];
    @file_put_contents(pdf_overrides_path($memberId), json_encode($safe, JSON_UNESCAPED_UNICODE));
}

/**
 * Read width/height from JPEG binary (SOF0/SOF1/SOF2). Required: PDF /Width and /Height must match DCT stream.
 *
 * @return array{0:int,1:int}|null
 */
function jpeg_binary_dimensions(string $jpeg): ?array {
    $len = strlen($jpeg);
    if ($len < 10 || $jpeg[0] !== "\xFF" || $jpeg[1] !== "\xD8") {
        return null;
    }
    $i = 2;
    while ($i < $len - 1) {
        if ($jpeg[$i] !== "\xFF") {
            $i++;
            continue;
        }
        $marker = ord($jpeg[$i + 1]);
        $i += 2;
        if ($marker === 0xD8 || $marker === 0xD9) {
            continue;
        }
        if ($marker === 0xDA) {
            break;
        }
        if ($i + 2 > $len) {
            return null;
        }
        $segLen = (ord($jpeg[$i]) << 8) | ord($jpeg[$i + 1]);
        if ($segLen < 2 || $i + $segLen > $len) {
            return null;
        }
        if ($marker === 0xC0 || $marker === 0xC1 || $marker === 0xC2) {
            if ($segLen < 9) {
                return null;
            }
            $h = (ord($jpeg[$i + 3]) << 8) | ord($jpeg[$i + 4]);
            $w = (ord($jpeg[$i + 5]) << 8) | ord($jpeg[$i + 6]);
            if ($w > 0 && $h > 0) {
                return [$w, $h];
            }
            return null;
        }
        $i += $segLen;
    }
    return null;
}

function load_member_pdf_payload(int $memberId): array {
    $path = pdf_overrides_path($memberId);
    if (!is_file($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function create_photo_jpeg_binary(array $member): ?string {
    $photoPath = trim((string)($member['photo_path'] ?? ''));
    $fullPath = '';
    if ($photoPath !== '') {
        $candidate = BASE_DIR . '/' . ltrim($photoPath, '/');
        if (is_file($candidate)) {
            $fullPath = $candidate;
        }
    }
    // Backward-compatible fallback for old records that used predictable naming.
    if ($fullPath === '' && !empty($member['id'])) {
        $legacy = PHOTO_DIR . '/photo_' . (int)$member['id'] . '.jpg';
        if (is_file($legacy)) {
            $fullPath = $legacy;
        }
    }
    if ($fullPath === '') {
        return null;
    }
    $raw = @file_get_contents($fullPath);
    if ($raw === false) {
        return null;
    }
    $mime = (string)(mime_content_type($fullPath) ?: '');
    $ext = strtolower((string)pathinfo($fullPath, PATHINFO_EXTENSION));

    // If source is already JPEG, embed directly to guarantee display even without GD.
    if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $ext === 'jpg' || $ext === 'jpeg') {
        return $raw;
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor')) {
        return null;
    }

    $src = @imagecreatefromstring($raw);
    if (!$src && $ext === 'png' && function_exists('imagecreatefrompng')) {
        $src = @imagecreatefrompng($fullPath);
    }
    if (!$src && $ext === 'webp' && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($fullPath);
    }
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

    // Fit image within passport frame (no over-zoom crop), keep full face visible.
    $scale = min($targetW / $srcW, $targetH / $srcH);
    $drawW = max(1, (int)round($srcW * $scale));
    $drawH = max(1, (int)round($srcH * $scale));
    $dstX = (int)floor(($targetW - $drawW) / 2);
    $dstY = (int)floor(($targetH - $drawH) / 2);
    $target = imagecreatetruecolor($targetW, $targetH);
    $white = imagecolorallocate($target, 255, 255, 255);
    imagefilledrectangle($target, 0, 0, $targetW, $targetH, $white);
    imagecopyresampled($target, $src, $dstX, $dstY, 0, 0, $drawW, $drawH, $srcW, $srcH);

    ob_start();
    imagejpeg($target, null, 88);
    $jpeg = (string)ob_get_clean();

    imagedestroy($target);
    imagedestroy($src);

    return $jpeg !== '' ? $jpeg : null;
}

function create_member_pdf(array $member, array $pdfOverrides = []): string {
    $pdfMember = array_merge($member, $pdfOverrides);
    $filename = 'member_' . $member['id'] . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $member['membership_id']) . '.pdf';
    $path = PDF_DIR . '/' . $filename;

    $pageW = 595;
    $pageH = 842;
    $margin = 28;
    $contentW = $pageW - (2 * $margin);
    $brandBlue = '0.08 0.45 0.24';
    $mutedText = '0.22 0.27 0.34';
    $lineColor = '0.79 0.88 0.82';

    $commands = [];
    $textBlock = [];
    $photoBinary = create_photo_jpeg_binary($member);
    $hasPhoto = $photoBinary !== null;
    $jpegW = 120;
    $jpegH = 140;
    if ($hasPhoto) {
        $dims = jpeg_binary_dimensions($photoBinary);
        if ($dims !== null) {
            [$jpegW, $jpegH] = $dims;
        } elseif (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($photoBinary);
            if (is_array($info) && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0) {
                $jpegW = (int)$info[0];
                $jpegH = (int)$info[1];
            }
        }
    }

    $addText = static function(array &$tb, float $x, float $y, string $font, int $size, string $color, string $text): void {
        $tb[] = "$color rg";
        $tb[] = "BT";
        $tb[] = "/$font $size Tf";
        $tb[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $y);
        $tb[] = '(' . pdf_escape($text) . ') Tj';
        $tb[] = 'ET';
    };

    $drawSection = static function(
        array &$commands,
        array &$textBlock,
        callable $addText,
        float $x,
        float $topY,
        float $width,
        float $height,
        string $title,
        array $rows
    ) use ($brandBlue, $mutedText, $lineColor): void {
        $headerH = 26.0;
        $bodyTop = $topY - $headerH;
        $bodyBottom = $topY - $height;

        $commands[] = '1 1 1 rg';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $x, $bodyBottom, $width, $height);
        $commands[] = $lineColor . ' RG';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $x, $bodyBottom, $width, $height);
        $commands[] = '0.91 0.97 0.93 rg';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $x, $bodyTop, $width, $headerH);
        $commands[] = '0.66 0.82 0.70 RG';
        $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $x, $bodyTop, $width, $headerH);

        $addText($textBlock, $x + 10, $topY - 18, 'F2', 11, $brandBlue, strtoupper($title));

        $rowY = $bodyTop - 14;
        $rowLineGap = 19;
        foreach ($rows as [$label, $value]) {
            $safe = $value !== '' ? $value : 'N/A';
            $addText($textBlock, $x + 8, $rowY, 'F2', 9, $mutedText, $label . ':');
            $addText($textBlock, $x + 118, $rowY, 'F1', 9, $mutedText, $safe);
            $commands[] = '0.88 0.93 0.89 RG';
            $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $x + 6, $rowY - 5, $x + $width - 6, $rowY - 5);
            $rowY -= $rowLineGap;
        }
    };

    // Page background
    $commands[] = '1 1 1 rg';
    $commands[] = sprintf('0 0 %.2f %.2f re f', $pageW, $pageH);

    // Header — photo slot from page geometry (width fraction × portrait factor tied to page aspect).
    $photoFrameW = $contentW * 0.24;
    $pageAspect = $pageH / max($pageW, 1.0);
    $slotPortrait = min(max(1.06, $pageAspect * 0.42), 1.38);
    $photoFrameH = $photoFrameW * $slotPortrait;
    $photoX = $pageW - $margin - $photoFrameW;
    $photoTopY = $pageH - $margin - 12;
    $photoY = $photoTopY - $photoFrameH;

    $titleX = $margin;
    $headerRuleEndX = max($titleX + $contentW * 0.62, $photoX - 10);

    $addText($textBlock, $titleX, 774, 'F2', 21, $brandBlue, 'Membership Registration Form');
    $addText($textBlock, $titleX, 748, 'F1', 11, $mutedText, 'Hon. Mavis Kuukua Bissue | Ahanta West');
    $commands[] = '0.69 0.83 0.73 RG';
    $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $titleX, 738, $headerRuleEndX, 738);
    $addText($textBlock, $titleX, 713, 'F2', 10, $brandBlue, 'Reference No.: ' . member_value($pdfMember, 'membership_id'));
    $addText($textBlock, $titleX, 692, 'F2', 10, $brandBlue, 'Date Submitted: ' . pdf_date(member_value($pdfMember, 'created_at')));

    // Photo frame (letterboxed image drawn inside with uniform scale — see below).
    $commands[] = '1 1 1 rg';
    $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $photoX, $photoY, $photoFrameW, $photoFrameH);
    $commands[] = '0.47 0.70 0.54 RG';
    $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $photoX, $photoY, $photoFrameW, $photoFrameH);
    if (!$hasPhoto) {
        $addText($textBlock, $photoX + ($photoFrameW / 2) - 22, $photoY + ($photoFrameH / 2) - 4, 'F2', 10, '0.42 0.42 0.42', 'No Photo');
    }

    $drawW = $photoFrameW;
    $drawH = $photoFrameH;
    $drawX = $photoX;
    $drawY = $photoY;
    if ($hasPhoto && $jpegW > 0 && $jpegH > 0) {
        $scale = min($photoFrameW / $jpegW, $photoFrameH / $jpegH);
        $drawW = $jpegW * $scale;
        $drawH = $jpegH * $scale;
        $drawX = $photoX + ($photoFrameW - $drawW) / 2;
        $drawY = $photoY + ($photoFrameH - $drawH) / 2;
    }

    // Body cards — vertical positions follow photo frame (no fixed row Y).
    $colGap = 14;
    $colW = ($contentW - $colGap) / 2;
    $leftX = $margin;
    $rightX = $margin + $colW + $colGap;
    $cardH = min(172.0, max(140.0, ($photoY - 2 * $margin) * 0.28));
    $cardRowGap = max(16.0, $margin * 0.55);
    $rowGapBelowPhoto = max(18.0, $margin * 0.65);
    $row1Y = $photoY - $rowGapBelowPhoto;
    $row2Y = $row1Y - $cardH - $cardRowGap;
    $membershipTopY = $row2Y - $cardH - $cardRowGap;

    $drawSection(
        $commands,
        $textBlock,
        $addText,
        $leftX,
        $row1Y,
        $colW,
        $cardH,
        '1. Personal Information',
        [
            ['Full Name', trim(member_value($pdfMember, 'firstname') . ' ' . member_value($pdfMember, 'surname'))],
            ['Date of Birth', pdf_date(member_value($pdfMember, 'date_of_birth'))],
            ['Place of Birth', member_value($pdfMember, 'place_of_birth')],
        ]
    );

    $drawSection(
        $commands,
        $textBlock,
        $addText,
        $rightX,
        $row1Y,
        $colW,
        $cardH,
        '2. Contact Details',
        [
            ['Phone Number', member_value($pdfMember, 'phone_no')],
            ['Branch', member_value($pdfMember, 'branch')],
            ['Languages', member_value($pdfMember, 'languages')],
        ]
    );

    $drawSection(
        $commands,
        $textBlock,
        $addText,
        $leftX,
        $row2Y,
        $colW,
        $cardH,
        '3. Identification Details',
        [
            ['Voters ID No', member_value($pdfMember, 'voter_id_no')],
            ['Ghana Card No', member_value($pdfMember, 'ghana_card_no')],
            ['Membership ID', member_value($pdfMember, 'membership_id')],
        ]
    );

    $drawSection(
        $commands,
        $textBlock,
        $addText,
        $rightX,
        $row2Y,
        $colW,
        $cardH,
        '4. Proposer Information',
        [
            ['Proposer Name', member_value($pdfMember, 'proposer_name')],
            ['Proposer Party ID', member_value($pdfMember, 'proposer_party_id')],
            ['Proposer Phone', member_value($pdfMember, 'proposer_phone_no')],
        ]
    );

    $membershipCardH = min(124.0, max(108.0, ($membershipTopY - $margin - 36) * 0.85));

    $drawSection(
        $commands,
        $textBlock,
        $addText,
        $margin,
        $membershipTopY,
        $pageW - (2 * $margin),
        $membershipCardH,
        '5. Membership Details',
        [
            ['Year Joined', member_value($pdfMember, 'year_joined')],
            ['Position Held', member_value($pdfMember, 'positions_held')],
            ['Profession', member_value($pdfMember, 'profession')],
        ]
    );

    $addText($textBlock, $pageW - 150, 20, 'F1', 8, '0.68 0.68 0.68', 'www.kuukuacares.com');

    if ($hasPhoto) {
        $commands[] = 'q';
        $commands[] = sprintf('%.4f 0 0 %.4f %.4f %.4f cm', $drawW, $drawH, $drawX, $drawY);
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
        $imgDict = "7 0 obj << /Type /XObject /Subtype /Image /Width {$jpegW} /Height {$jpegH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($photoBinary) . " >> stream\n";
        // JPEG bytes must be immediately followed by endstream (no extra byte); /Length === strlen($photoBinary).
        $objects[] = $imgDict . $photoBinary . "endstream endobj\n";
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
