<?php
declare(strict_types=1);
/**
 * Arkesel SMS — HTTP API (developers.arkesel.com).
 * SMS v2: POST https://sms.arkesel.com/api/v2/sms/send — JSON { sender, message, recipients[] }, header api-key (Bearer fallback).
 * Legacy https://sms.arkesel.com/sms/api returns 405 for POST — migrated automatically in config.
 * OTP: POST https://sms.arkesel.com/api/otp/generate — JSON { number, sender_id, expiry, length, medium, type, message } with %otp_code%.
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
 * Resolve SMS send URL; map deprecated path that returns HTTP 405 to v2.
 */
function arkesel_resolve_sms_send_url(string $stored): string {
    $t = trim($stored);
    if ($t === '') {
        return 'https://sms.arkesel.com/api/v2/sms/send';
    }
    if (!str_contains($t, '/api/v2/') && str_contains($t, 'sms.arkesel.com') && str_contains($t, '/sms/api')) {
        return 'https://sms.arkesel.com/api/v2/sms/send';
    }

    return $t;
}

/**
 * @return array{ok: bool, error?: string, response?: string}
 */
function arkesel_send_sms(PDO $pdo, string $e164NoPlus, string $message): array {
    $apiKey = trim(get_setting($pdo, 'arkasel_api_key'));
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'SMS API key not configured in Settings.'];
    }
    $sender = substr(trim(get_setting($pdo, 'arkasel_sender_id', 'MavisHub')), 0, 11);
    $endpoint = arkesel_resolve_sms_send_url(trim(get_setting($pdo, 'arkasel_api_url', 'https://sms.arkesel.com/api/v2/sms/send')));

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

    $doRequest = static function (string $url, string $body, array $headers): array {
        $ch = curl_init($url);
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

    $authVariants = [
        [
            'Content-Type: application/json',
            'api-key: ' . $apiKey,
            'Authorization: Bearer ' . $apiKey,
        ],
        [
            'Content-Type: application/json',
            'api-key: ' . $apiKey,
        ],
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ];

    $lastAuthError = '';

    foreach ($authVariants as $headers) {
        $r = $doRequest($endpoint, $payload, $headers);
        if ($r['resp'] === false) {
            return ['ok' => false, 'error' => $r['cerr'] ?: 'SMS request failed'];
        }

        $resp = $r['resp'];
        $http = $r['code'];
        $decoded = json_decode((string)$resp, true);

        if ($http >= 200 && $http < 300 && is_array($decoded)) {
            $c = $decoded['code'] ?? null;
            $st = strtolower((string)($decoded['status'] ?? ''));
            // v2 /api/v2/sms/send returns { "status": "success", "data": ... }; legacy uses code 1000
            if ($c === '1000' || $c === 1000 || $st === 'success') {
                return ['ok' => true, 'response' => $resp];
            }

            return ['ok' => false, 'error' => arkesel_api_user_message($decoded, $http, $resp, 'SMS')];
        }

        if ($http === 401) {
            $lastAuthError = arkesel_api_user_message(is_array($decoded) ? $decoded : null, $http, $resp, 'SMS');

            continue;
        }

        if ($http === 405) {
            return [
                'ok' => false,
                'error' => 'HTTP 405: SMS endpoint does not accept this request. Use POST '
                    . 'https://sms.arkesel.com/api/v2/sms/send with JSON sender, message, recipients (see Settings).',
            ];
        }

        return ['ok' => false, 'error' => arkesel_api_user_message(is_array($decoded) ? $decoded : null, $http, $resp, 'SMS')];
    }

    return [
        'ok' => false,
        'error' => $lastAuthError !== '' ? $lastAuthError : 'SMS authentication failed after retries.',
    ];
}

/**
 * Human-readable message for Arkesel JSON error bodies (SMS / OTP).
 *
 * @param array<string,mixed>|null $decoded
 */
