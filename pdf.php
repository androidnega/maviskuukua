<?php
function pdf_escape(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdf_date(string $value): string {
    $time = strtotime($value);
    return $time ? date('d M Y', $time) : $value;
}

function pdf_datetime(string $value): string {
    $time = strtotime($value);

    return $time ? date('d M Y H:i', $time) : $value;
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

/** Passport-style portrait ratio (width : height), matches common ID photo proportions and PDF slot layout. */
const PASSPORT_ASPECT_W = 35;
const PASSPORT_ASPECT_H = 45;

function passport_aspect_wh(): float {
    return PASSPORT_ASPECT_W / PASSPORT_ASPECT_H;
}

/**
 * Center-crop a GD image to passport width:height ratio.
 *
 * @param resource|\GdImage $src
 * @return resource|\GdImage|false
 */
function passport_center_crop_gd($src) {
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($srcW <= 0 || $srcH <= 0) {
        return false;
    }
    $r = passport_aspect_wh();
    $rw = $srcW / $srcH;
    if ($rw > $r) {
        $cropH = $srcH;
        $cropW = (int)max(1, round($srcH * $r));
        $sx = (int)(($srcW - $cropW) / 2);
        $sy = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int)max(1, round($srcW / $r));
        $sx = 0;
        $sy = (int)(($srcH - $cropH) / 2);
    }
    $cropped = @imagecrop($src, ['x' => $sx, 'y' => $sy, 'width' => $cropW, 'height' => $cropH]);

    return $cropped !== false ? $cropped : false;
}

/**
 * Resize passport-ratio image so longest edge ≤ $maxEdge pixels (aspect unchanged).
 *
 * @param resource|\GdImage $cropped Passport-ratio image
 * @return resource|\GdImage|false
 */
function passport_scale_max_edge_gd($cropped, int $maxEdge) {
    $cw = imagesx($cropped);
    $ch = imagesy($cropped);
    if ($cw <= 0 || $ch <= 0) {
        return false;
    }
    $scale = min(1.0, $maxEdge / max($cw, $ch));
    $nw = max(1, (int)round($cw * $scale));
    $nh = max(1, (int)round($ch * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $cropped, 0, 0, 0, 0, $nw, $nh, $cw, $ch);

    return $dst;
}

/**
 * Resize passport-ratio image to an exact output height (width derived from 35:45).
 *
 * @param resource|\GdImage $cropped
 * @return resource|\GdImage|false
 */
function passport_resize_to_height_gd($cropped, int $targetHeightPx) {
    $cw = imagesx($cropped);
    $ch = imagesy($cropped);
    if ($cw <= 0 || $ch <= 0 || $targetHeightPx < 2) {
        return false;
    }
    $tw = max(1, (int)round($targetHeightPx * PASSPORT_ASPECT_W / PASSPORT_ASPECT_H));
    $th = $targetHeightPx;
    $dst = imagecreatetruecolor($tw, $th);
    imagecopyresampled($dst, $cropped, 0, 0, 0, 0, $tw, $th, $cw, $ch);

    return $dst;
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

/** Pixel height for passport raster embedded in PDF (width follows 35:45). */
const PDF_PASSPORT_EMBED_HEIGHT = 450;

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

    $gdOk = function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')
        && function_exists('imagejpeg');

    if (!$gdOk) {
        if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $ext === 'jpg' || $ext === 'jpeg') {
            return $raw;
        }

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
        if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $ext === 'jpg' || $ext === 'jpeg') {
            return $raw;
        }

        return null;
    }

    $cropped = passport_center_crop_gd($src);
    imagedestroy($src);
    if ($cropped === false) {
        return null;
    }

    $passport = passport_resize_to_height_gd($cropped, PDF_PASSPORT_EMBED_HEIGHT);
    imagedestroy($cropped);
    if ($passport === false) {
        return null;
    }

    ob_start();
    imagejpeg($passport, null, 88);
    $jpeg = (string)ob_get_clean();
    imagedestroy($passport);

    return $jpeg !== '' ? $jpeg : null;
}

