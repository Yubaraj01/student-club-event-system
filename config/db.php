<?php
$config = require __DIR__ . '/config.php';

$host = $config['db']['host'];
$db   = $config['db']['name'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In a real app you might log errors. For assignment clarity we show a brief message.
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
