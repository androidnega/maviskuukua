<?php
session_start();

define('APP_NAME', 'Mavis Kuukua Bissue Membership System');
define('BASE_DIR', __DIR__);
define('STORAGE_DIR', BASE_DIR . '/storage');
define('DB_PATH', resolve_db_path());
define('PHOTO_DIR', BASE_DIR . '/storage/photos');
define('PDF_DIR', resolve_pdf_dir());

if (!is_dir(STORAGE_DIR)) mkdir(STORAGE_DIR, 0775, true);
if (!is_dir(PHOTO_DIR)) mkdir(PHOTO_DIR, 0775, true);
if (!is_dir(PDF_DIR)) mkdir(PDF_DIR, 0775, true);

function resolve_db_path(): string {
    $primaryPath = STORAGE_DIR . '/database.sqlite';
    if (ensure_writable_db_location($primaryPath)) {
        return $primaryPath;
    }

    // XAMPP's temp directory is writable by the Apache daemon on macOS installations.
    $fallbackDir = '/Applications/XAMPP/xamppfiles/temp/mavis_membership_system';
    $fallbackPath = $fallbackDir . '/database.sqlite';
    if (ensure_writable_db_location($fallbackPath)) {
        return $fallbackPath;
    }

    throw new RuntimeException('Unable to create or write SQLite database file in storage or temp directory.');
}

function resolve_pdf_dir(): string {
    $primaryDir = STORAGE_DIR . '/pdfs';
    if (ensure_writable_dir($primaryDir)) {
        return $primaryDir;
    }

    $fallbackDir = '/Applications/XAMPP/xamppfiles/temp/mavis_membership_system/pdfs';
    if (ensure_writable_dir($fallbackDir)) {
        return $fallbackDir;
    }

    throw new RuntimeException('Unable to create or write PDF directory in storage or temp directory.');
}

function ensure_writable_db_location(string $path): bool {
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return false;
    }

    if (!file_exists($path) && @file_put_contents($path, '') === false) {
        return false;
    }

    return is_writable($path);
}

function ensure_writable_dir(string $dir): bool {
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return false;
    }
    return is_writable($dir);
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        init_db($pdo);
    }
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        other_names TEXT,
        gender TEXT NOT NULL,
        date_of_birth TEXT NOT NULL,
        phone TEXT NOT NULL UNIQUE,
        email TEXT,
        community TEXT NOT NULL,
        electoral_area TEXT,
        voter_id TEXT NOT NULL UNIQUE,
        ghana_card TEXT,
        occupation TEXT,
        membership_id TEXT NOT NULL UNIQUE,
        photo_path TEXT,
        pdf_path TEXT,
        created_at TEXT NOT NULL
    )");
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM admins WHERE username = ?');
    $stmt->execute(['admin']);
    if ((int)$stmt->fetch()['total'] === 0) {
        $insert = $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)');
        $insert->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), date('c')]);
    }
}

function h(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): void { header('Location: ' . $path); exit; }
function is_admin(): bool { return isset($_SESSION['admin_id']); }
function require_admin(): void { if (!is_admin()) redirect('login.php'); }
function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) { $_SESSION['flash'][$key] = $value; return null; }
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}
function generate_membership_id(): string {
    return 'MKB-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}
