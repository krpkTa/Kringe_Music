<?php

class Router {
    private $routes = [];
    
    public function addRoute($path, $callback) {
        $this->routes[$path] = $callback;
    }
    
    public function handleRequest($uri) {
        $uri = strtok($uri, '?');
        
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        if (array_key_exists($uri, $this->routes)) {
            call_user_func($this->routes[$uri]);
        } else {
            $this->show404();
        }
    }
    
    private function show404() {
        http_response_code(404);
        echo '
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Страница не найдена - Kringe-Music</title>
            <link rel="stylesheet" href="/styles/common.css">
        </head>
        <body>
            <div class="main-container">
                <aside class="sidebar">
                    <div class="logo">Kringe-Music</div>
                    <nav class="sidebar-nav">
                        <ul>
                            <li><a href="/">Главная</a></li>
                            <li><a href="/playlists">Моя медиатека</a></li>
                            <li><a href="/login">Вход</a></li>
                        </ul>
                    </nav>
                </aside>
                <div class="main-content">
                    <header>
                        <div class="header-controls">
                            <form class="search-form">
                                <input type="search" placeholder="Поиск музыки..." aria-label="Поиск музыки">
                                <button type="submit">Найти</button>
                            </form>
                        </div>
                    </header>
                    <main style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
                        <div style="text-align: center;">
                            <h1 style="font-size: 3rem; color: #ff6b6b; margin-bottom: 1rem;">404</h1>
                            <h2 style="color: #ffffff; margin-bottom: 1rem;">Страница не найдена</h2>
                            <p style="color: rgba(224, 224, 224, 0.8); margin-bottom: 2rem;">
                                Запрашиваемая страница не существует.
                            </p>
                            <a href="/" style="display: inline-block; padding: 0.75rem 1.5rem; 
                                             background: #ff6b6b; color: #ffffff; 
                                             text-decoration: none; border-radius: 25px;
                                             font-weight: 600;">
                                Вернуться на главную
                            </a>
                        </div>
                    </main>
                </div>
            </div>
        </body>
        </html>';
    }
}
?>