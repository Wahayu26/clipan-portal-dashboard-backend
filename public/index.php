<?php

declare(strict_types=1);

// Clipan Portal Backend - Front Controller

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/Router.php';
require_once dirname(__DIR__) . '/src/Middleware/RateLimitMiddleware.php';
require_once dirname(__DIR__) . '/src/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/src/Controllers/AppController.php';
require_once dirname(__DIR__) . '/src/Controllers/ActivityController.php';
require_once dirname(__DIR__) . '/src/Controllers/HealthController.php';

use PortalBackend\Config;
use PortalBackend\Router;
use PortalBackend\Middleware\RateLimitMiddleware;
use PortalBackend\Middleware\AuthMiddleware;
use PortalBackend\Controllers\AuthController;
use PortalBackend\Controllers\AppController;
use PortalBackend\Controllers\ActivityController;
use PortalBackend\Controllers\HealthController;

Config::load(dirname(__DIR__) . '/.env');

$router = new Router();

// ==========================================
// 1. HEALTH & SYSTEM STATUS
// ==========================================
$router->get('/api/health', [HealthController::class, 'check']);
$router->get('/api/portal/health', [HealthController::class, 'check']);
$router->get('/health', [HealthController::class, 'check']);

// ==========================================
// 2. AUTHENTICATION & SESSION MANAGEMENT
// ==========================================
// Login with anti brute-force rate limit protection
$router->post('/api/login', [AuthController::class, 'login'], [RateLimitMiddleware::class]);
$router->post('/api/auth/login', [AuthController::class, 'login'], [RateLimitMiddleware::class]);
$router->post('/api/portal/login', [AuthController::class, 'login'], [RateLimitMiddleware::class]);
$router->post('/api/portal/auth/login', [AuthController::class, 'login'], [RateLimitMiddleware::class]);

// Logout & Terminate Session
$router->post('/api/logout', [AuthController::class, 'logout']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->post('/api/portal/logout', [AuthController::class, 'logout']);

// Current Authenticated User Profile
$router->get('/api/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
$router->get('/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
$router->get('/api/portal/me', [AuthController::class, 'me'], [AuthMiddleware::class]);

// Fast session validation check for frontend background timer
$router->get('/api/verify-session', [AuthController::class, 'verifySession'], [AuthMiddleware::class]);
$router->get('/api/auth/verify', [AuthController::class, 'verifySession'], [AuthMiddleware::class]);
$router->get('/api/portal/verify-session', [AuthController::class, 'verifySession'], [AuthMiddleware::class]);

// ==========================================
// 3. APPLICATIONS REGISTRY & LAUNCH COUNTER
// ==========================================
// List all applications
$router->get('/api/apps', [AppController::class, 'index']);
$router->get('/api/portal/apps', [AppController::class, 'index']);

// List globally deleted applications
$router->get('/api/portal/deleted-apps', [AppController::class, 'getDeletedApps']);
$router->get('/api/deleted-apps', [AppController::class, 'getDeletedApps']);

// Reset deleted applications
$router->post('/api/portal/deleted-apps/reset', [AppController::class, 'resetDeletedApps']);
$router->post('/api/deleted-apps/reset', [AppController::class, 'resetDeletedApps']);

// Add new custom application
$router->post('/api/apps', [AppController::class, 'store']);
$router->post('/api/portal/apps', [AppController::class, 'store']);

// Toggle Favorite Pin
$router->post('/api/apps/:id/favorite', [AppController::class, 'toggleFavorite']);
$router->post('/api/apps/{id}/favorite', [AppController::class, 'toggleFavorite']);
$router->post('/api/portal/apps/:id/favorite', [AppController::class, 'toggleFavorite']);
$router->post('/api/portal/apps/{id}/favorite', [AppController::class, 'toggleFavorite']);

// Launch telemetry counter & timestamp updater
$router->post('/api/apps/:id/launch', [AppController::class, 'launch']);
$router->post('/api/apps/{id}/launch', [AppController::class, 'launch']);
$router->post('/api/portal/apps/:id/launch', [AppController::class, 'launch']);
$router->post('/api/portal/apps/{id}/launch', [AppController::class, 'launch']);

// Delete application (supports DELETE and POST .../delete)
$router->delete('/api/apps/:id', [AppController::class, 'delete']);
$router->delete('/api/apps/{id}', [AppController::class, 'delete']);
$router->post('/api/apps/:id/delete', [AppController::class, 'delete']);
$router->post('/api/apps/{id}/delete', [AppController::class, 'delete']);
$router->delete('/api/portal/apps/:id', [AppController::class, 'delete']);
$router->delete('/api/portal/apps/{id}', [AppController::class, 'delete']);
$router->post('/api/portal/apps/:id/delete', [AppController::class, 'delete']);
$router->post('/api/portal/apps/{id}/delete', [AppController::class, 'delete']);

// ==========================================
// 4. ACTIVITY LOGS & AUDIT
// ==========================================
$router->get('/api/activities', [ActivityController::class, 'index']);
$router->get('/api/portal/activities', [ActivityController::class, 'index']);

// Dispatch Request
$router->dispatch();
