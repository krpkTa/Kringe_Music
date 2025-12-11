document.addEventListener('DOMContentLoaded', function() {
    console.log('News script loaded');
    loadNews();
});

async function loadNews() {
    try {
        console.log('Loading news...');
        
        const response = await fetch('/api/news');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log('News API response:', result);
        
        if (result.success) {
            displayNews(result.data);
        } else {
            console.error('Failed to load news:', result.error);
            showErrorMessage('Не удалось загрузить новости: ' + (result.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        console.error('Error loading news:', error);
        showErrorMessage('Ошибка загрузки новостей. Проверьте подключение к серверу.');
    }
}

function displayNews(newsArray) {
    const newsContainer = document.querySelector('.news-container');
    
    if (!newsContainer) {
        console.error('News container not found');
        return;
    }
    
    if (!Array.isArray(newsArray) || newsArray.length === 0) {
        newsContainer.innerHTML = `
            <div class="no-news">
                <p>Новостей пока нет.</p>
                <button onclick="initSampleData()">Добавить тестовые данные</button>
            </div>
        `;
        return;
    }
    
    newsContainer.innerHTML = '';
    
    newsArray.forEach(newsItem => {
        const newsCard = document.createElement('article');
        newsCard.className = 'news-card';
        newsCard.innerHTML = `
            <div class="news-content">
                <h3>${escapeHtml(newsItem.title)}</h3>
                <p class="news-excerpt">${escapeHtml(newsItem.short_content || newsItem.content?.substring(0, 100) || '')}...</p>
                <div class="news-meta">
                    <span class="news-category">${escapeHtml(newsItem.category || 'новости')}</span>
                    <span class="news-date">${formatDate(newsItem.created_at)}</span>
                    <span class="news-views">👁️ ${newsItem.views || 0}</span>
                </div>
                <div class="news-tags">
                    ${(newsItem.tags || []).map(tag => `<span class="news-tag">${escapeHtml(tag)}</span>`).join('')}
                </div>
            </div>
        `;
        
        // Добавляем клик на всю карточку для просмотра деталей
        newsCard.addEventListener('click', () => {
            window.location.href = `/news-detail.html?id=${newsItem._id}`;
        });
        
        newsContainer.appendChild(newsCard);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

function showErrorMessage(message) {
    const newsContainer = document.querySelector('.news-container');
    if (newsContainer) {
        newsContainer.innerHTML = `
            <div class="error-message">
                <p>${escapeHtml(message)}</p>
                <button onclick="loadNews()">Повторить попытку</button>
                <button onclick="initSampleData()" style="margin-left: 10px;">Инициализировать данные</button>
            </div>
        `;
    }
}

async function initSampleData() {
    try {
        const response = await fetch('/api/news?init=1');
        const result = await response.json();
        
        if (result.success) {
            alert(`Добавлено ${result.count} тестовых новостей!`);
            loadNews();
        } else {
            alert('Ошибка при добавлении данных: ' + (result.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка при инициализации данных: ' + error.message);
    }
}