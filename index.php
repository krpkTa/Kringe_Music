<?php
require_once 'php_scripts/router.php';
require_once 'php_scripts/db_connect.php';
require_once 'php_scripts/mongo_connect.php';
require_once 'php_scripts/auth.php'; 

$router = new Router();

function serveHtmlFile($filename) {
    if (!file_exists($filename)) {
        http_response_code(404);
        echo "Файл не найден: " . htmlspecialchars($filename);
        return;
    }
    
    $content = file_get_contents($filename);
    
    $replacements = [
        'href="index.html"' => 'href="/"',
        'href="login.html"' => 'href="/login"',
        'href="playlists.html"' => 'href="/playlists"',
        
        "onclick=\"location.href='login.html'\"" => "onclick=\"location.href='/login'\"",
        "onclick=\"location.href='index.html'\"" => "onclick=\"location.href='/'\"",
        "onclick=\"location.href='playlists.html'\"" => "onclick=\"location.href='/playlists'\"",
        
        'action="login.html"' => 'action="/login"',
        
        'src="/scripts/auth.js"' => 'src="/scripts/auth.js"',
        'src="js/news.js"' => 'src="/js/news.js"',
        '../api/newsApi.php' => '/api/news',
        'api/newsApi.php' => '/api/news',
    ];
    
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    echo $content;
}

// Маршруты для страниц
$router->addRoute('/', function() {
    serveHtmlFile('index.html');
});

$router->addRoute('/login', function() {
    serveHtmlFile('login.html');
});

$router->addRoute('/playlists', function() {
    serveHtmlFile('playlists.html');
});

// API маршруты
$router->addRoute('/api/login', function() {
    require_once 'php_scripts/auth.php';
    handleLogin();
});

$router->addRoute('/api/register', function() {
    require_once 'php_scripts/auth.php';
    handleRegister();
});

$router->addRoute('/api/logout', function() {
    require_once 'php_scripts/auth.php';
    handleLogout();
});

// Маршруты для новостей
$router->addRoute('/api/news', function() {
    require_once 'api/newsApi.php';
});

$router->addRoute('/api/news/init', function() {
    require_once 'api/newsApi.php';
});

// Статические файлы
$router->addRoute('/scripts/news.js', function() {
    if (file_exists('scripts/news.js')) {
        header('Content-Type: application/javascript');
        readfile('scripts/news.js');
    } else {
        http_response_code(404);
        echo 'File not found';
    }
});

$router->addRoute('/scripts/auth.js', function() {
    if (file_exists('scripts/auth.js')) {
        header('Content-Type: application/javascript');
        readfile('scripts/auth.js');
    } else {
        http_response_code(404);
        echo 'File not found';
    }
});

$router->handleRequest($_SERVER['REQUEST_URI']);
?>