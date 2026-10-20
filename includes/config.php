<?php

$databaseHost = getenv('DB_HOST') ?: 'car-rental-db';
$databaseName = getenv('DB_NAME') ?: 'carrental';
$databaseUser = getenv('DB_USER') ?: 'car_rental_app';
$databasePassword = getenv('DB_PASSWORD');

if ($databasePassword === false || $databasePassword === '') {
    error_log('Database configuration is missing DB_PASSWORD.');
    http_response_code(503);
    exit('The car rental service is temporarily unavailable. Please try again later.');
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $databaseHost,
        $databaseName
    );
    $dbh = new PDO($dsn, $databaseUser, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Car rental database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('The car rental service is temporarily unavailable. Please try again later.');
}
