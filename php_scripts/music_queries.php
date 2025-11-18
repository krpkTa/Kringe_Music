<?php
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
 * @return array Массив треков
 */
function getAllTracks($pdo, $withArtistInfo = true) {
    try {
        if ($withArtistInfo) {
            $sql = "SELECT t.*, a.name as artist_name, a.genre as artist_genre 
                    FROM tracks t 
                    LEFT JOIN artist a ON t.artist_id = a.id 
                    ORDER BY t.title";
        } else {
            $sql = "SELECT * FROM tracks ORDER BY title";
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
            $sql = "SELECT t.*, a.name as artist_name, a.genre as artist_genre 
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
            SELECT t.*, a.name as artist_name, a.genre as artist_genre 
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
            SELECT t.*, a.name as artist_name, a.genre as artist_genre 
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
            SELECT t.*, a.name as artist_name, a.genre as artist_genre 
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
 * Получить популярные треки (по какому-либо критерию, например по количеству в плейлистах)
 * @param int $limit Лимит результатов
 * @return array Массив популярных треков
 */
function getPopularTracks($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, a.name as artist_name, a.genre as artist_genre,
                   COUNT(pt.track_id) as playlist_count
            FROM tracks t 
            LEFT JOIN artist a ON t.artist_id = a.id 
            LEFT JOIN playlist_tracks pt ON t.id = pt.track_id
            GROUP BY t.id, a.name, a.genre
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

// Примеры использования:
/*
// Получить всех исполнителей
$artists = getAllArtists($pdo);

// Получить исполнителя по ID
$artist = getArtistById($pdo, 1);

// Получить всех исполнителей жанра "Rock"
$rockArtists = getArtistsByGenre($pdo, "Rock");

// Получить все треки с информацией об исполнителях
$tracks = getAllTracks($pdo);

// Получить трек по ID
$track = getTrackById($pdo, 1);

// Получить все треки исполнителя
$artistTracks = getTracksByArtist($pdo, 1);

// Поиск треков
$searchResults = searchTracks($pdo, "love");

// Получить популярные треки
$popularTracks = getPopularTracks($pdo, 20);
*/
?>