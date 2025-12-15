<?php


$host = "localhost";
$port = "5432";
$dbname = "KringeMusic";
$user = "kringe_user";
$password = "kringe_music";

$pdo = null;

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $pdo->exec("SET CLIENT_ENCODING TO 'UTF8'");
    $pdo->exec("SET NAMES 'UTF8'");
    
    createTablesIfNotExist($pdo);
    
} catch(PDOException $e) {
    error_log("Connection to database failed: " . $e->getMessage());
    $pdo = null;
}

function createTablesIfNotExist($pdo) {
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            login VARCHAR(50) PRIMARY KEY,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS artist (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            genre VARCHAR(50),
            image VARCHAR(255)
        )
    ");
    
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS playlist (
            id SERIAL PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            is_public BOOLEAN DEFAULT false,
            FOREIGN KEY (user_id) REFERENCES users(login) ON DELETE CASCADE
        )
    ");
    
    
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

    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS feedback (
            id SERIAL PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT
        )
    ");
}