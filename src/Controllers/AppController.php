<?php

declare(strict_types=1);

namespace PortalBackend\Controllers;

use PortalBackend\Database;
use PortalBackend\Router;
use PortalBackend\Middleware\AuthMiddleware;
use PDO;

final class AppController
{
    public function index(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT * FROM portal_apps 
            ORDER BY is_favorite DESC, launch_count DESC, id ASC
        ");
        $rows = $stmt->fetchAll();

        $apps = array_map(function ($row) {
            return [
                'id' => $row['app_id'],
                'db_id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'url' => $row['url'],
                'category' => $row['category'],
                'iconName' => $row['icon_name'],
                'badge' => $row['badge'] ?: undefined,
                'accentColor' => $row['accent_color'],
                'bgGradient' => $row['bg_gradient'],
                'status' => $row['status'],
                'isFavorite' => (bool)$row['is_favorite'],
                'isCustom' => (bool)$row['is_custom'],
                'launchCount' => (int)$row['launch_count'],
                'lastOpenedAt' => $row['last_opened_at'] ? (int)$row['last_opened_at'] : null,
            ];
        }, $rows);

        Router::json([
            'success' => true,
            'count' => count($apps),
            'data' => $apps,
        ]);
    }

    public function store(array $data): void
    {
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $url = trim((string)($data['url'] ?? ''));
        $category = (string)($data['category'] ?? 'other');
        $iconName = (string)($data['iconName'] ?? 'Globe');
        $accentColor = (string)($data['accentColor'] ?? '#6366f1');

        if ($name === '' || $url === '') {
            Router::json([
                'error' => true,
                'message' => 'Nama aplikasi dan URL wajib diisi.',
            ], 422);
            return;
        }

        $appId = 'app-custom-' . time() . '-' . bin2hex(random_bytes(2));
        $user = AuthMiddleware::user();
        $createdBy = $user ? $user['username'] : 'system';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO portal_apps 
                (app_id, name, description, url, category, icon_name, accent_color, is_custom, created_by)
            VALUES 
                (:app_id, :name, :description, :url, :category, :icon_name, :accent_color, 1, :created_by)
        ");
        $stmt->execute([
            'app_id' => $appId,
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'category' => $category,
            'icon_name' => $iconName,
            'accent_color' => $accentColor,
            'created_by' => $createdBy,
        ]);

        Router::json([
            'success' => true,
            'message' => "Aplikasi '{$name}' berhasil ditambahkan ke Portal.",
            'app_id' => $appId,
        ], 201);
    }

    public function toggleFavorite(array $data, array $params): void
    {
        $appId = $params['id'] ?? $data['id'] ?? '';
        if ($appId === '') {
            Router::json(['error' => true, 'message' => 'ID aplikasi tidak diberikan.'], 400);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE portal_apps 
            SET is_favorite = IF(is_favorite = 1, 0, 1) 
            WHERE app_id = :id OR id = :id_num
        ");
        $stmt->execute([
            'id' => $appId,
            'id_num' => is_numeric($appId) ? (int)$appId : 0,
        ]);

        Router::json([
            'success' => true,
            'message' => 'Status favorit aplikasi berhasil diperbarui.',
        ]);
    }

    public function launch(array $data, array $params): void
    {
        $appId = $params['id'] ?? $data['id'] ?? '';
        if ($appId === '') {
            Router::json(['error' => true, 'message' => 'ID aplikasi tidak diberikan.'], 400);
            return;
        }

        $nowMs = (int)(microtime(true) * 1000);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            UPDATE portal_apps 
            SET launch_count = launch_count + 1, last_opened_at = :last_opened 
            WHERE app_id = :id OR id = :id_num
        ");
        $stmt->execute([
            'last_opened' => $nowMs,
            'id' => $appId,
            'id_num' => is_numeric($appId) ? (int)$appId : 0,
        ]);

        // Audit log
        $user = AuthMiddleware::user();
        if ($user) {
            $audit = $pdo->prepare("
                INSERT INTO portal_audit_logs (user_id, username, action, description, ip_address, status)
                VALUES (:uid, :uname, 'APP_LAUNCH', :desc, :ip, 'SUCCESS')
            ");
            $audit->execute([
                'uid' => $user['id'],
                'uname' => $user['username'],
                'desc' => "Membuka aplikasi: {$appId}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        }

        Router::json([
            'success' => true,
            'timestamp' => $nowMs,
        ]);
    }

    public function delete(array $data, array $params): void
    {
        $appId = $params['id'] ?? $data['id'] ?? '';
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("DELETE FROM portal_apps WHERE app_id = :id AND is_custom = 1");
        $stmt->execute(['id' => $appId]);

        if ($stmt->rowCount() > 0) {
            Router::json(['success' => true, 'message' => 'Aplikasi kustom berhasil dihapus.']);
        } else {
            Router::json(['error' => true, 'message' => 'Aplikasi bawaan sistem tidak dapat dihapus.'], 403);
        }
    }
}
