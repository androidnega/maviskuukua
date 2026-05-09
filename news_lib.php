<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function news_sanitize_html(string $html): string {
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    $html = preg_replace('#<\s*(script|style)\b[^>]*>.*?</\s*\1\s*>#is', '', $html) ?? '';
    $html = preg_replace('#<\s*(script|style)\b[^>]*/>#is', '', $html) ?? '';
    $html = preg_replace('#<\s*(iframe|object|embed)\b[^>]*>.*?</\s*\1\s*>#is', '', $html) ?? '';
    $html = preg_replace('#<\s*(iframe|object|embed)\b[^>]*/>#is', '', $html) ?? '';
    $html = preg_replace('#\s(on[a-z]+\s*|xmlns\s*)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
    $html = preg_replace('#\sstyle\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $html) ?? '';
    $html = preg_replace('#\sstyle\s*=\s*[^\s>]+#i', '', $html) ?? '';
    $html = preg_replace_callback('#\b(href|src)\s*=\s*("|\')([^"\']*)#i', static function (array $m): string {
        $v = trim($m[3]);
        if (preg_match('#^\s*javascript:#i', $v) !== 0) {
            return $m[1] . '=' . $m[2] . '#';
        }

        return $m[0];
    }, $html) ?? '';

    return trim($html);
}

function news_slugify(string $title): string {
    $t = strtolower(trim($title));
    $t = preg_replace('/[^a-z0-9]+/i', '-', $t) ?? '';
    $t = trim((string) $t, '-');
    if ($t === '') {
        $t = 'post-' . bin2hex(random_bytes(3));
    }

    return $t;
}

function news_ensure_unique_slug(PDO $pdo, string $base, ?int $exceptId): string {
    $slug = $base;
    $n = 2;
    while (true) {
        if ($exceptId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM news_posts WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $exceptId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM news_posts WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

function news_post_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        'SELECT n.*, a.username AS author_username FROM news_posts n
        LEFT JOIN admins a ON a.id = n.author_admin_id WHERE n.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function news_post_by_slug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare(
        'SELECT n.*, a.username AS author_username FROM news_posts n
        LEFT JOIN admins a ON a.id = n.author_admin_id WHERE n.slug = ?'
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return list<array<string,mixed>> */
function news_list_published(PDO $pdo, int $limit = 50): array {
    $limit = max(1, min(100, $limit));
    $st = $pdo->prepare(
        'SELECT n.*, a.username AS author_username FROM news_posts n
        LEFT JOIN admins a ON a.id = n.author_admin_id
        WHERE n.published = 1 ORDER BY n.created_at DESC LIMIT ' . (int) $limit
    );
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** @return list<array<string,mixed>> */
function news_list_all(PDO $pdo): array {
    $stmt = $pdo->query(
        'SELECT n.*, a.username AS author_username FROM news_posts n
        LEFT JOIN admins a ON a.id = n.author_admin_id
        ORDER BY n.created_at DESC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function news_delete_file_if_in_news_dir(?string $relPath): void {
    if ($relPath === null || $relPath === '') {
        return;
    }
    $relPath = str_replace('\\', '/', $relPath);
    if (!str_starts_with($relPath, 'storage/news/')) {
        return;
    }
    $full = BASE_DIR . '/' . $relPath;
    $realNews = realpath(NEWS_DIR);
    if ($realNews === false) {
        return;
    }
    $realFile = realpath($full);
    if ($realFile !== false && str_starts_with($realFile, $realNews) && is_file($realFile)) {
        @unlink($realFile);
    }
}

/**
 * @return array{0: ?string, 1: string}|null [ext, expected_mime] or null if disallowed
 */
function news_upload_allowed(string $filename, bool $imagesOnly): ?array {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
    ];
    if (!isset($map[$ext])) {
        return null;
    }
    if ($imagesOnly && strncmp($map[$ext], 'image/', 6) !== 0) {
        return null;
    }

    return [$ext, $map[$ext]];
}
