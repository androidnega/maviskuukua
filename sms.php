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
 * Build Arkesel OTP /generate JSON body (current API: number, expiry, length, medium, type).
 *
 * @return array<string, mixed>
 */
function arkesel_otp_build_payload(PDO $pdo, string $e164NoPlus, string $messageWithPlaceholder): array {
    $sender = trim(get_setting($pdo, 'arkasel_sender_id', 'MavisHub'));
    $sender = substr($sender, 0, 11);

    $expiry = (int)get_setting($pdo, 'arkasel_otp_expiry', '5');
    if ($expiry < 1 || $expiry > 10) {
        $expiry = 5;
    }
    $length = (int)get_setting($pdo, 'arkasel_otp_length', '6');
    if ($length < 6 || $length > 15) {
        $length = 6;
    }

    return [
        'expiry' => $expiry,
        'length' => $length,
        'medium' => 'sms',
        'message' => $messageWithPlaceholder,
        'number' => $e164NoPlus,
        'sender_id' => $sender,
        'type' => 'numeric',
    ];
}

/**
 * Arkesel managed OTP: message MUST contain %otp_code% placeholder.
 * Uses Main SMS API key as api-key header (OTP does not work with Arkesel "multiple" sub-keys).
 *
 * @return array{ok: bool, error?: string, response?: string, raw?: string}
 */
function arkesel_otp_generate(PDO $pdo, string $e164NoPlus, string $messageWithPlaceholder): array {
    $apiKey = trim(get_setting($pdo, 'arkasel_api_key'));
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'SMS API key not configured in Settings.'];
    }
    $endpoint = trim(get_setting($pdo, 'arkasel_otp_generate_url', 'https://sms.arkesel.com/api/otp/generate'));

    $payloadArr = arkesel_otp_build_payload($pdo, $e164NoPlus, $messageWithPlaceholder);
    $payload = json_encode($payloadArr, JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return ['ok' => false, 'error' => 'Invalid payload'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP curl extension required for OTP SMS.'];
    }

    $doRequest = static function (string $endpointUrl, string $body, array $headers): array {
        $ch = curl_init($endpointUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        return ['resp' => $resp, 'code' => $code, 'cerr' => $cerr];
    };

    $headersApiKey = [
        'Content-Type: application/json',
        'api-key: ' . $apiKey,
    ];
    $headersBearer = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];

    $r = $doRequest($endpoint, $payload, $headersApiKey);
    $resp = $r['resp'];
    $code = $r['code'];

    if ($resp === false) {
        return ['ok' => false, 'error' => $r['cerr'] ?: 'OTP request failed'];
    }

    // Some accounts accept Bearer for OTP; retry on 401 Invalid key
    if ($code === 401) {
        $r2 = $doRequest($endpoint, $payload, $headersBearer);
        if ($r2['resp'] !== false) {
            $resp = $r2['resp'];
            $code = $r2['code'];
        }
    }

    $decoded = json_decode((string)$resp, true);
    if (is_array($decoded) && (($decoded['code'] ?? '') === '1000' || ($decoded['code'] ?? 0) === 1000)) {
        return ['ok' => true, 'response' => $resp, 'raw' => $resp];
    }

    if ($code >= 200 && $code < 300 && !is_array($decoded)) {
        return ['ok' => true, 'response' => $resp, 'raw' => $resp];
    }

    $errMsg = 'HTTP ' . $code . ': ' . $resp;
    if ($code === 401) {
        $errMsg .= ' — Use the Main SMS API key from Arkesel (OTP does not work with multiple/sub-keys).';
    }

    return ['ok' => false, 'error' => $errMsg, 'raw' => $resp];
}
