<?php
$host = 'db';
$db = 'myDB';
$user = 'user';
$pass = '1234';
$char = 'utf8';

$conn = "mysql:host=$host;dbname=$db;charset=$char";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($conn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
