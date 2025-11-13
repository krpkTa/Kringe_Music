<?php
$host = "localhost";
$port = "5432";
$dbname = "KringeMusic";
$user = "kringe_user";
$password = "kringe_music";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Вместо echo используем error_log и устанавливаем $pdo = null
    error_log("Connection to database failed: " . $e->getMessage());
    $pdo = null;
}
?>