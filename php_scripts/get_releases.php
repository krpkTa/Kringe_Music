<?php
// api/get_releases.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Разрешаем доступ с любого домена

// Подключаемся к базе данных
require_once '../config/database.php';
require_once '../php_scripts/music_queries.php';

$response = [
    'success' => false,
    'releases' => [],
    'error' => null
];

try {
    if (!$pdo) {
        throw new Exception('Нет подключения к базе данных');
    }
    
    // Проверяем, есть ли данные в базе
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tracks");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $response['message'] = 'Нет треков в базе данных';
        echo json_encode($response);
        exit;
    }
    
    // Получаем случайные треки как релизы
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3;
    $stmt = $pdo->prepare("
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
                ELSE 'Сингл'
            END as release_type
        FROM tracks t
        LEFT JOIN artist a ON t.artist_id = a.id
        ORDER BY RANDOM()
        LIMIT :limit
    ");
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем данные для ответа
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
            'track_url' => $track['track_url'],
            'artist_id' => $track['artist_id'],
            'album_id' => $track['album_id']
        ];
    }
    
    $response['success'] = true;
    $response['releases'] = $formattedReleases;
    
} catch (PDOException $e) {
    $response['error'] = 'Ошибка базы данных: ' . $e->getMessage();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>