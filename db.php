<?php
$host = '127.0.0.1';
$db   = 'web_dev';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,  // Real prepared statements
    PDO::ATTR_STRINGIFY_FETCHES => false, // Return proper types
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log error instead of displaying it in production
    error_log('Database connection failed: ' . $e->getMessage());
    die('Unable to connect to database. Please try again later.');
}