<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function contact_public_email(): string {
    return CONTACT_PUBLIC_EMAIL;
}

/**
 * @return array{ok:bool, errors:array<string,string>}
 */
function contact_str_len(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

function contact_validate_submission(array $post): array {
    $errors = [];
    $name = trim((string) ($post['full_name'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $subject = trim((string) ($post['subject'] ?? ''));
    $body = trim((string) ($post['body'] ?? ''));

    if ($name === '') {
        $errors['full_name'] = 'Please enter your name.';
    } elseif (contact_str_len($name) > 200) {
        $errors['full_name'] = 'Name is too long.';
    }

    if ($email === '') {
        $errors['email'] = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (contact_str_len($email) > 320) {
        $errors['email'] = 'Email is too long.';
    }

    if ($subject === '') {
        $errors['subject'] = 'Please enter a subject.';
    } elseif (contact_str_len($subject) > 400) {
        $errors['subject'] = 'Subject is too long.';
    }

    if ($body === '') {
        $errors['body'] = 'Please enter your message.';
    } elseif (contact_str_len($body) > 8000) {
        $errors['body'] = 'Message is too long (max 8000 characters).';
    }

    return ['ok' => count($errors) === 0, 'errors' => $errors];
}

function contact_insert_message(PDO $pdo, string $name, string $email, string $subject, string $body, string $ip): int {
    $stmt = $pdo->prepare('INSERT INTO contact_messages (full_name, email, subject, body, ip, created_at) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$name, $email, $subject, $body, $ip, date('c')]);

    return (int) $pdo->lastInsertId();
}

function contact_unread_count(PDO $pdo): int {
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** @return list<array<string,mixed>> */
function contact_recent_messages(PDO $pdo, int $limit = 8): array {
    $limit = max(1, min(50, $limit));

    try {
        return $pdo->query(
            'SELECT id, full_name, email, subject, body, created_at, read_at FROM contact_messages ORDER BY datetime(created_at) DESC LIMIT ' . (int) $limit
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
