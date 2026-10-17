<?php

declare(strict_types=1);

namespace PortalBackend;

use Throwable;

final class Router
{
    private array $routes = [];
    private array $middleware = [];

    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->compilePattern($path),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function dispatch(): void
    {
        $this->handleCors();

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($requestMethod === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Strip subfolder prefix if backend is run inside a subpath
        $basePath = Config::get('BASE_PATH', '');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
        }

        // Parse JSON body if present
        $body = [];
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }
        $requestData = array_merge($_GET, $_POST, $body);

        $matched = false;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $matched = true;

                // Extract named params
                $params = array_filter($matches, '\is_string', ARRAY_FILTER_USE_KEY);

                try {
                    // Run middlewares
                    foreach ($route['middleware'] as $mw) {
                        $mwInstance = is_string($mw) ? new $mw() : $mw;
                        $mwResult = $mwInstance->handle($requestData, $params);
                        if ($mwResult !== null) {
                            return; // Middleware aborted and sent response
                        }
                    }

                    // Run handler
                    $handler = $route['handler'];
                    if (is_array($handler)) {
                        [$class, $action] = $handler;
                        $controller = is_string($class) ? new $class() : $class;
                        $controller->$action($requestData, $params);
                    } else {
                        $handler($requestData, $params);
                    }
                    return;
                } catch (Throwable $e) {
                    self::json([
                        'error' => true,
                        'message' => $e->getMessage(),
                        'code' => $e->getCode() ?: 500,
                    ], 500);
                    return;
                }
            }
        }

        if (!$matched) {
            self::json([
                'error' => true,
                'message' => "Endpoint not found: {$requestMethod} {$uri}",
            ], 404);
        }
    }

    private function handleCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowedOrigins = explode(',', (string)Config::get('CORS_ALLOWED_ORIGINS', '*'));

        $originToSet = '*';
        if (in_array('*', $allowedOrigins, true)) {
            $originToSet = $origin;
        } elseif (in_array($origin, $allowedOrigins, true)) {
            $originToSet = $origin;
        }

        header("Access-Control-Allow-Origin: {$originToSet}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');
    }

    private function compilePattern(string $path): string
    {
        // Convert :param or {param} into regex capture groups
        $pattern = preg_replace('/:[a-zA-Z0-9_]+/', '(?P<$0>[^/]+)', $path);
        $pattern = str_replace(':', '', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . rtrim($pattern, '/') . '/?$#';
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
