<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ENV', 'testing');
define('APP_URL', 'http://localhost');
define('APP_NAME', 'BulkReplace Test');
define('CRON_AUTH_KEY', 'test_cron_key');

function db()
{
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO('sqlite::memory:');
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $conn->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT UNIQUE,
                password TEXT,
                plan TEXT DEFAULT 'free',
                email_verified INTEGER DEFAULT 0,
                email_verified_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $conn->exec("CREATE TABLE IF NOT EXISTS licenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                license_key TEXT UNIQUE,
                product_id TEXT,
                product_slug TEXT,
                sale_id TEXT,
                email TEXT,
                user_id INTEGER,
                status TEXT DEFAULT 'inactive',
                activated_at DATETIME,
                expires_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $conn->exec("CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                identifier TEXT,
                endpoint TEXT,
                attempts INTEGER DEFAULT 0,
                window_start INTEGER,
                blocked_until INTEGER
            )");

        } catch (Exception $e) {
            die('Test database connection failed: ' . $e->getMessage());
        }
    }

    return $conn;
}

if (!function_exists('startSession')) {
    function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
