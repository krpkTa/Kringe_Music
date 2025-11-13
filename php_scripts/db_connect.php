<?php
function getDatabaseConnection() {
    // Получаем строку подключения из переменных окружения Render
    $databaseUrl = getenv('DATABASE_URL');
    
    if ($databaseUrl) {
        // Парсим URL подключения от Render
        $url = parse_url($databaseUrl);
        
        $host = $url['host'];
        $port = $url['port'];
        $dbname = ltrim($url['path'], '/');
        $user = $url['user'];
        $password = $url['pass'];
    } else {
        // Локальные настройки для разработки
        $host = "localhost";
        $port = "5432";
        $dbname = "KringeMusic";
        $user = "kringe_user";
        $password = "kringe_music";
    }

    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Connection to database failed: " . $e->getMessage());
        return null;
    }
}

// Использование:
$pdo = getDatabaseConnection();
?>