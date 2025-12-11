<?php
require_once 'models/news.php';

try {
    $newsModel = new NewsModel();
    
    echo "<h2>🎵 Тестирование системы новостей MongoDB</h2>";
    
    // Добавляем тестовые данные
    $count = $newsModel->addSampleData();
    echo "✅ Добавлено тестовых новостей: $count<br><br>";
    
    // Получаем все новости
    $news = $newsModel->getAllNews();
    echo "📰 Всего новостей: " . count($news) . "<br>";
    
    foreach ($news as $item) {
        echo "---<br>";
        echo "<strong>" . $item['title'] . "</strong><br>";
        echo "Категория: " . $item['category'] . "<br>";
        echo "Автор: " . $item['author'] . "<br>";
        echo "Теги: " . implode(', ', $item['tags']) . "<br>";
        echo "ID: " . $item['_id'] . "<br>";
    }
    
    // Тестируем поиск
    echo "<br><h3>🔍 Тестирование поиска:</h3>";
    $searchResults = $newsModel->searchNews('альбом');
    echo "Найдено по запросу 'альбом': " . count($searchResults) . " новостей<br>";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "<br>";
    echo "Убедитесь, что сервер MongoDB запущен!";
}
?>