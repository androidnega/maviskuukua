<?php
declare(strict_types=1);

/** Must be loaded after config.php (session + db). */

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_COORDINATOR = 'coordinator';
const ROLE_FIELD_OFFICER = 'field_officer';

function admin_role(): string {
    $r = $_SESSION['admin_role'] ?? '';
    if ($r === ROLE_SUPER_ADMIN || $r === ROLE_COORDINATOR || $r === ROLE_FIELD_OFFICER) {
        return $r;
    }
    return ROLE_SUPER_ADMIN;
}

function is_super_admin(): bool {
    return admin_role() === ROLE_SUPER_ADMIN;
}

function is_coordinator(): bool {
    return admin_role() === ROLE_COORDINATOR;
}

function is_field_officer(): bool {
    return admin_role() === ROLE_FIELD_OFFICER;
}

function can_access_branch_executive_data(): bool {
    return is_super_admin() || is_field_officer();
}

function can_delete_members(): bool {
    return is_super_admin();
}

function can_manage_staff_accounts(): bool {
    return is_super_admin() || is_coordinator();
}

function can_view_audit_and_logs(): bool {
    return is_super_admin() || is_coordinator();
}

function can_export_bulk_members(): bool {
    return is_super_admin() || is_coordinator();
}

function members_active_clause(string $alias = ''): string {
    $p = $alias !== '' ? $alias . '.' : '';

    return '(' . $p . 'deleted_at IS NULL)';
}

function require_super_admin(): void {
    if (!is_super_admin()) {
        flash('admin_notice', 'You do not have access to that area.');
        redirect('admin.php');
    }
}

function require_branch_executive_section(): void {
    if (!can_access_branch_executive_data()) {
        flash('admin_notice', 'You do not have access to branch executive data.');
        redirect('admin.php');
    }
}

function require_audit_access(): void {
    if (!can_view_audit_and_logs()) {
        flash('admin_notice', 'You do not have access to audit logs.');
        redirect('admin.php');
    }
}

function require_settings_access(): void {
    require_super_admin();
}

function log_admin_action(PDO $pdo, string $action, string $entityType, ?int $entityId, ?array $details = null): void {
    $actorId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $json = $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_admin_id, action, entity_type, entity_id, details_json, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$actorId, $action, $entityType, $entityId, $json, $ip, date('c')]);
}

function get_setting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare('SELECT value FROM app_settings WHERE key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return $row ? (string)$row['value'] : $default;
}

function set_setting(PDO $pdo, string $key, string $value): void {
    $pdo->prepare('INSERT OR REPLACE INTO app_settings (key, value) VALUES (?, ?)')->execute([$key, $value]);
}
