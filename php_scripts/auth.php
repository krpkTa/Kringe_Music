<?php

// Стартуем сессию для хранения состояния входа
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Временное "хранилище" пользователей (в реальном приложении здесь была бы база данных)
$users = [];

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
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit;
}

function handleLogin() {
    global $users;
    
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
    
    // Имитация проверки пользователя (в реальном приложении - проверка в БД)
    $userFound = false;
    $userData = null;
    
    foreach ($users as $user) {
        if (($user['email'] === $email || $user['username'] === $email) && $user['password'] === $password) {
            $userFound = true;
            $userData = $user;
            break;
        }
    }
    
    if ($userFound && $userData) {
        // Сохраняем в сессию
        $_SESSION['user'] = [
            'id' => uniqid(), // В реальном приложении - ID из БД
            'username' => $userData['username'],
            'email' => $userData['email'],
            'logged_in' => true,
            'login_time' => time()
        ];
        
        // Регенерируем ID сессии для безопасности
        session_regenerate_id(true);
        
        sendJsonResponse(true, 'Вход выполнен успешно!', '/');
    } else {
        // Логируем неудачную попытку входа (в реальном приложении)
        error_log("Failed login attempt for email: $email");
        
        sendJsonResponse(false, 'Неверный email/имя пользователя или пароль');
    }
}

function handleRegister() {
    global $users;
    
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
    
    // Проверяем, не занят ли email или username
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            sendJsonResponse(false, 'Этот email уже занят');
        }
        if ($user['username'] === $username) {
            sendJsonResponse(false, 'Это имя пользователя уже занято');
        }
    }
    
    // "Регистрируем" пользователя
    $users[] = [
        'id' => uniqid(),
        'username' => $username,
        'email' => $email,
        'password' => $password, // В реальном приложении: password_hash($password, PASSWORD_DEFAULT)
        'created_at' => time()
    ];
    
    // Автоматически входим после регистрации
    $_SESSION['user'] = [
        'id' => $users[count($users)-1]['id'],
        'username' => $username,
        'email' => $email,
        'logged_in' => true,
        'login_time' => time()
    ];
    
    // Регенерируем ID сессии для безопасности
    session_regenerate_id(true);
    
    sendJsonResponse(true, 'Регистрация прошла успешно!', '/');
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
?>