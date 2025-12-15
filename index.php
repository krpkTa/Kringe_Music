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

$router->addRoute('/about', function() {
    serveHtmlFile('about.html');
});


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

$router->addRoute('/api/feedback', function() {
    require_once 'php_scripts/feedback_api.php';
});

$router->handleRequest($_SERVER['REQUEST_URI']);
?>