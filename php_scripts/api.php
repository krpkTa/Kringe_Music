<?php
// php_scripts/api.php

// ВАЖНО: Устанавливаем кодировку PHP в UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once 'db_connect.php';
require_once 'music_queries.php';

// Установка заголовков (с явным указанием UTF-8)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Получение действия из запроса
$action = $_GET['action'] ?? '';

try {
    // Проверка подключения к БД
    if ($pdo === null) {
        throw new Exception('Database connection failed');
    }
    
    switch ($action) {
        case 'getAllTracks':
            $tracks = getAllTracks($pdo, true);
            
            // Нормализуем пути для каждого трека
            $rootDir = dirname(__DIR__); // Корневая директория проекта
            
            foreach ($tracks as &$track) {
                // Убираем возможные кавычки
                $track['track_url'] = trim($track['track_url'], '"\'');
                $track['img_url'] = trim($track['img_url'], '"\'');
                
                // Проверяем существование файлов
                $trackPath = $rootDir . '/' . $track['track_url'];
                $imagePath = $rootDir . '/' . $track['img_url'];
                
                if (!file_exists($trackPath)) {
                    error_log("WARNING: Track file not found: " . $trackPath);
                }
                
                if (!file_exists($imagePath)) {
                    error_log("WARNING: Image file not found: " . $imagePath);
                    $track['img_url'] = 'images/default-cover.jpg';
                }
            }
            
            echo json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'getTrackById':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid track ID');
            }
            
            $track = getTrackById($pdo, $id, true);
            
            if ($track === null) {
                http_response_code(404);
                echo json_encode(['error' => 'Track not found'], JSON_UNESCAPED_UNICODE);
            } else {
                $track['track_url'] = trim($track['track_url'], '"\'');
                $track['img_url'] = trim($track['img_url'], '"\'');
                echo json_encode($track, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
            
        case 'getTracksByArtist':
            $artistId = intval($_GET['artist_id'] ?? 0);
            $tracks = getTracksByArtist($pdo, $artistId);
            echo json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'searchTracks':
            $searchTerm = $_GET['search'] ?? '';
            $tracks = searchTracks($pdo, $searchTerm);
            echo json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'getPopularTracks':
            $limit = intval($_GET['limit'] ?? 10);
            $tracks = getPopularTracks($pdo, $limit);
            echo json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Unknown action',
                'available_actions' => [
                    'getAllTracks', 
                    'getTrackById', 
                    'getTracksByArtist', 
                    'searchTracks',
                    'getPopularTracks'
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    error_log("API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>