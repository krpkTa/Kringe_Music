<?php

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

mb_internal_encoding('UTF-8');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

function sendJsonResponse($success, $message = '', $errors = [], $data = []) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'errors' => $errors,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Метод не разрешен. Используйте POST.');
}

try {
    require_once __DIR__ . '/db_connect.php';
} catch (Exception $e) {
    error_log("DB include error: " . $e->getMessage());
    sendJsonResponse(false, 'Ошибка подключения к базе данных');
}

if (!isset($pdo) || $pdo === null) {
    sendJsonResponse(false, 'Ошибка подключения к базе данных');
}

$json = file_get_contents('php://input');

if (empty($json)) {
    sendJsonResponse(false, 'Нет данных для обработки');
}

$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(false, 'Неверный формат JSON: ' . json_last_error_msg());
}

function validateFeedbackData($data) {
    $errors = [];
    
    $email = isset($data['email']) ? trim($data['email']) : '';
    if (empty($email)) {
        $errors['email'] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email адрес';
    } elseif (mb_strlen($email) > 100) {
        $errors['email'] = 'Email слишком длинный';
    }
    
    if (!isset($data['rating']) || $data['rating'] === '' || $data['rating'] === null) {
        $errors['rating'] = 'Оценка обязательна';
    } else {
        $rating = intval($data['rating']);
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Оценка должна быть от 1 до 5';
        }
    }
    
    $comment = isset($data['comment']) ? trim($data['comment']) : '';
    if (empty($comment)) {
        $errors['comment'] = 'Комментарий обязателен';
    } elseif (mb_strlen($comment) < 10) {
        $errors['comment'] = 'Комментарий должен содержать минимум 10 символов';
    } elseif (mb_strlen($comment) > 1000) {
        $errors['comment'] = 'Комментарий слишком длинный (максимум 1000 символов)';
    }
    
    return $errors;
}

$validationErrors = validateFeedbackData($data);
if (!empty($validationErrors)) {
    sendJsonResponse(false, 'Ошибка валидации данных', $validationErrors);
}

try {
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $rating = intval($data['rating']);
    $comment = htmlspecialchars(trim($data['comment']), ENT_QUOTES, 'UTF-8');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : 'unknown';
    
    $stmt = $pdo->prepare("
        INSERT INTO feedback (email, rating, comment, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$email, $rating, $comment, $ipAddress, $userAgent]);
    
    if ($result) {
        $feedbackId = $pdo->lastInsertId();
        sendJsonResponse(true, 'Спасибо за ваш отзыв! Ваше мнение очень важно для нас.', [], [
            'id' => $feedbackId
        ]);
    } else {
        sendJsonResponse(false, 'Ошибка при сохранении отзыва');
    }
    
} catch (PDOException $e) {
    error_log("Database error in feedback_api: " . $e->getMessage());
    sendJsonResponse(false, 'Ошибка базы данных');
}