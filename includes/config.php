<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

$databaseHost = getenv('DB_HOST') ?: '127.0.0.1';
$databaseName = getenv('DB_NAME') ?: 'carrental';
$databaseUser = getenv('DB_USER') ?: 'car_rental_app';
$databasePassword = getenv('DB_PASSWORD');
$dbh = null;
$databaseError = null;

if ($databasePassword === false || $databasePassword === '') {
    $databaseError = 'Database credentials are not configured.';
    error_log($databaseError);
} else {
    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $databaseHost,
            $databaseName
        );
        $dbh = new PDO($dsn, $databaseUser, $databasePassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        $databaseError = 'The vehicle database is temporarily unavailable.';
        error_log('Car rental database connection failed: ' . $exception->getMessage());
    }
}

if ($dbh === null) {
    http_response_code(503);
}
