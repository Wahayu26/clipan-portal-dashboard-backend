<?php

declare(strict_types=1);

namespace PortalBackend\Middleware;

use PortalBackend\Database;
use PortalBackend\Router;
use PDO;

final class AuthMiddleware
{
    public static ?array $currentUser = null;

    public function handle(array &$requestData, array $params): ?bool
    {
        $token = self::extractToken();

        if ($token === null) {
            Router::json([
                'error' => true,
                'message' => 'Unauthenticated. Sesi login diperlukan untuk mengakses endpoint ini.',
            ], 401);
            return false;
        }

        $tokenHash = hash('sha256', $token);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.id as session_id, s.expires_at, s.is_remember,
                   u.id, u.username, u.full_name, u.email, u.role, u.is_active
            FROM portal_sessions s
            INNER JOIN portal_users u ON u.id = s.user_id
            WHERE s.token_hash = :token_hash
            LIMIT 1
        ");
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            Router::json([
                'error' => true,
                'message' => 'Token tidak valid atau sesi telah berakhir.',
            ], 401);
            return false;
        }

        if ((int)$row['is_active'] !== 1) {
            Router::json([
                'error' => true,
                'message' => 'User ini dinonaktifkan oleh Administrator.',
            ], 403);
            return false;
        }

        if (strtotime($row['expires_at']) < time()) {
            // Delete expired session
            $delStmt = $pdo->prepare("DELETE FROM portal_sessions WHERE id = :id");
            $delStmt->execute(['id' => $row['session_id']]);

            Router::json([
                'error' => true,
                'message' => 'Sesi login telah kedaluwarsa. Silakan login kembali.',
            ], 401);
            return false;
        }

        self::$currentUser = [
            'id' => (int)$row['id'],
            'username' => (string)$row['username'],
            'full_name' => (string)$row['full_name'],
            'email' => (string)($row['email'] ?? ''),
            'role' => (string)$row['role'],
            'session_id' => (int)$row['session_id'],
        ];

        return null;
    }

    public static function extractToken(): ?string
    {
        // 1. Check Authorization Header: Bearer <token>
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // 2. Check Cookie portal_token or portal_session
        if (!empty($_COOKIE['portal_token'])) {
            return $_COOKIE['portal_token'];
        }

        return null;
    }

    public static function user(): ?array
    {
        return self::$currentUser;
    }
}
