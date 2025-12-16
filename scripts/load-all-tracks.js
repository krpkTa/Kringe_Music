// scripts/load-all-tracks.js

class AllTracksLoader {
    constructor() {
        this.apiUrl = '/api/tracks';
        this.containerSelector = '.tracks-container';
        this.container = null;
        this.defaultCover = 'images/default-track.jpg';
    }
    
    init() {
        this.container = document.querySelector(this.containerSelector);
        if (!this.container) {
            console.error('Контейнер для треков не найден');
            return;
        }
        
        this.loadTracks();
    }
    
    async loadTracks() {
        try {
            this.showLoading();
            
            const response = await fetch(`${this.apiUrl}?_=${Date.now()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Отладка: посмотрим, что приходит с сервера
            console.log('Получены данные треков:', data);
            
            if (!data.success) {
                this.showError(data.error || data.message || 'Не удалось загрузить треки');
                return;
            }
            
            if (!data.tracks || data.tracks.length === 0) {
                this.showNoData();
                return;
            }
            
            this.renderTracks(data.tracks);
            
        } catch (error) {
            console.error('Ошибка загрузки треков:', error);
            this.showError('Ошибка соединения с сервером');
        }
    }
    
    showLoading() {
        this.container.innerHTML = `
            <div class="loading-tracks">
                <div class="loading-spinner"></div>
                <p>Загрузка треков...</p>
            </div>
        `;
    }
    
    showError(message) {
        this.container.innerHTML = `
            <div class="tracks-error">
                <div class="error-icon">⚠️</div>
                <h3>Ошибка загрузки</h3>
                <p>${message}</p>
                <button class="retry-btn player-btn" onclick="allTracksLoader.loadTracks()">
                    Попробовать снова
                </button>
            </div>
        `;
    }
    
    showNoData() {
        this.container.innerHTML = `
            <div class="no-tracks">
                <div class="no-data-icon">🎵</div>
                <h3>Треков пока нет</h3>
                <p>База данных пуста</p>
            </div>
        `;
    }
    
    renderTracks(tracks) {
        let html = '';
        
        tracks.forEach((track, index) => {
            html += this.renderTrack(track, index + 1);
        });
        
        this.container.innerHTML = html;
        this.addEventListeners();
    }
    
    renderTrack(track, number) {
        // Определяем, какие поля есть в данных трека
        const trackArtist = track.artist || track.artist_name || track.author || 'Неизвестный исполнитель';
        const trackTitle = track.title || track.name || 'Без названия';
        const trackCover = track.cover || track.img_url || track.image || this.defaultCover;
        const duration = track.duration_formatted || this.formatDuration(track.duration || 0);
        
        return `
            <article class="track-card" data-track-id="${track.id}">
                <span class="track-number">${number}</span>
                <div class="track-info">
                    <img src="${trackCover}" 
                         alt="${trackTitle}"
                         class="track-cover"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='${this.defaultCover}'">
                    <div class="track-details">
                        <h3 class="track-title">${this.truncateText(trackTitle, 30)}</h3>
                        <p class="track-artist">${this.truncateText(trackArtist, 25)}</p>
                    </div>
                </div>
                <span class="track-duration">${duration}</span>
            </article>
        `;
    }
    
    truncateText(text, maxLength) {
        if (!text || typeof text !== 'string') return 'Неизвестно';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    formatDuration(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
    
    addEventListeners() {
        // Клик по карточке трека
        document.querySelectorAll('.track-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const trackId = card.dataset.trackId;
                this.playTrack(trackId);
            });
        });
    }
    
    playTrack(trackId) {
        console.log('Воспроизведение трека:', trackId);
        // Здесь можно добавить логику для плеера
        alert(`Воспроизведение трека ID: ${trackId}`);
    }
}

// Создаем экземпляр и добавляем в глобальную область видимости
const allTracksLoader = new AllTracksLoader();

// Инициализируем при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        allTracksLoader.init();
    });
} else {
    allTracksLoader.init();
}