<?php

declare(strict_types=1);

namespace PortalBackend\Controllers;

use PortalBackend\Database;
use PortalBackend\Router;
use PortalBackend\Middleware\AuthMiddleware;
use PDO;

final class AuthController
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;
    private const SESSION_DURATION_HOURS = 12;
    private const REMEMBER_DURATION_DAYS = 7;

    public function login(array $data): void
    {
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $remember = !empty($data['remember_me']);

        if ($username === '' || $password === '') {
            Router::json([
                'error' => true,
                'message' => 'Username dan password wajib diisi.',
            ], 422);
            return;
        }

        $pdo = Database::getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // 1. Fetch user from portal_users
        $stmt = $pdo->prepare("
            SELECT * FROM portal_users 
            WHERE username = :username 
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->logAudit(null, $username, 'LOGIN_FAILED', 'User tidak ditemukan', $ip, $userAgent, 'FAILED');
            Router::json([
                'error' => true,
                'message' => 'Kredensial tidak valid. Username atau password salah.',
            ], 401);
            return;
        }

        // 2. Check if locked out
        if (!empty($user['locked_until'])) {
            $lockedUntil = strtotime($user['locked_until']);
            $now = time();
            if ($lockedUntil > $now) {
                $seconds = $lockedUntil - $now;
                $this->logAudit((int)$user['id'], $username, 'LOGIN_BLOCKED', "Akses terblokir lockout ({$seconds}s remaining)", $ip, $userAgent, 'BLOCKED');
                Router::json([
                    'error' => true,
                    'locked' => true,
                    'seconds_remaining' => $seconds,
                    'message' => "Terlalu banyak percobaan gagal. Akun dikunci sementara. Coba lagi dalam {$seconds} detik.",
                ], 429);
                return;
            }
        }

        // 3. Verify Active status
        if ((int)$user['is_active'] !== 1) {
            Router::json([
                'error' => true,
                'message' => 'Akun dinonaktifkan. Hubungi SOC Administrator.',
            ], 403);
            return;
        }

        // 4. Verify Password
        if (!password_verify($password, $user['password_hash'])) {
            $newAttempts = (int)$user['failed_attempts'] + 1;
            $lockedUntil = null;
            $isLocked = false;
            $secondsRemaining = 0;

            if ($newAttempts >= self::MAX_FAILED_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_SECONDS);
                $isLocked = true;
                $secondsRemaining = self::LOCKOUT_SECONDS;
            }

            $updateStmt = $pdo->prepare("
                UPDATE portal_users 
                SET failed_attempts = :attempts, locked_until = :locked_until 
                WHERE id = :id
            ");
            $updateStmt->execute([
                'attempts' => $newAttempts,
                'locked_until' => $lockedUntil,
                'id' => $user['id'],
            ]);

            $attemptsLeft = max(0, self::MAX_FAILED_ATTEMPTS - $newAttempts);
            $this->logAudit((int)$user['id'], $username, 'LOGIN_FAILED', "Password salah (Attempt {$newAttempts})", $ip, $userAgent, 'FAILED');

            if ($isLocked) {
                Router::json([
                    'error' => true,
                    'locked' => true,
                    'seconds_remaining' => $secondsRemaining,
                    'message' => "Terlalu banyak percobaan login gagal berturut-turut. Akun dikunci selama {$secondsRemaining} detik.",
                ], 429);
                return;
            }

            Router::json([
                'error' => true,
                'locked' => false,
                'attempts_left' => $attemptsLeft,
                'message' => "Kredensial tidak valid. Sisa kesempatan login: {$attemptsLeft}x.",
            ], 401);
            return;
        }

        // 5. Password Correct! Reset lockout counters
        $resetStmt = $pdo->prepare("
            UPDATE portal_users 
            SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = :ip 
            WHERE id = :id
        ");
        $resetStmt->execute([
            'ip' => $ip,
            'id' => $user['id'],
        ]);

        // 6. Generate Secure Session Token
        $token = bin2hex(random_bytes(32)); // 64 hex characters
        $tokenHash = hash('sha256', $token);

        $durationSec = $remember 
            ? (self::REMEMBER_DURATION_DAYS * 86400) 
            : (self::SESSION_DURATION_HOURS * 3600);
            
        $expiresAt = date('Y-m-d H:i:s', time() + $durationSec);

        $sessionStmt = $pdo->prepare("
            INSERT INTO portal_sessions 
                (user_id, token_hash, expires_at, ip_address, user_agent, is_remember)
            VALUES 
                (:user_id, :token_hash, :expires_at, :ip_address, :user_agent, :is_remember)
        ");
        $sessionStmt->execute([
            'user_id' => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'is_remember' => $remember ? 1 : 0,
        ]);

        // Set Cookie for automatic session retention
        setcookie('portal_token', $token, [
            'expires' => time() + $durationSec,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        ]);
        
        // Also set companion soc_auth cookie for seamless Next.js SSO
        setcookie('soc_auth', '1', [
            'expires' => time() + $durationSec,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        ]);

        $this->logAudit((int)$user['id'], $username, 'LOGIN_SUCCESS', "Login berhasil ({$user['role']})", $ip, $userAgent, 'SUCCESS');

        Router::json([
            'success' => true,
            'message' => 'Autentikasi berhasil.',
            'access_token' => $token,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => (time() + $durationSec) * 1000, // JavaScript ms timestamp
            'user' => [
                'id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'name' => (string)$user['full_name'],
                'full_name' => (string)$user['full_name'],
                'email' => (string)($user['email'] ?? ''),
                'role' => (string)$user['role'],
            ],
        ]);
    }

    public function logout(array $data): void
    {
        $token = AuthMiddleware::extractToken();
        if ($token) {
            $tokenHash = hash('sha256', $token);
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM portal_sessions WHERE token_hash = :token_hash");
            $stmt->execute(['token_hash' => $tokenHash]);
        }

        // Clear cookies
        setcookie('portal_token', '', ['expires' => time() - 3600, 'path' => '/']);
        setcookie('soc_auth', '', ['expires' => time() - 3600, 'path' => '/']);

        $user = AuthMiddleware::user();
        if ($user) {
            $this->logAudit($user['id'], $user['username'], 'LOGOUT', 'User sign out', $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', 'SUCCESS');
        }

        Router::json([
            'success' => true,
            'message' => 'Berhasil sign out.',
        ]);
    }

    public function me(): void
    {
        $user = AuthMiddleware::user();
        Router::json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function verifySession(): void
    {
        $user = AuthMiddleware::user();
        Router::json([
            'success' => true,
            'valid' => true,
            'user' => $user,
        ]);
    }

    private function logAudit(?int $userId, string $username, string $action, string $desc, string $ip, string $ua, string $status): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO portal_audit_logs 
                    (user_id, username, action, description, ip_address, user_agent, status)
                VALUES 
                    (:user_id, :username, :action, :description, :ip, :ua, :status)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'username' => $username,
                'action' => $action,
                'description' => $desc,
                'ip' => $ip,
                'ua' => substr($ua, 0, 500),
                'status' => $status,
            ]);
        } catch (\Throwable) {
            // Ignore audit log failure
        }
    }
}
