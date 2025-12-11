<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    
    $models_path = __DIR__ . '/../models/news.php';
    if (!file_exists($models_path)) {
        throw new Exception('Model file not found: ' . $models_path);
    }

    require_once $models_path;
    
    $newsModel = new NewsModel();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($newsModel);
            break;
            
        case 'POST':
            handlePostRequest($newsModel);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleGetRequest($newsModel) {
    try {
        if (isset($_GET['id'])) {
            
            $news = $newsModel->getNewsById($_GET['id']);
            if ($news) {
                $newsModel->incrementViews($_GET['id']);
                echo json_encode(['success' => true, 'data' => $news]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'News not found']);
            }
        } else if (isset($_GET['search'])) {
            
            $news = $newsModel->searchNews($_GET['search']);
            echo json_encode(['success' => true, 'data' => $news]);
        } else if (isset($_GET['init'])) {
            
            $count = $newsModel->addSampleData();
            echo json_encode(['success' => true, 'message' => "Added $count sample news", 'count' => $count]);
        } else {
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $news = $newsModel->getAllNews($page, $limit);
            echo json_encode(['success' => true, 'data' => $news]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'HandleGetRequest Error: ' . $e->getMessage()]);
    }
}

function handlePostRequest($newsModel) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        
        if (isset($input['title']) && isset($input['content'])) {
            $id = $newsModel->createNews($input);
            echo json_encode([
                'success' => true, 
                'message' => 'News created successfully',
                'id' => (string)$id
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Title and content are required']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'HandlePostRequest Error: ' . $e->getMessage()]);
    }
}
?>