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

/** Super admin only: remove coordinators and field officers (not self, not other super admins). */
function staff_target_deletable_by_actor(array $target): bool {
    if (!is_super_admin()) {
        return false;
    }
    $tid = (int)($target['id'] ?? 0);
    if ($tid <= 0 || $tid === (int)($_SESSION['admin_id'] ?? 0)) {
        return false;
    }
    $role = (string)($target['role'] ?? '');

    return $role === ROLE_COORDINATOR || $role === ROLE_FIELD_OFFICER;
}

/**
 * Super admin: reset coordinator or field officer (not self).
 * Coordinator: reset field officers only (not self).
 */
function staff_target_password_resettable_by_actor(array $target): bool {
    $tid = (int)($target['id'] ?? 0);
    if ($tid <= 0 || $tid === (int)($_SESSION['admin_id'] ?? 0)) {
        return false;
    }
    $role = (string)($target['role'] ?? '');
    if (is_super_admin()) {
        return $role === ROLE_COORDINATOR || $role === ROLE_FIELD_OFFICER;
    }
    if (is_coordinator()) {
        return $role === ROLE_FIELD_OFFICER;
    }

    return false;
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

/**
 * Field officer removal: only the coordinator who created them must approve;
 * if created by super admin (or unknown), any coordinator may approve.
 */
function coordinator_can_approve_fo_delete(PDO $pdo, int $coordinatorId, array $target): bool {
    if ((string)($target['role'] ?? '') !== ROLE_FIELD_OFFICER) {
        return false;
    }
    $creatorId = (int)($target['created_by_admin_id'] ?? 0);
    if ($creatorId <= 0) {
        return true;
    }
    $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
    $stmt->execute([$creatorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return true;
    }
    if ((string)$row['role'] === ROLE_COORDINATOR) {
        return $creatorId === $coordinatorId;
    }

    return true;
}

/**
 * Permanently remove a staff row (chat messages, child created_by links, optional pending request row).
 *
 * @param array{id:int,username?:string,role?:string} $target
 * @param array<string,mixed>|null $auditExtra merged into audit details
 */
function staff_perform_hard_delete(PDO $pdo, array $target, ?array $auditExtra = null): void {
    $targetId = (int)($target['id'] ?? 0);
    if ($targetId <= 0) {
        throw new InvalidArgumentException('Invalid staff delete target');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM staff_delete_requests WHERE target_admin_id = ?')->execute([$targetId]);
        $pdo->prepare('UPDATE admins SET created_by_admin_id = NULL WHERE created_by_admin_id = ?')->execute([$targetId]);

        try {
            $pdo->prepare('DELETE FROM chat_messages WHERE admin_id = ?')->execute([$targetId]);
        } catch (Throwable $e) {
            // table may be absent in older DBs
        }

        $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$targetId]);

        $chk = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE id = ?');
        $chk->execute([$targetId]);
        if ((int)$chk->fetchColumn() !== 0) {
            $pdo->rollBack();
            throw new RuntimeException('Staff delete did not remove the row');
        }

        $details = array_merge([
            'deleted_username' => (string)($target['username'] ?? ''),
            'deleted_role' => (string)($target['role'] ?? ''),
        ], $auditExtra ?? []);
        log_admin_action($pdo, 'staff_account_deleted', 'admin', $targetId, $details);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Pending FO removal requests this coordinator is allowed to approve or reject.
 *
 * @return list<array<string,mixed>>
 */
function staff_pending_removal_rows_for_coordinator(PDO $pdo, int $coordinatorId): array {
    $stmt = $pdo->query(
        'SELECT r.id AS request_id, r.requested_at, r.requested_by_admin_id,
            a.id AS target_id, a.username AS target_username, a.role AS target_role, a.phone, a.created_at, a.created_by_admin_id
        FROM staff_delete_requests r
        INNER JOIN admins a ON a.id = r.target_admin_id
        ORDER BY r.requested_at ASC'
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
        $target = [
            'id' => (int)$row['target_id'],
            'role' => (string)$row['target_role'],
            'created_by_admin_id' => $row['created_by_admin_id'],
        ];
        if (coordinator_can_approve_fo_delete($pdo, $coordinatorId, $target)) {
            $out[] = $row;
        }
    }

    return $out;
}

function staff_pending_removal_count_for_coordinator(PDO $pdo, int $coordinatorId): int {
    return count(staff_pending_removal_rows_for_coordinator($pdo, $coordinatorId));
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
