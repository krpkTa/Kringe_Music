// scripts/load-playlist.js

class PlaylistLoader {
    constructor() {
        this.apiUrl = '/api/daily-playlist'; // Новый endpoint
        this.containerId = 'playlist';
        this.container = null;
        this.defaultCover = 'images/day-playlist.jpg';
    }
    
    init() {
        this.container = document.getElementById(this.containerId);
        if (!this.container) {
            console.error('Контейнер для плейлиста не найден');
            return;
        }
        
        // Находим контейнер внутри секции
        this.playlistContainer = this.container.querySelector('.playlist-container');
        if (!this.playlistContainer) {
            console.error('Контейнер плейлиста не найден');
            return;
        }
        
        this.loadPlaylist();
    }
    
    async loadPlaylist() {
        try {
            this.showLoading();
            
            const response = await fetch(`${this.apiUrl}?limit=15&_=${Date.now()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                this.showError(data.error || data.message || 'Не удалось загрузить плейлист');
                return;
            }
            
            if (!data.tracks || data.tracks.length === 0) {
                this.showNoData();
                return;
            }
            
            this.renderPlaylist(data.tracks);
            
        } catch (error) {
            console.error('Ошибка загрузки плейлиста:', error);
            this.showError('Ошибка соединения с сервером');
        }
    }
    
    // В функции renderTrack используйте более простую структуру
renderTrack(track, number) {
    const duration = track.duration_formatted || this.formatDuration(track.duration || 0);
    
    return `
        <div class="playlist-track" data-track-id="${track.id}">
            <div class="track-number">${number}</div>
            <div class="track-cover-container">
                <img src="${track.cover || this.defaultCover}" 
                     alt="${track.title}"
                     class="track-cover"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='${this.defaultCover}'">
            </div>
            <div class="track-info">
                <div class="track-title" title="${track.title}">
                    ${this.truncateText(track.title, 30)}
                </div>
                <div class="track-artist" title="${track.artist}">
                    ${this.truncateText(track.artist || 'Неизвестный исполнитель', 25)}
                </div>
            </div>
            <div class="track-duration">${duration}</div>
            <div class="track-actions">
                <button class="player-btn play-track-btn" data-track-id="${track.id}" title="Воспроизвести">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>
                <button class="player-btn add-to-library-btn" title="Добавить в медиатеку">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
}
    
    showLoading() {
        this.playlistContainer.innerHTML = `
            <div class="loading-playlist">
                <div class="loading-spinner"></div>
                <p>Загрузка плейлиста дня...</p>
            </div>
        `;
    }
    
    showError(message) {
        this.playlistContainer.innerHTML = `
            <div class="playlist-error">
                <div class="error-icon">⚠️</div>
                <h3>Ошибка загрузки плейлиста</h3>
                <p>${message}</p>
                <button class="retry-btn player-btn" onclick="playlistLoader.loadPlaylist()">
                    Попробовать снова
                </button>
            </div>
        `;
    }
    
    showNoData() {
        this.playlistContainer.innerHTML = `
            <div class="no-playlist">
                <div class="no-data-icon">🎵</div>
                <h3>Плейлист дня недоступен</h3>
                <p>В базе данных недостаточно треков</p>
                <a href="/admin/upload.php" class="add-music-link player-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Добавить музыку
                </a>
            </div>
        `;
    }
    
    renderPlaylist(tracks) {
        let html = `
            <article class="playlist-header">
                <img src="${this.defaultCover}" alt="Обложка плейлиста дня" class="playlist-cover">
                <div class="playlist-info">
                    <h3>Плейлист дня</h3>
                    <p>Ежедневная случайная подборка из 15 треков</p>
                    <p>Количество треков: ${tracks.length}</p>
                    <button class="player-btn shuffle-play-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/>
                        </svg>
                    </button>
                </div>
            </article>
            <div class="playlist-tracks">
        `;
        
        tracks.forEach((track, index) => {
            html += this.renderTrack(track, index + 1);
        });
        
        html += '</div>';
        
        this.playlistContainer.innerHTML = html;
        this.addEventListeners();
    }
    
    renderTrack(track, number) {
        return `
            <div class="playlist-track" data-track-id="${track.id}">
                <div class="track-number">${number}</div>
                <div class="track-cover-container">
                    <img src="${track.cover || this.defaultCover}" 
                         alt="${track.title}"
                         class="track-cover"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='${this.defaultCover}'">
                </div>
                <div class="track-info">
                    <div class="track-title" title="${track.title}">
                        ${this.truncateText(track.title, 30)}
                    </div>
                    <div class="track-artist">${track.artist || 'Неизвестный исполнитель'}</div>
                </div>
                <div class="track-duration">${this.formatDuration(track.duration)}</div>
                <div class="track-actions">
                    <button class="player-btn play-track-btn" data-track-id="${track.id}" title="Воспроизвести">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    <button class="player-btn add-to-library-btn" title="Добавить в медиатеку">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                    </button>
                    <button class="player-btn more-options-btn" title="Еще">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }
    
    truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    formatDuration(seconds) {
        if (!seconds) return '0:00';
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
    
    addEventListeners() {
        // Кнопки воспроизведения отдельных треков
        document.querySelectorAll('.play-track-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const trackId = btn.dataset.trackId;
                this.playTrack(trackId);
            });
        });
        
        // Кнопка перемешивания всего плейлиста
        const shuffleBtn = document.querySelector('.shuffle-play-btn');
        if (shuffleBtn) {
            shuffleBtn.addEventListener('click', () => {
                this.shufflePlaylist();
            });
        }
        
        // Клик по треку для воспроизведения
        document.querySelectorAll('.playlist-track').forEach(track => {
            track.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    const trackId = track.dataset.trackId;
                    this.playTrack(trackId);
                }
            });
        });
        
        // Кнопки добавления в медиатеку
        document.querySelectorAll('.add-to-library-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const trackElement = btn.closest('.playlist-track');
                const trackId = trackElement.dataset.trackId;
                this.addToLibrary(trackId);
            });
        });
        
        // Кнопки "Еще"
        document.querySelectorAll('.more-options-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const trackElement = btn.closest('.playlist-track');
                const trackId = trackElement.dataset.trackId;
                this.showMoreOptions(trackId, e);
            });
        });
    }
    
    playTrack(trackId) {
        console.log('Воспроизведение трека из плейлиста:', trackId);
        // Здесь можно добавить логику для плеера
        // Например: window.player.playTrack(trackId);
        alert(`Воспроизведение трека ID: ${trackId}`);
    }
    
    shufflePlaylist() {
        console.log('Перемешивание плейлиста');
        // Логика перемешивания треков в плейлисте
        const tracks = document.querySelectorAll('.playlist-track');
        const tracksArray = Array.from(tracks);
        
        // Перемешиваем массив
        for (let i = tracksArray.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [tracksArray[i], tracksArray[j]] = [tracksArray[j], tracksArray[i]];
        }
        
        // Обновляем номера треков
        const tracksContainer = document.querySelector('.playlist-tracks');
        tracksContainer.innerHTML = '';
        tracksArray.forEach((track, index) => {
            track.querySelector('.track-number').textContent = index + 1;
            tracksContainer.appendChild(track);
        });
        
        alert('Плейлист перемешан!');
    }
    
    addToLibrary(trackId) {
        console.log('Добавление в медиатеку:', trackId);
        // Здесь можно добавить запрос к API для добавления в медиатеку
        alert(`Трек ID: ${trackId} добавлен в вашу медиатеку`);
    }
    
    showMoreOptions(trackId, event) {
        console.log('Показать дополнительные опции для трека:', trackId);
        event.preventDefault();
        // Можно показать контекстное меню
    }
}

// Создаем экземпляр и добавляем в глобальную область видимости
const playlistLoader = new PlaylistLoader();

// Инициализируем при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        playlistLoader.init();
    });
} else {
    playlistLoader.init();
}