function arkesel_api_user_message(?array $decoded, int $httpCode, string $rawResp, string $channelLabel = 'API'): string {
    $msg = is_array($decoded) ? trim((string)($decoded['message'] ?? '')) : '';
    $c = is_array($decoded) ? ($decoded['code'] ?? null) : null;
    $status = is_array($decoded) ? trim((string)($decoded['status'] ?? '')) : '';

    if (
        $httpCode === 401
        || ($msg !== '' && stripos($msg, 'invalid key') !== false)
        || ($status !== '' && strtolower($status) === 'error' && stripos($msg, 'invalid key') !== false)
    ) {
        return 'Authentication failed'
            . ($c !== null ? ' (code ' . $c . ')' : '')
            . '. Paste the Main SMS API key from Arkesel (Dashboard → API). '
            . 'OTP does not accept “multiple” sub-keys. Remove spaces/newlines from the key. '
            . 'If it still fails, create a fresh Main key in the dashboard.';
    }

    if ($c === '1007' || $c === 1007) {
        return 'Insufficient SMS credits for ' . $channelLabel . ' (Arkesel code 1007). '
            . 'OTP and SMS deduct SMS units from your SMS balance — a large “main wallet” balance alone is not enough if your SMS package is empty. '
            . 'In Arkesel: purchase or top up an SMS bundle, then retry.';
    }

    if ($msg !== '') {
        return 'Arkesel ' . $channelLabel . ': ' . $msg . ($c !== null ? ' (code ' . $c . ')' : '') . ' [HTTP ' . $httpCode . ']';
    }

    return 'HTTP ' . $httpCode . ': ' . $rawResp;
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
 * True when Arkesel JSON indicates success (HTTP may still be 200 on errors).
 *
 * @param array<string,mixed>|null $decoded
 */
function arkesel_response_is_success(?array $decoded): bool {
    if (!is_array($decoded)) {
        return false;
    }
    $c = $decoded['code'] ?? null;

    return $c === '1000' || $c === 1000;
}

/**
 * When Arkesel's /otp/generate endpoint fails (e.g. code 1007) but your SMS API works,
 * send the same template via the standard SMS endpoint with a locally generated OTP.
 *
 * @return array{ok: bool, error?: string, response?: string, raw?: string, delivery?: string}
 */
function arkesel_otp_try_sms_fallback(PDO $pdo, string $e164NoPlus, string $messageWithPlaceholder): array {
    $disable = trim(get_setting($pdo, 'arkasel_otp_disable_sms_fallback')) === '1';
    if ($disable) {
        return ['ok' => false, 'error' => 'SMS fallback disabled in settings.'];
    }

    $length = (int)get_setting($pdo, 'arkasel_otp_length', '6');
    $length = max(6, min(15, $length));
    $max = 10 ** $length;
    $otp = str_pad((string)random_int(0, $max - 1), $length, '0', STR_PAD_LEFT);

    $expiry = (int)get_setting($pdo, 'arkasel_otp_expiry', '5');
    if ($expiry < 1 || $expiry > 10) {
        $expiry = 5;
    }

    $msg = str_replace(['%otp_code%', '%expiry%'], [$otp, (string)$expiry], $messageWithPlaceholder);

    $sms = arkesel_send_sms($pdo, $e164NoPlus, $msg);
    if ($sms['ok']) {
        return [
            'ok' => true,
            'response' => $sms['response'] ?? '',
            'raw' => $sms['response'] ?? '',
            'delivery' => 'sms_fallback',
        ];
    }

    return ['ok' => false, 'error' => $sms['error'] ?? 'SMS fallback failed'];
}

/**
 * Arkesel managed OTP: message MUST contain %otp_code% placeholder.
 * Retries different auth header combinations only on HTTP 401 (invalid key).
 * If the OTP API returns an error (e.g. 1007) but your account sends SMS fine, falls back to plain SMS with the same text.
 *
 * @return array{ok: bool, error?: string, response?: string, raw?: string, delivery?: string}
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

    $authVariants = [
        [
            'Content-Type: application/json',
            'api-key: ' . $apiKey,
            'Authorization: Bearer ' . $apiKey,
        ],
        [
            'Content-Type: application/json',
            'api-key: ' . $apiKey,
        ],
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ];

    $lastAuthError = '';

    foreach ($authVariants as $headers) {
        $r = $doRequest($endpoint, $payload, $headers);
        if ($r['resp'] === false) {
            return ['ok' => false, 'error' => $r['cerr'] ?: 'OTP request failed'];
        }

        $resp = $r['resp'];
        $http = $r['code'];
        $decoded = json_decode((string)$resp, true);

        if ($http >= 200 && $http < 300 && is_array($decoded)) {
            if (arkesel_response_is_success($decoded)) {
                return ['ok' => true, 'response' => $resp, 'raw' => $resp, 'delivery' => 'otp_api'];
            }

            // HTTP 200 but managed OTP failed — try standard SMS API (same wallet many apps use)
            $fb = arkesel_otp_try_sms_fallback($pdo, $e164NoPlus, $messageWithPlaceholder);
            if ($fb['ok']) {
                return $fb;
            }

            $apiErr = arkesel_api_user_message($decoded, $http, $resp, 'OTP');

            return [
                'ok' => false,
                'error' => $apiErr . ' SMS fallback: ' . ($fb['error'] ?? 'failed'),
                'raw' => $resp,
            ];
        }

        if ($http === 401) {
            $lastAuthError = arkesel_api_user_message(is_array($decoded) ? $decoded : null, $http, $resp, 'OTP');

            continue;
        }

        // Non-401 HTTP error — try SMS fallback once (OTP endpoint may reject while SMS works)
        $fb = arkesel_otp_try_sms_fallback($pdo, $e164NoPlus, $messageWithPlaceholder);
        if ($fb['ok']) {
            return $fb;
        }

        return [
            'ok' => false,
            'error' => arkesel_api_user_message(is_array($decoded) ? $decoded : null, $http, $resp, 'OTP')
                . ' SMS fallback: ' . ($fb['error'] ?? ''),
            'raw' => $resp,
        ];
    }

    // Only HTTP 401 from every variant — OTP endpoint won't authenticate; SMS API often still works
    $fb = arkesel_otp_try_sms_fallback($pdo, $e164NoPlus, $messageWithPlaceholder);
    if ($fb['ok']) {
        return $fb;
    }

    return [
        'ok' => false,
        'error' => ($lastAuthError !== '' ? $lastAuthError : 'OTP authentication failed after retries.')
            . ' SMS fallback: ' . ($fb['error'] ?? ''),
        'raw' => null,
    ];
}
