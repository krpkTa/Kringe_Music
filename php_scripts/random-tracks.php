<?php
// api/random-tracks.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Подключаем необходимые файлы
require_once 'music_queries.php';
require_once 'db_connect.php';

// Получаем лимит из GET-параметра (по умолчанию 15)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$limit = min($limit, 50); // Максимальный лимит - 50 треков

try {
    // Получаем соединение с базой данных
    $pdo = getPDOConnection();
    
    // Используем существующую функцию getRandomReleases, но увеличиваем лимит
    $tracks = getRandomTracks($pdo, $limit);
    
    if (!$tracks || empty($tracks)) {
        // Если случайных треков нет, попробуем получить просто все треки с лимитом
        $tracks = getAllTracks($pdo, true, $limit);
    }
    
    // Форматируем данные для ответа
    $formattedTracks = [];
    foreach ($tracks as $track) {
        $duration = isset($track['duration']) ? $track['duration'] : 0;
        
        // Определяем обложку
        $cover = 'images/default-track.jpg';
        if (!empty($track['img_url'])) {
            $cover = $track['img_url'];
        } elseif (!empty($track['cover'])) {
            $cover = $track['cover'];
        } elseif (!empty($track['artist_image'])) {
            $cover = $track['artist_image'];
        }
        
        $formattedTracks[] = [
            'id' => $track['id'],
            'title' => $track['title'] ?? 'Без названия',
            'artist' => $track['artist_name'] ?? ($track['artist'] ?? 'Неизвестный исполнитель'),
            'duration' => $duration,
            'cover' => $cover,
            'album' => $track['album_id'] ? 'Альбом #' . $track['album_id'] : 'Без альбома',
            'genre' => $track['artist_genre'] ?? $track['genre'] ?? 'Не указан',
            'track_url' => $track['track_url'] ?? '',
            'artist_id' => $track['artist_id'] ?? null
        ];
    }
    
    // Если треков все равно нет, возвращаем пустой массив
    if (empty($formattedTracks)) {
        echo json_encode([
            'success' => true,
            'tracks' => [],
            'count' => 0,
            'date' => date('Y-m-d'),
            'message' => 'В базе данных нет треков'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'tracks' => $formattedTracks,
        'count' => count($formattedTracks),
        'date' => date('Y-m-d'),
        'message' => 'Плейлист дня успешно загружен'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сервера: ' . $e->getMessage(),
        'tracks' => []
    ]);
}