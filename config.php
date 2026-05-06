<?php
session_start();

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/sms.php';

define('APP_NAME', 'Mavis Kuukua Bissue Membership System');
define('BASE_DIR', __DIR__);
define('STORAGE_DIR', BASE_DIR . '/storage');
define('DB_PATH', resolve_db_path());
define('PDF_DIR', resolve_pdf_dir());
define('PHOTO_DIR', STORAGE_DIR . '/photos');

if (!is_dir(STORAGE_DIR)) mkdir(STORAGE_DIR, 0775, true);
if (!is_dir(PDF_DIR)) mkdir(PDF_DIR, 0775, true);
if (!is_dir(PHOTO_DIR)) mkdir(PHOTO_DIR, 0775, true);

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
        firstname TEXT NOT NULL,
        surname TEXT NOT NULL,
        place_of_birth TEXT NOT NULL,
        date_of_birth TEXT NOT NULL,
        branch TEXT NOT NULL,
        phone_no TEXT NOT NULL UNIQUE,
        year_joined TEXT NOT NULL,
        voter_id_no TEXT NOT NULL UNIQUE,
        ghana_card_no TEXT NOT NULL UNIQUE,
        positions_held TEXT,
        languages TEXT,
        profession TEXT,
        proposer_name TEXT NOT NULL,
        proposer_party_id TEXT NOT NULL,
        proposer_phone_no TEXT NOT NULL,
        membership_id TEXT NOT NULL UNIQUE,
        photo_path TEXT,
        pdf_path TEXT,
        created_at TEXT NOT NULL,
        viewed_at TEXT
    )");
    migrate_members_schema($pdo);
    migrate_system_extensions($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM admins WHERE username = ?');
    $stmt->execute(['admin']);
    if ((int)$stmt->fetch()['total'] === 0) {
        $insert = $pdo->prepare('INSERT INTO admins (username, password_hash, role, created_at) VALUES (?, ?, ?, ?)');
        $insert->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), ROLE_SUPER_ADMIN, date('c')]);
    }
}

function migrate_system_extensions(PDO $pdo): void {
    $adminCols = [];
    foreach ($pdo->query('PRAGMA table_info(admins)')->fetchAll() as $col) {
        $adminCols[] = $col['name'];
    }
    if (!in_array('role', $adminCols, true)) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN role TEXT NOT NULL DEFAULT '" . ROLE_SUPER_ADMIN . "'");
    }
    if (!in_array('created_by_admin_id', $adminCols, true)) {
        $pdo->exec('ALTER TABLE admins ADD COLUMN created_by_admin_id INTEGER');
    }
    if (!in_array('phone', $adminCols, true)) {
        $pdo->exec('ALTER TABLE admins ADD COLUMN phone TEXT');
    }
    $pdo->exec("UPDATE admins SET role = '" . ROLE_SUPER_ADMIN . "' WHERE role IS NULL OR trim(role) = ''");

    $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (
        key TEXT PRIMARY KEY NOT NULL,
        value TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        actor_admin_id INTEGER,
        action TEXT NOT NULL,
        entity_type TEXT NOT NULL,
        entity_id INTEGER,
        details_json TEXT,
        ip TEXT,
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS registration_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        member_id INTEGER NOT NULL UNIQUE,
        token TEXT NOT NULL,
        phone_normalized TEXT,
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS chat_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_id INTEGER NOT NULL,
        body TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS web_page_hits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        anon_id TEXT NOT NULL,
        path TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_web_hits_created ON web_page_hits(created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_web_hits_ip ON web_page_hits(ip)');

    $pdo->exec('CREATE TABLE IF NOT EXISTS registration_funnel (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        anon_id TEXT NOT NULL UNIQUE,
        member_id INTEGER UNIQUE,
        ip TEXT,
        registration_started_at TEXT NOT NULL,
        submitted_at TEXT,
        success_page_viewed_at TEXT,
        pdf_downloaded_at TEXT,
        pdf_inline_viewed_at TEXT,
        visit_count INTEGER NOT NULL DEFAULT 1,
        last_seen_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS member_audit_snapshots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        member_id INTEGER NOT NULL,
        snapshot_json TEXT NOT NULL,
        reason TEXT,
        actor_admin_id INTEGER,
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS staff_delete_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        target_admin_id INTEGER NOT NULL UNIQUE,
        requested_by_admin_id INTEGER NOT NULL,
        requested_at TEXT NOT NULL
    )');

    $memCols = [];
    foreach ($pdo->query('PRAGMA table_info(members)')->fetchAll() as $col) {
        $memCols[] = $col['name'];
    }
    if (!in_array('deleted_at', $memCols, true)) {
        $pdo->exec('ALTER TABLE members ADD COLUMN deleted_at TEXT');
    }
    if (!in_array('deleted_by_admin_id', $memCols, true)) {
        $pdo->exec('ALTER TABLE members ADD COLUMN deleted_by_admin_id INTEGER');
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

function migrate_members_schema(PDO $pdo): void {
    $columns = $pdo->query('PRAGMA table_info(members)')->fetchAll();
    $available = [];
    foreach ($columns as $column) {
        $available[] = $column['name'];
    }

    $required = [
        'firstname', 'surname', 'place_of_birth', 'date_of_birth', 'branch', 'phone_no',
        'year_joined', 'voter_id_no', 'ghana_card_no', 'positions_held', 'languages', 'profession',
        'proposer_name', 'proposer_party_id', 'proposer_phone_no', 'membership_id', 'pdf_path', 'created_at'
    ];

    foreach ($required as $name) {
        if (!in_array($name, $available, true)) {
            $pdo->exec('DROP TABLE IF EXISTS members');
            $pdo->exec("CREATE TABLE members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                firstname TEXT NOT NULL,
                surname TEXT NOT NULL,
                place_of_birth TEXT NOT NULL,
                date_of_birth TEXT NOT NULL,
                branch TEXT NOT NULL,
                phone_no TEXT NOT NULL UNIQUE,
                year_joined TEXT NOT NULL,
                voter_id_no TEXT NOT NULL UNIQUE,
                ghana_card_no TEXT NOT NULL UNIQUE,
                positions_held TEXT,
                languages TEXT,
                profession TEXT,
                proposer_name TEXT NOT NULL,
                proposer_party_id TEXT NOT NULL,
                proposer_phone_no TEXT NOT NULL,
                membership_id TEXT NOT NULL UNIQUE,
                photo_path TEXT,
                pdf_path TEXT,
                created_at TEXT NOT NULL,
                viewed_at TEXT
            )");
            break;
        }
    }

    if (!in_array('photo_path', $available, true)) {
        $pdo->exec('ALTER TABLE members ADD COLUMN photo_path TEXT');
    }
    if (!in_array('viewed_at', $available, true)) {
        $pdo->exec('ALTER TABLE members ADD COLUMN viewed_at TEXT');
    }
}
