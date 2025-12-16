<?php
// php_scripts/music_api.php

require_once 'db_connect.php';
require_once 'music_queries.php';

// Функция для установки заголовков API
function setApiHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Обработка preflight запросов
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Обработчик для получения релизов
function handleReleases() {
    setApiHeaders();
    
    $response = [
        'success' => false,
        'releases' => [],
        'error' => null
    ];
    
    try {
        if (!$GLOBALS['pdo']) {
            throw new Exception('Нет подключения к базе данных');
        }
        
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3;
        
        // Используем упрощенную версию получения релизов
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url as cover,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image,
                CASE 
                    WHEN t.album_id IS NOT NULL THEN 'Альбом'
                    ELSE 'Трек'
                END as release_type
            FROM tracks t
            LEFT JOIN artist a ON t.artist_id = a.id
            ORDER BY RANDOM()
            LIMIT :limit
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tracks)) {
            $response['message'] = 'Нет треков в базе данных';
        } else {
            $formattedReleases = [];
            
            foreach ($tracks as $track) {
                $formattedReleases[] = [
                    'id' => $track['id'],
                    'title' => htmlspecialchars($track['title']),
                    'artist' => htmlspecialchars($track['artist_name'] ?? 'Неизвестный исполнитель'),
                    'cover' => !empty($track['cover']) 
                        ? $track['cover'] 
                        : (!empty($track['artist_image']) 
                            ? $track['artist_image'] 
                            : 'images/default-album.jpg'),
                    'type' => $track['release_type'],
                    'date' => date('d.m.Y', strtotime('-'.rand(0, 30).' days')),
                    'duration' => gmdate("i:s", $track['duration']),
                    'genre' => htmlspecialchars($track['artist_genre'] ?? 'Разнообразный'),
                    'track_url' => $track['track_url']
                ];
            }
            
            $response['success'] = true;
            $response['releases'] = $formattedReleases;
        }
        
    } catch (PDOException $e) {
        $response['error'] = 'Ошибка базы данных: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

// Обработчик для получения всех треков
function handleTracks() {
    setApiHeaders();
    
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $popular = isset($_GET['popular']) ? $_GET['popular'] === 'true' : false;
        
        if ($popular) {
            $tracks = getPopularTracks($GLOBALS['pdo'], $limit);
        } else {
            $tracks = getAllTracks($GLOBALS['pdo'], true);
            $tracks = array_slice($tracks, 0, $limit);
        }
        
        echo json_encode([
            'success' => true,
            'tracks' => $tracks
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Обработчик для получения трека по ID
function handleTrack($id) {
    setApiHeaders();
    
    try {
        $track = getTrackById($GLOBALS['pdo'], intval($id), true);
        
        if ($track) {
            echo json_encode([
                'success' => true,
                'track' => $track
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Трек не найден'
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Обработчик для получения всех артистов
function handleArtists() {
    setApiHeaders();
    
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $artists = getAllArtists($GLOBALS['pdo']);
        $artists = array_slice($artists, 0, $limit);
        
        echo json_encode([
            'success' => true,
            'artists' => $artists
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Обработчик для получения артиста по ID
function handleArtist($id) {
    setApiHeaders();
    
    try {
        $artist = getArtistById($GLOBALS['pdo'], intval($id));
        
        if ($artist) {
            $tracks = getTracksByArtist($GLOBALS['pdo'], intval($id));
            
            echo json_encode([
                'success' => true,
                'artist' => $artist,
                'tracks' => $tracks
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Исполнитель не найден'
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Обработчик для поиска
function handleSearch() {
    setApiHeaders();
    
    try {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            echo json_encode([
                'success' => true,
                'results' => []
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $tracks = searchTracks($GLOBALS['pdo'], $query);
        
        echo json_encode([
            'success' => true,
            'query' => $query,
            'results' => $tracks
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>