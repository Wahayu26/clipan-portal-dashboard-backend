<?php

declare(strict_types=1);

namespace PortalBackend\Controllers;

use PortalBackend\Database;
use PortalBackend\Router;
use PDO;

final class ActivityController
{
    public function index(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT a.id, a.user_id, a.username, a.action, a.description, a.created_at,
                   p.name as app_name, p.category, p.icon_name, p.accent_color
            FROM portal_audit_logs a
            LEFT JOIN portal_apps p ON a.description LIKE CONCAT('%', p.app_id, '%')
            ORDER BY a.id DESC 
            LIMIT 15
        ");
        $rows = $stmt->fetchAll();

        $activities = array_map(function ($row) {
            return [
                'id' => 'act-' . $row['id'],
                'username' => $row['username'],
                'action' => $row['action'],
                'description' => $row['description'],
                'appName' => $row['app_name'] ?? $row['action'],
                'category' => $row['category'] ?? 'security',
                'iconName' => $row['icon_name'] ?? 'Activity',
                'accentColor' => $row['accent_color'] ?? '#06b6d4',
                'timestamp' => strtotime($row['created_at']) * 1000,
                'createdAt' => $row['created_at'],
            ];
        }, $rows);

        Router::json([
            'success' => true,
            'count' => count($activities),
            'data' => $activities,
        ]);
    }
}
