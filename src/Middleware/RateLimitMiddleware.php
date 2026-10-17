<?php

declare(strict_types=1);

namespace PortalBackend\Middleware;

use PortalBackend\Database;
use PortalBackend\Router;
use PDO;

final class RateLimitMiddleware
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    public function handle(array &$requestData, array $params): ?bool
    {
        $username = trim((string)($requestData['username'] ?? ''));
        if ($username === '') {
            return null; // Let controller handle missing username
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT failed_attempts, locked_until 
            FROM portal_users 
            WHERE username = :username 
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && !empty($user['locked_until'])) {
            $lockedUntil = strtotime($user['locked_until']);
            $now = time();

            if ($lockedUntil > $now) {
                $secondsRemaining = $lockedUntil - $now;
                Router::json([
                    'error' => true,
                    'locked' => true,
                    'seconds_remaining' => $secondsRemaining,
                    'message' => "Akun dikunci sementara karena terlalu banyak percobaan login gagal. Coba lagi dalam {$secondsRemaining} detik.",
                ], 429);
                return false;
            }
        }

        return null;
    }
}
