<?php
declare(strict_types=1);
/**
 * Arkesel SMS (https://arkesel.com) — HTTP API.
 * Configure keys in Settings (super admin). Uses Bearer auth JSON API shape commonly used by Arkesel.
 */

function sms_normalize_ghana_phone(string $raw): ?string {
    $digits = preg_replace('/\D/', '', $raw);
    if ($digits === null || $digits === '') {
        return null;
    }
    if (str_starts_with($digits, '233')) {
        return strlen($digits) >= 12 ? $digits : null;
    }
    if (str_starts_with($digits, '0') && strlen($digits) === 10) {
        return '233' . substr($digits, 1);
    }
    if (strlen($digits) === 9 && str_starts_with($digits, '2')) {
        return '233' . $digits;
    }

    return null;
}

/**
 * @return array{ok: bool, error?: string, response?: string}
 */
function arkesel_send_sms(PDO $pdo, string $e164NoPlus, string $message): array {
    $apiKey = trim(get_setting($pdo, 'arkasel_api_key'));
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'SMS API key not configured in Settings.'];
    }
    $sender = trim(get_setting($pdo, 'arkasel_sender_id', 'MavisHub'));
    $endpoint = trim(get_setting($pdo, 'arkasel_api_url', 'https://sms.arkesel.com/sms/api'));

    $payload = json_encode([
        'sender' => $sender,
        'message' => $message,
        'recipients' => [$e164NoPlus],
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return ['ok' => false, 'error' => 'Invalid payload'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP curl extension required for SMS.'];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'error' => $cerr ?: 'SMS request failed'];
    }
    if ($code >= 200 && $code < 300) {
        return ['ok' => true, 'response' => $resp];
    }

    return ['ok' => false, 'error' => 'HTTP ' . $code . ': ' . $resp];
}

/**
 * Arkesel managed OTP: message MUST contain %otp_code% placeholder.
 *
 * @return array{ok: bool, error?: string, response?: string, raw?: string}
 */
function arkesel_otp_generate(PDO $pdo, string $e164NoPlus, string $messageWithPlaceholder): array {
    $apiKey = trim(get_setting($pdo, 'arkasel_api_key'));
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'SMS API key not configured in Settings.'];
    }
    $sender = trim(get_setting($pdo, 'arkasel_sender_id', 'MavisHub'));
    $endpoint = trim(get_setting($pdo, 'arkasel_otp_generate_url', 'https://sms.arkesel.com/api/otp/generate'));

    $payload = json_encode([
        'phone_number' => $e164NoPlus,
        'sender_id' => $sender,
        'message' => $messageWithPlaceholder,
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return ['ok' => false, 'error' => 'Invalid payload'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP curl extension required for OTP SMS.'];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'error' => $cerr ?: 'OTP request failed'];
    }

    $decoded = json_decode((string)$resp, true);
    if (is_array($decoded) && (($decoded['code'] ?? '') === '1000' || ($decoded['code'] ?? 0) === 1000)) {
        return ['ok' => true, 'response' => $resp, 'raw' => $resp];
    }

    if ($code >= 200 && $code < 300 && !is_array($decoded)) {
        return ['ok' => true, 'response' => $resp, 'raw' => $resp];
    }

    return ['ok' => false, 'error' => 'HTTP ' . $code . ': ' . $resp, 'raw' => $resp];
}
