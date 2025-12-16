<?php
// php_scripts/music_queries.php

/**
 * Получить всех исполнителей
 * @return array Массив исполнителей
 */
function getAllArtists($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM artist ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting artists: " . $e->getMessage());
        return [];
    }
}
// Добавьте эти функции в php_scripts/music_queries.php

/**
 * Получить случайные треки для плейлиста дня
 * @param int $limit Количество треков
 * @return array Массив треков
 */
function getDailyPlaylistTracks($pdo, $limit = 15) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image
            FROM tracks t
            LEFT JOIN artist a ON t.artist_id = a.id
            ORDER BY RANDOM()
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting daily playlist tracks: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить количество активных треков в базе
 * @return int Количество треков
 */
function getActiveTracksCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tracks WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch(PDOException $e) {
        error_log("Error getting active tracks count: " . $e->getMessage());
        return 0;
    }
}
// Добавьте эту функцию в music_queries.php после getRandomReleases

/**
 * Получить случайные треки (расширенная версия для плейлиста дня)
 * @param int $limit Количество треков
 * @return array Массив треков
 */
function getRandomTracks($pdo, $limit = 15) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image
            FROM tracks t
            LEFT JOIN artist a ON t.artist_id = a.id
            ORDER BY RANDOM()
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting random tracks: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить количество треков в базе
 * @return int Количество треков
 */
function getTracksCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tracks");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch(PDOException $e) {
        error_log("Error getting tracks count: " . $e->getMessage());
        return 0;
    }
}
/**
 * Получить исполнителя по ID
 * @param int $id ID исполнителя
 * @return array|null Данные исполнителя или null если не найден
 */
function getArtistById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM artist WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch(PDOException $e) {
        error_log("Error getting artist by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Получить всех исполнителей по жанру
 * @param string $genre Жанр
 * @return array Массив исполнителей
 */
function getArtistsByGenre($pdo, $genre) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM artist WHERE genre = ? ORDER BY name");
        $stmt->execute([$genre]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting artists by genre: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить все треки
 * @param bool $withArtistInfo Включить информацию об исполнителе
 * @param int $limit Ограничение количества записей
 * @return array Массив треков
 */
function getAllTracks($pdo, $withArtistInfo = true, $limit = null) {
    try {
        if ($withArtistInfo) {
            $sql = "SELECT 
                        t.id,
                        t.title,
                        t.duration,
                        t.album_id,
                        t.artist_id,
                        t.img_url,
                        t.track_url,
                        a.name as artist_name,
                        a.genre as artist_genre,
                        a.image as artist_image
                    FROM tracks t 
                    LEFT JOIN artist a ON t.artist_id = a.id 
                    ORDER BY t.id";
        } else {
            $sql = "SELECT * FROM tracks ORDER BY id";
        }
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting tracks: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить трек по ID
 * @param int $id ID трека
 * @param bool $withArtistInfo Включить информацию об исполнителе
 * @return array|null Данные трека или null если не найден
 */
function getTrackById($pdo, $id, $withArtistInfo = true) {
    try {
        if ($withArtistInfo) {
            $sql = "SELECT 
                        t.id,
                        t.title,
                        t.duration,
                        t.album_id,
                        t.artist_id,
                        t.img_url,
                        t.track_url,
                        a.name as artist_name,
                        a.genre as artist_genre,
                        a.image as artist_image
                    FROM tracks t 
                    LEFT JOIN artist a ON t.artist_id = a.id 
                    WHERE t.id = ?";
        } else {
            $sql = "SELECT * FROM tracks WHERE id = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch(PDOException $e) {
        error_log("Error getting track by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Получить треки по исполнителю
 * @param int $artistId ID исполнителя
 * @return array Массив треков
 */
function getTracksByArtist($pdo, $artistId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            WHERE t.artist_id = ? 
            ORDER BY t.title
        ");
        $stmt->execute([$artistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting tracks by artist: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить треки по жанру
 * @param string $genre Жанр
 * @return array Массив треков
 */
function getTracksByGenre($pdo, $genre) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            WHERE a.genre = ? 
            ORDER BY t.title
        ");
        $stmt->execute([$genre]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting tracks by genre: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить треки по названию (поиск)
 * @param string $searchTerm Поисковый запрос
 * @return array Массив треков
 */
function searchTracks($pdo, $searchTerm) {
    try {
        $searchTerm = "%$searchTerm%";
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            WHERE t.title ILIKE ? OR a.name ILIKE ? 
            ORDER BY t.title
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error searching tracks: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить популярные треки (по количеству в плейлистах)
 * @param int $limit Лимит результатов
 * @return array Массив популярных треков
 */
function getPopularTracks($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url,
                t.track_url,
                a.name as artist_name,
                a.genre as artist_genre,
                a.image as artist_image,
                COUNT(pt.track_id) as playlist_count
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            LEFT JOIN playlist_tracks pt ON t.id = pt.track_id
            GROUP BY t.id, a.name, a.genre, a.image
            ORDER BY playlist_count DESC, t.title
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting popular tracks: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить случайные релизы (треки)
 * @param int $limit Количество релизов
 * @return array Массив релизов
 */
function getRandomReleases($pdo, $limit = 3) {
    try {
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
                a.image as artist_image
            FROM tracks t
            LEFT JOIN artist a ON t.artist_id = a.id
            ORDER BY RANDOM()
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting random releases: " . $e->getMessage());
        return [];
    }
}

/**
 * Проверить наличие данных в базе
 */
function hasDatabaseData($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tracks");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch(PDOException $e) {
        error_log("Error checking database data: " . $e->getMessage());
        return false;
    }
}
?>