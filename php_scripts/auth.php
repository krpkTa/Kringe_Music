<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/db_connect.php';

// Стартуем сессию для хранения состояния входа
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!$pdo) {
    sendJsonResponse(false, 'Ошибка подключения к базе данных');
    exit;
}
// Временное "хранилище" пользователей (в реальном приложении здесь была бы база данных)
$userModel = new UserModel($pdo);

// Вспомогательные функции для валидации
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function validateUsername($username) {
    return !empty(trim($username)) && preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sendJsonResponse($success, $message = '', $redirect = '') {
    // Очищаем буфер вывода
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit; // ВАЖНО: завершаем выполнение скрипта
}

function handleLogin() {
    global $userModel;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        sendJsonResponse(false, 'Метод не разрешен');
    }
    
    // Проверяем CSRF токен
    if (!validateCsrfToken()) {
        sendJsonResponse(false, 'Ошибка безопасности');
    }
    
    // Получаем и очищаем данные
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Введите email или имя пользователя';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    }
    
    if (!empty($errors)) {
        sendJsonResponse(false, implode(', ', $errors));
    }
    
    try {
        // Ищем пользователя в базе данных
        $userData = $userModel->findUserForLogin($email);
        
        if ($userData && password_verify($password, $userData['password'])) {
            // Сохраняем в сессию
            $_SESSION['user'] = [
                'login' => $userData['login'],
                'email' => $userData['email'],
                'logged_in' => true,
                'login_time' => time()
            ];
            
            // Регенерируем ID сессии для безопасности
            session_regenerate_id(true);
            
            sendJsonResponse(true, 'Вход выполнен успешно!', '/');
        } else {
            // Логируем неудачную попытку входа
            error_log("Failed login attempt for: $email");
            
            sendJsonResponse(false, 'Неверный email/имя пользователя или пароль');
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendJsonResponse(false, 'Произошла ошибка при входе');
    }
}

function handleRegister() {
    global $userModel;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        sendJsonResponse(false, 'Метод не разрешен');
    }
    
    // Проверяем CSRF токен
    if (!validateCsrfToken()) {
        sendJsonResponse(false, 'Ошибка безопасности');
    }
    
    // Получаем и очищаем данные
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Валидация
    $errors = [];
    
    if (!validateUsername($username)) {
        $errors[] = 'Имя пользователя должно содержать 3-20 символов (только буквы, цифры и подчеркивания)';
    }
    
    if (!validateEmail($email)) {
        $errors[] = 'Введите корректный email';
    }
    
    if (!validatePassword($password)) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }
    
    if (!empty($errors)) {
        sendJsonResponse(false, implode(', ', $errors));
    }
    
    try {
        // Проверяем, не занят ли email или username
        $existingUser = $userModel->userExists($username, $email);
        if ($existingUser) {
            if ($existingUser['login'] === $username) {
                sendJsonResponse(false, 'Это имя пользователя уже занято');
            }
            if ($existingUser['email'] === $email) {
                sendJsonResponse(false, 'Этот email уже занят');
            }
        }
        
        // Создаем пользователя в базе данных
        $result = $userModel->createUser($username, $email, $password);
        
        if ($result) {
    // Автоматически входим после регистрации
    $_SESSION['user'] = [
        'login' => $username,
        'email' => $email,
        'logged_in' => true,
        'login_time' => time()
    ];
    
    // Регенерируем ID сессии для безопасности
    session_regenerate_id(true);
    
    sendJsonResponse(true, 'Регистрация прошла успешно!', '/');
} else {
    // ВРЕМЕННО: Показываем детальную ошибку для отладки
    $errorMsg = $userModel->getLastError() ?: 'Неизвестная ошибка при создании пользователя';
    error_log("Registration failed: " . $errorMsg);
    sendJsonResponse(false, 'Ошибка БД: ' . $errorMsg);
}
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        // ВРЕМЕННО: показываем детальную ошибку для отладки
        sendJsonResponse(false, 'Произошла ошибка при регистрации: ' . $e->getMessage());
    }
}

function handleLogout() {
    // Очищаем сессию
    $_SESSION = [];
    
    // Удаляем куки сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    sendJsonResponse(true, 'Выход выполнен', '/');
}

function isLoggedIn() {
    return isset($_SESSION['user']) && 
           $_SESSION['user']['logged_in'] === true &&
           (time() - ($_SESSION['user']['login_time'] ?? 0)) < (24 * 60 * 60); // 24 часа
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function validateCsrfToken() {
    // В реальном приложении здесь должна быть проверка CSRF токена
    // Для демонстрации всегда возвращаем true
    return true;
}

// Генерация CSRF токена (для будущего использования)
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'register':
            handleRegister();
            break;
        case 'login':
            handleLogin();
            break;
        case 'logout':
            handleLogout();
            break;
        default:
            http_response_code(400);
            sendJsonResponse(false, 'Неизвестное действие');
    }
}
?>