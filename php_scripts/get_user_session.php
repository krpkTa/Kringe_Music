<?php


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

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['login'] = $_SESSION['login'] ?? null;
    $response['email'] = $_SESSION['email'] ?? null;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;