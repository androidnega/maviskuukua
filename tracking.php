<?php
declare(strict_types=1);
/**
 * Public site analytics and per-registration funnel (anonymous cookie + IP).
 * Loaded after config.php (session + db).
 */

function tracking_anon_cookie(): string {
    if (!empty($_COOKIE['mavis_anon']) && preg_match('/^[a-f0-9]{32}$/', (string)$_COOKIE['mavis_anon'])) {
        return (string)$_COOKIE['mavis_anon'];
    }
    $id = bin2hex(random_bytes(16));
    if (!headers_sent()) {
        setcookie('mavis_anon', $id, [
            'expires' => time() + 365 * 24 * 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE['mavis_anon'] = $id;

    return $id;
}

function tracking_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    }

    return substr($ip, 0, 64);
}

function tracking_public_hit(string $path): void {
    try {
        $pdo = db();
        $pdo->prepare('INSERT INTO web_page_hits (ip, anon_id, path, created_at) VALUES (?,?,?,?)')
            ->execute([tracking_client_ip(), tracking_anon_cookie(), $path, date('c')]);
    } catch (Throwable $e) {
        // ignore tracking failures
    }
}

function tracking_registration_funnel_touch(): void {
    try {
        $pdo = db();
        $anon = tracking_anon_cookie();
        $ip = tracking_client_ip();
        $now = date('c');
        $stmt = $pdo->prepare('SELECT id FROM registration_funnel WHERE anon_id = ?');
        $stmt->execute([$anon]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE registration_funnel SET visit_count = visit_count + 1, last_seen_at = ?, ip = ? WHERE anon_id = ?')
                ->execute([$now, $ip, $anon]);
        } else {
            $pdo->prepare('INSERT INTO registration_funnel (anon_id, ip, registration_started_at, submitted_at, success_page_viewed_at, pdf_downloaded_at, pdf_inline_viewed_at, visit_count, last_seen_at, member_id)
                VALUES (?,?,?,NULL,NULL,NULL,NULL,1,?,NULL)')
                ->execute([$anon, $ip, $now, $now]);
        }
    } catch (Throwable $e) {
        // ignore
    }
}

function tracking_registration_attach_member(int $memberId): void {
    try {
        $pdo = db();
        $anon = tracking_anon_cookie();
        $now = date('c');
        $ip = tracking_client_ip();
        $u = $pdo->prepare('UPDATE registration_funnel SET member_id = ?, submitted_at = ?, last_seen_at = ?, ip = ? WHERE anon_id = ?');
        $u->execute([$memberId, $now, $now, $ip, $anon]);
        if ($u->rowCount() === 0) {
            $pdo->prepare('INSERT INTO registration_funnel (anon_id, member_id, ip, registration_started_at, submitted_at, visit_count, last_seen_at)
                VALUES (?,?,?,?,?,?,?)')
                ->execute([$anon, $memberId, $ip, $now, $now, 1, $now]);
        }
    } catch (Throwable $e) {
        // ignore
    }
}

function tracking_success_page_view(int $memberId): void {
    try {
        $pdo = db();
        $now = date('c');
        $pdo->prepare('UPDATE registration_funnel SET success_page_viewed_at = COALESCE(success_page_viewed_at, ?), last_seen_at = ? WHERE member_id = ?')
            ->execute([$now, $now, $memberId]);
    } catch (Throwable $e) {
        // ignore
    }
}

function tracking_public_pdf_inline(int $memberId): void {
    if (function_exists('is_admin') && is_admin()) {
        return;
    }
    try {
        $pdo = db();
        $now = date('c');
        $pdo->prepare('UPDATE registration_funnel SET pdf_inline_viewed_at = COALESCE(pdf_inline_viewed_at, ?), last_seen_at = ? WHERE member_id = ?')
            ->execute([$now, $now, $memberId]);
    } catch (Throwable $e) {
        // ignore
    }
}

function tracking_public_pdf_download(int $memberId): void {
    if (function_exists('is_admin') && is_admin()) {
        return;
    }
    try {
        $pdo = db();
        $now = date('c');
        $pdo->prepare('UPDATE registration_funnel SET pdf_downloaded_at = COALESCE(pdf_downloaded_at, ?), last_seen_at = ? WHERE member_id = ?')
            ->execute([$now, $now, $memberId]);
    } catch (Throwable $e) {
        // ignore
    }
}

/**
 * Dashboard helpers (super admin + coordinator).
 *
 * @return array{labels: string[], page_views: int[], unique_ips: int[], avg_session_seconds: float, median_registration_seconds: ?float, unique_ips_period: int, total_hits_period: int, returning_ip_hits: int}
 */
function tracking_dashboard_stats(PDO $pdo, int $days = 7): array {
    $labels = [];
    $pageViews = [];
    $uniqueIps = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime('-' . $i . ' days'));
        $labels[] = date('D j', strtotime($day));
        $st = $pdo->prepare('SELECT COUNT(*) AS c FROM web_page_hits WHERE date(created_at) = date(?)');
        $st->execute([$day]);
        $pageViews[] = (int)$st->fetch()['c'];
        $st2 = $pdo->prepare('SELECT COUNT(DISTINCT ip) AS c FROM web_page_hits WHERE date(created_at) = date(?)');
        $st2->execute([$day]);
        $uniqueIps[] = (int)$st2->fetch()['c'];
    }

    $sinceIso = date('c', strtotime('-' . $days . ' days'));
    $stU = $pdo->prepare('SELECT COUNT(DISTINCT ip) AS c FROM web_page_hits WHERE datetime(created_at) >= datetime(?)');
    $stU->execute([$sinceIso]);
    $uniquePeriod = (int)$stU->fetch()['c'];
    $stT = $pdo->prepare('SELECT COUNT(*) AS c FROM web_page_hits WHERE datetime(created_at) >= datetime(?)');
    $stT->execute([$sinceIso]);
    $totalHits = (int)$stT->fetch()['c'];

    $returningHits = max(0, $totalHits - $uniquePeriod);

    $avgSession = 0.0;
    $rows = $pdo->prepare('SELECT anon_id, date(created_at) AS d, MIN(created_at) AS mn, MAX(created_at) AS mx FROM web_page_hits WHERE datetime(created_at) >= datetime(?) GROUP BY anon_id, date(created_at) HAVING COUNT(*) > 1');
    $rows->execute([$sinceIso]);
    $rows = $rows->fetchAll(PDO::FETCH_ASSOC);
    $durations = [];
    foreach ($rows as $r) {
        $a = strtotime((string)$r['mn']);
        $b = strtotime((string)$r['mx']);
        if ($a && $b && $b >= $a) {
            $durations[] = $b - $a;
        }
    }
    if ($durations !== []) {
        $avgSession = array_sum($durations) / count($durations);
    }

    $medianReg = null;
    $funnel = $pdo->query("SELECT registration_started_at, submitted_at FROM registration_funnel WHERE member_id IS NOT NULL AND submitted_at IS NOT NULL AND registration_started_at IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $regSecs = [];
    foreach ($funnel as $f) {
        $a = strtotime((string)$f['registration_started_at']);
        $b = strtotime((string)$f['submitted_at']);
        if ($a && $b && $b >= $a) {
            $regSecs[] = $b - $a;
        }
    }
    if ($regSecs !== []) {
        sort($regSecs);
        $mid = (int)floor((count($regSecs) - 1) / 2);
        $medianReg = (float)$regSecs[$mid];
    }

    return [
        'labels' => $labels,
        'page_views' => $pageViews,
        'unique_ips' => $uniqueIps,
        'avg_session_seconds' => $avgSession,
        'median_registration_seconds' => $medianReg,
        'unique_ips_period' => $uniquePeriod,
        'total_hits_period' => $totalHits,
        'returning_ip_hits' => $returningHits,
    ];
}
