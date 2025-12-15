<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/db_connect.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!$pdo) {
    sendJsonResponse(false, 'Ошибка подключения к базе данных');
    exit;
}

$userModel = new UserModel($pdo);


function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password)
{
    return strlen($password) >= 6;
}

function validateUsername($username)
{
    return !empty(trim($username)) && preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function sanitizeInput($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sendJsonResponse($success, $message = '', $redirect = '', $userData = []) {
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect,
        'userData' => $userData
    ], JSON_UNESCAPED_UNICODE);
    exit; 
}

function handleLogin()
{
    global $userModel;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        sendJsonResponse(false, 'Метод не разрешен');
    }

    
    if (!validateCsrfToken()) {
        sendJsonResponse(false, 'Ошибка безопасности');
    }

    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    
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
        
        $userData = $userModel->findUserForLogin($email);

        if ($userData && password_verify($password, $userData['password'])) {
            
            $_SESSION['user'] = [
                'login' => $userData['login'],
                'email' => $userData['email'],
                'logged_in' => true,
                'login_time' => time()
            ];

            
            session_regenerate_id(true);

            
            $userDataForJs = json_encode($_SESSION['user']);

            
            echo json_encode([
                'success' => true,
                'message' => 'Вход выполнен успешно!',
                'redirect' => '/',
                'userData' => $_SESSION['user']
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } else {
            
            error_log("Failed login attempt for: $email");

            sendJsonResponse(false, 'Неверный email/имя пользователя или пароль');
        }

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendJsonResponse(false, 'Произошла ошибка при входе');
    }
}

function handleRegister()
{
    global $userModel;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        sendJsonResponse(false, 'Метод не разрешен');
    }

    
    if (!validateCsrfToken()) {
        sendJsonResponse(false, 'Ошибка безопасности');
    }

    
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    
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
        
        $existingUser = $userModel->userExists($username, $email);
        if ($existingUser) {
            if ($existingUser['login'] === $username) {
                sendJsonResponse(false, 'Это имя пользователя уже занято');
            }
            if ($existingUser['email'] === $email) {
                sendJsonResponse(false, 'Этот email уже занят');
            }
        }

        
        $result = $userModel->createUser($username, $email, $password);

        if ($result) {
            
            $_SESSION['user'] = [
                'login' => $username,
                'email' => $email,
                'logged_in' => true,
                'login_time' => time()
            ];

            
            session_regenerate_id(true);

            
            echo json_encode([
                'success' => true,
                'message' => 'Регистрация прошла успешно!',
                'redirect' => '/',
                'userData' => $_SESSION['user']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        
        sendJsonResponse(false, 'Произошла ошибка при регистрации: ' . $e->getMessage());
    }
}

function handleLogout()
{
    
    $_SESSION = [];

    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    sendJsonResponse(true, 'Выход выполнен', '/');
}

function isLoggedIn()
{
    return isset($_SESSION['user']) &&
        $_SESSION['user']['logged_in'] === true &&
        (time() - ($_SESSION['user']['login_time'] ?? 0)) < (24 * 60 * 60); 
}

function getCurrentUser()
{
    return $_SESSION['user'] ?? null;
}

function validateCsrfToken()
{
    
    
    return true;
}


function generateCsrfToken()
{
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