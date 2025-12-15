<?php
// php_scripts/music_queries.php

/**
 * Получить всех исполнителей
 * @return array Массив исполнителей
 */
function getAllArtists($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM artist ORDER BY name");
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting artists: " . $e->getMessage());
        return [];
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
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetch() ?: null;
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
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting artists by genre: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить все треки
 * @param bool $withArtistInfo Включить информацию об исполнителе
 * @return array Массив треков
 */
function getAllTracks($pdo, $withArtistInfo = true) {
    try {
        if ($withArtistInfo) {
            $sql = "SELECT 
                        t.id,
                        t.title::text,
                        t.duration,
                        t.album_id,
                        t.artist_id,
                        t.img_url::text,
                        t.track_url::text,
                        a.name::text as artist_name,
                        a.genre::text as artist_genre
                    FROM tracks t 
                    LEFT JOIN artist a ON t.artist_id = a.id 
                    ORDER BY t.id";
        } else {
            $sql = "SELECT 
                        id,
                        title::text,
                        duration,
                        album_id,
                        artist_id,
                        img_url::text,
                        track_url::text
                    FROM tracks 
                    ORDER BY id";
        }
        
        $stmt = $pdo->query($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        
        // Конвертируем кодировку из Latin1 в UTF-8 если нужно
        array_walk_recursive($result, function(&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1251');
            }
        });
        
        return $result;
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
                        t.title::text,
                        t.duration,
                        t.album_id,
                        t.artist_id,
                        t.img_url::text,
                        t.track_url::text,
                        a.name::text as artist_name,
                        a.genre::text as artist_genre
                    FROM tracks t 
                    LEFT JOIN artist a ON t.artist_id = a.id 
                    WHERE t.id = ?";
        } else {
            $sql = "SELECT 
                        id,
                        title::text,
                        duration,
                        album_id,
                        artist_id,
                        img_url::text,
                        track_url::text
                    FROM tracks 
                    WHERE id = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetch() ?: null;
        
        if ($result) {
            array_walk_recursive($result, function(&$item) {
                if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1251');
                }
            });
        }
        
        return $result;
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
                t.title::text,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url::text,
                t.track_url::text,
                a.name::text as artist_name,
                a.genre::text as artist_genre
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            WHERE t.artist_id = ? 
            ORDER BY t.title
        ");
        $stmt->execute([$artistId]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        
        array_walk_recursive($result, function(&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1251');
            }
        });
        
        return $result;
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
                t.title::text,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url::text,
                t.track_url::text,
                a.name::text as artist_name,
                a.genre::text as artist_genre
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            WHERE a.genre = ? 
            ORDER BY t.title
        ");
        $stmt->execute([$genre]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        
        array_walk_recursive($result, function(&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1251');
            }
        });
        
        return $result;
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
                t.title::text,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url::text,
                t.track_url::text,
                a.name::text as artist_name,
                a.genre::text as artist_genre
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            WHERE t.title ILIKE ? OR a.name ILIKE ? 
            ORDER BY t.title
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        
        array_walk_recursive($result, function(&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1251');
            }
        });
        
        return $result;
    } catch(PDOException $e) {
        error_log("Error searching tracks: " . $e->getMessage());
        return [];
    }
}

/**
 * Получить популярные треки
 * @param int $limit Лимит результатов
 * @return array Массив популярных треков
 */
function getPopularTracks($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.title::text,
                t.duration,
                t.album_id,
                t.artist_id,
                t.img_url::text,
                t.track_url::text,
                a.name::text as artist_name,
                a.genre::text as artist_genre,
                COUNT(pt.track_id) as playlist_count
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            LEFT JOIN playlist_tracks pt ON t.id = pt.track_id
            GROUP BY t.id, t.title, t.duration, t.album_id, t.artist_id, t.img_url, t.track_url, a.name, a.genre
            ORDER BY playlist_count DESC, t.title
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        
        array_walk_recursive($result, function(&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1251');
            }
        });
        
        return $result;
    } catch(PDOException $e) {
        error_log("Error getting popular tracks: " . $e->getMessage());
        return [];
    }
}
?>