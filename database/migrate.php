<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';

use PortalBackend\Config;
use PortalBackend\Database;

echo "=== Running Clipan Portal Database Migration ===\n";

try {
    Config::load(dirname(__DIR__) . '/.env');

    $host = Config::get('DB_HOST', '127.0.0.1');
    $port = Config::getInt('DB_PORT', 3306);
    $database = Config::get('DB_DATABASE', 'soc_dashboard');
    $username = Config::get('DB_USERNAME', 'root');
    $password = Config::get('DB_PASSWORD', '');

    echo "[1/3] Checking MySQL connection to {$host}:{$port}...\n";
    $rootPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "[2/3] Ensuring database '{$database}' exists...\n";
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    $pdo = Database::getConnection();

    echo "[3/3] Executing schema.sql DDL...\n";
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException("Could not read schema.sql");
    }

    $pdo->exec($sql);

    echo "SUCCESS: All portal_* tables (portal_users, portal_apps, portal_sessions, portal_audit_logs) are ready!\n";
} catch (\Throwable $e) {
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}