function create_member_pdf(array $member, array $pdfOverrides = []): string {
    $pdfMember = array_merge($member, $pdfOverrides);
    $filename = 'member_' . $member['id'] . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $member['membership_id']) . '.pdf';
    $path = PDF_DIR . '/' . $filename;

    $pageW = 595;
    $pageH = 842;
    $margin = 36;
    $contentW = $pageW - (2 * $margin);
    /** Deep green (header, section titles, passport frame) — RGB 0–1 */
    $brandGreen = '0.06 0.38 0.20';
    $brandGreenDark = '0.05 0.30 0.17';
    $labelGrey = '0.58 0.60 0.63';
    $valueBlack = '0 0 0';
    $ruleGrey = '0.83 0.85 0.87';
    $white = '1 1 1';

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
        $tb[] = 'BT';
        $tb[] = "/$font $size Tf";
        $tb[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $y);
        $tb[] = '(' . pdf_escape($text) . ') Tj';
        $tb[] = 'ET';
    };

    // Page background
    $commands[] = '1 1 1 rg';
    $commands[] = sprintf('0 0 %.2f %.2f re f', $pageW, $pageH);

    // Full-width deep green header band
    $hHeader = 70;
    $yHeaderBottom = $pageH - $hHeader;
    $commands[] = $brandGreen . ' rg';
    $commands[] = sprintf('0 %.2f %.2f %.2f re f', $yHeaderBottom, $pageW, $hHeader);

    $addText($textBlock, $margin, $pageH - 22, 'F2', 18, $white, 'Membership Registration Form');
    $addText($textBlock, $margin, $pageH - 44, 'F1', 10, $white, 'Hon. Mavis Kuukua Bissue | Ahanta West');

    // Meta row (left) + passport frame (right), below header
    $photoFrameW = min(122, $contentW * 0.26);
    $photoFrameH = $photoFrameW * (PASSPORT_ASPECT_H / PASSPORT_ASPECT_W);
    $photoX = $pageW - $margin - $photoFrameW;
    $photoTopY = $yHeaderBottom - 12;
    $photoY = $photoTopY - $photoFrameH;

    $yRef = $yHeaderBottom - 18;
    $yDate = $yRef - 17;
    $refNo = member_value($pdfMember, 'membership_id');
    $submitted = pdf_datetime(member_value($pdfMember, 'created_at'));
    $addText($textBlock, $margin, $yRef, 'F2', 10, $valueBlack, 'Reference No: ' . $refNo);
    $addText($textBlock, $margin, $yDate, 'F1', 10, $valueBlack, 'Date Submitted: ' . $submitted);

    // Photo area: white fill, optional image, deep green border
    $commands[] = '1 1 1 rg';
    $commands[] = sprintf('%.2f %.2f %.2f %.2f re f', $photoX, $photoY, $photoFrameW, $photoFrameH);

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

    if ($hasPhoto && $photoBinary !== null) {
        $commands[] = 'q';
        $commands[] = sprintf('%.4f 0 0 %.4f %.4f %.4f cm', $drawW, $drawH, $drawX, $drawY);
        $commands[] = '/Im1 Do';
        $commands[] = 'Q';
    }

    $commands[] = '0.85 w';
    $commands[] = $brandGreenDark . ' RG';
    $commands[] = sprintf('%.2f %.2f %.2f %.2f re S', $photoX, $photoY, $photoFrameW, $photoFrameH);

    if (!$hasPhoto) {
        $addText(
            $textBlock,
            $photoX + ($photoFrameW / 2) - 48,
            $photoY + ($photoFrameH / 2) - 3,
            'F2',
            7,
            $labelGrey,
            'PASSPORT PHOTOGRAPH'
        );
    }

    // Five sections: three columns each (label small grey, value black uppercase)
    $colGap = 10;
    $colW = ($contentW - 2 * $colGap) / 3;
    $xCol = static function (int $i) use ($margin, $colW, $colGap): float {
        return $margin + $i * ($colW + $colGap);
    };

    $drawThreeColSection = static function (
        array &$commands,
        array &$textBlock,
        callable $addText,
        float $titleBaseline,
        string $sectionHeading,
        array $cols
    ) use ($margin, $pageW, $brandGreen, $labelGrey, $valueBlack, $ruleGrey, $xCol, $colW): float {
        $addText($textBlock, $margin, $titleBaseline, 'F2', 10, $brandGreen, $sectionHeading);
        $yRule = $titleBaseline - 11;
        $commands[] = $ruleGrey . ' RG';
        $commands[] = '0.4 w';
        $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $margin, $yRule, $pageW - $margin, $yRule);

        $yLab = $titleBaseline - 24;
        $yVal = $titleBaseline - 38;
        for ($i = 0; $i < 3; $i++) {
            $pair = $cols[$i] ?? ['', ''];
            $lab = strtoupper(trim((string)($pair[0] ?? '')));
            $val = strtoupper(trim((string)($pair[1] ?? '')));
            if ($val === '') {
                $val = 'N/A';
            }
            $addText($textBlock, $xCol($i), $yLab, 'F2', 7, $labelGrey, $lab);
            $addText($textBlock, $xCol($i), $yVal, 'F1', 9, $valueBlack, $val);
        }

        return $titleBaseline - 68;
    };

    $fullName = trim(member_value($pdfMember, 'firstname') . ' ' . member_value($pdfMember, 'surname'));
    $ySec = min($photoY - 20, $yDate - 28);
    $ySec = $drawThreeColSection(
        $commands,
        $textBlock,
        $addText,
        $ySec,
        '1. PERSONAL INFORMATION',
        [
            ['FULL NAME', $fullName],
            ['DATE OF BIRTH', pdf_date(member_value($pdfMember, 'date_of_birth'))],
            ['PLACE OF BIRTH', member_value($pdfMember, 'place_of_birth')],
        ]
    );
    $ySec = $drawThreeColSection(
        $commands,
        $textBlock,
        $addText,
        $ySec,
        '2. CONTACT DETAILS',
        [
            ['PHONE NUMBER', member_value($pdfMember, 'phone_no')],
            ['BRANCH', member_value($pdfMember, 'branch')],
            ['LANGUAGES', member_value($pdfMember, 'languages')],
        ]
    );
    $ySec = $drawThreeColSection(
        $commands,
        $textBlock,
        $addText,
        $ySec,
        '3. IDENTIFICATION DETAILS',
        [
            ['VOTERS ID NO', member_value($pdfMember, 'voter_id_no')],
            ['GHANA CARD NO', member_value($pdfMember, 'ghana_card_no')],
            ['MEMBERSHIP ID', member_value($pdfMember, 'membership_id')],
        ]
    );
    $ySec = $drawThreeColSection(
        $commands,
        $textBlock,
        $addText,
        $ySec,
        '4. PROPOSER INFORMATION',
        [
            ['PROPOSER NAME', member_value($pdfMember, 'proposer_name')],
            ['PROPOSER PARTY ID', member_value($pdfMember, 'proposer_party_id')],
            ['PROPOSER PHONE', member_value($pdfMember, 'proposer_phone_no')],
        ]
    );
    $drawThreeColSection(
        $commands,
        $textBlock,
        $addText,
        $ySec,
        '5. MEMBERSHIP DETAILS',
        [
            ['YEAR JOINED', member_value($pdfMember, 'year_joined')],
            ['POSITION HELD', member_value($pdfMember, 'positions_held')],
            ['PROFESSION', member_value($pdfMember, 'profession')],
        ]
    );

    $commands[] = $ruleGrey . ' RG';
    $commands[] = '0.35 w';
    $commands[] = sprintf('%.2f %.2f m %.2f %.2f l S', $margin, 52, $pageW - $margin, 52);
    $addText(
        $textBlock,
        $pageW / 2 - 148,
        38,
        'F1',
        8,
        $labelGrey,
        'Official Membership Record - Ahanta West Constituency'
    );

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
