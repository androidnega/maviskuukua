#!/usr/bin/env php
<?php
/**
 * One-off maintenance: delete all rows from audit_logs (admin activity trail).
 * Run from project root: php scripts/clear_audit_logs.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/config.php';

$pdo = db();
$before = (int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
$pdo->exec('DELETE FROM audit_logs');
$after = (int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

echo "audit_logs: removed {$before} row(s); remaining {$after}.\n";
echo 'Database: ' . DB_PATH . "\n";
