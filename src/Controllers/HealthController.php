<?php

declare(strict_types=1);

namespace PortalBackend\Controllers;

use PortalBackend\Database;
use PortalBackend\Router;
use PDO;
use Throwable;

final class HealthController
{
    public function check(): void
    {
        $dbStatus = 'ok';
        $dbLatency = 0.0;

        try {
            $t0 = microtime(true);
            $pdo = Database::getConnection();
            $pdo->query("SELECT 1");
            $dbLatency = round((microtime(true) - $t0) * 1000, 2);
        } catch (Throwable $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        Router::json([
            'status' => $dbStatus === 'ok' ? 'HEALTHY' : 'DEGRADED',
            'service' => 'Clipan Portal Backend API',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'database' => [
                'status' => $dbStatus,
                'latency_ms' => $dbLatency,
            ],
            'server_time' => date('c'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ], $dbStatus === 'ok' ? 200 : 503);
    }
}
