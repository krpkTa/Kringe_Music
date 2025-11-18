<?php
$host = "localhost";
$port = "5432";
$dbname = "KringeMusic";
$user = "kringe_user";
$password = "kringe_music";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создание таблиц, если они не существуют
    createTablesIfNotExist($pdo);
    
} catch(PDOException $e) {
    error_log("Connection to database failed: " . $e->getMessage());
    $pdo = null;
}

function createTablesIfNotExist($pdo) {
    // Создание таблицы artist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS artist (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            genre VARCHAR(50),
            image VARCHAR(255)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            login VARCHAR(50) PRIMARY KEY,
            password VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE
        )
    ");
    
    // Создание таблицы playlist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS playlist (
            id SERIAL PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            is_public BOOLEAN DEFAULT false,
            FOREIGN KEY (user_id) REFERENCES users(login) ON DELETE CASCADE
        )
    ");
    
    // Создание таблицы tracks
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tracks (
            id SERIAL PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            duration INTEGER NOT NULL,
            album_id INTEGER,
            artist_id INTEGER NOT NULL,
            img_url VARCHAR(255),
            track_url VARCHAR(255) NOT NULL,
            FOREIGN KEY (artist_id) REFERENCES artist(id) ON DELETE CASCADE
        )
    ");
    
    // Создание таблицы playlist_tracks
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS playlist_tracks (
            playlist_id INTEGER NOT NULL,
            track_id INTEGER NOT NULL,
            position INTEGER NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (playlist_id, track_id),
            FOREIGN KEY (playlist_id) REFERENCES playlist(id) ON DELETE CASCADE,
            FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
        )
    ");
    
    error_log("Database tables checked/created successfully");
}
?>