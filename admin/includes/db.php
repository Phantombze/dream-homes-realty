<?php
if (!defined('DREAMHOMES_CONFIG')) {
    require_once __DIR__ . '/../../private/dh-config.php';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        $logFile = __DIR__ . '/../../../private/db-error.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND);
        throw $e;
    }

    return $pdo;
}
