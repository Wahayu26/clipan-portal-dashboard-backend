<?php

declare(strict_types=1);

namespace PortalBackend;

final class Config
{
    private static array $env = [];
    private static bool $loaded = false;

    public static function load(?string $filePath = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $filePath ?? dirname(__DIR__) . '/.env';

        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $val = trim($parts[1]);
                    // Strip surrounding quotes
                    if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                        (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                        $val = substr($val, 1, -1);
                    }
                    self::$env[$key] = $val;
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return $envVal;
        }

        return $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int)self::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key, $default);
        if (is_bool($val)) {
            return $val;
        }
        return in_array(strtolower((string)$val), ['true', '1', 'yes', 'on'], true);
    }
}
