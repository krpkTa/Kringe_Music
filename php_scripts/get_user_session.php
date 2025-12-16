<?php
// php_scripts/get_user_session.php

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

session_start();

ob_clean();

$response = [
    'logged_in' => false,
    'login' => null,
    'email' => null
];

if (isset($_SESSION['user']) && 
    isset($_SESSION['user']['logged_in']) && 
    $_SESSION['user']['logged_in'] === true &&
    isset($_SESSION['user']['login_time']) &&
    (time() - $_SESSION['user']['login_time']) < (24 * 60 * 60)) {
    
    $response['logged_in'] = true;
    $response['login'] = $_SESSION['user']['login'] ?? null;
    $response['email'] = $_SESSION['user']['email'] ?? null;
    
    // Обновляем время активности
    $_SESSION['user']['login_time'] = time();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>