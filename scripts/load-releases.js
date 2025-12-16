// scripts/load-releases.js

class ReleasesLoader {
    constructor() {
        this.apiUrl = '/api/releases';
        this.containerId = 'releases-container';
        this.container = null;
        this.defaultCover = 'images/default-album.jpg';
    }
    
    init() {
        this.container = document.getElementById(this.containerId);
        if (!this.container) {
            console.error('Контейнер для релизов не найден');
            return;
        }
        
        this.loadReleases();
    }
    
    async loadReleases() {
        try {
            this.showLoading();
            
            const response = await fetch(`${this.apiUrl}?limit=3&_=${Date.now()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                this.showError(data.error || 'Не удалось загрузить релизы');
                return;
            }
            
            if (!data.releases || data.releases.length === 0) {
                this.showNoData();
                return;
            }
            
            this.renderReleases(data.releases);
            
        } catch (error) {
            console.error('Ошибка загрузки релизов:', error);
            this.showError('Ошибка соединения с сервером');
        }
    }
    
    showLoading() {
        this.container.innerHTML = `
            <div class="loading-releases">
                <div class="loading-spinner"></div>
                <p>Загрузка новых релизов...</p>
            </div>
        `;
    }
    
    showError(message) {
        this.container.innerHTML = `
            <div class="releases-error">
                <div class="error-icon">⚠️</div>
                <h3>Ошибка загрузки</h3>
                <p>${message}</p>
                <button class="retry-btn player-btn" onclick="releasesLoader.loadReleases()">
                    Попробовать снова
                </button>
            </div>
        `;
    }
    
    showNoData() {
        this.container.innerHTML = `
            <div class="no-releases">
                <div class="no-data-icon">🎵</div>
                <h3>Пока нет релизов</h3>
                <p>Добавьте треки в базу данных</p>
                <a href="/admin/upload.php" class="add-music-link player-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Добавить музыку
                </a>
            </div>
        `;
    }
    
    renderReleases(releases) {
        let html = '';
        
        releases.forEach(release => {
            html += `
                <article class="release-card" 
                         data-release-id="${release.id}"
                         data-type="${release.type}">
                    
                    <div class="release-badge">${release.type}</div>
                    
                    <div class="release-cover-container">
                        <img src="${release.cover}" 
                             alt="${release.title} - ${release.artist}"
                             class="release-cover"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='${this.defaultCover}'">
                    </div>
                    
                    <div class="release-content">
                        <h3 class="release-title" title="${release.title}">
                            ${this.truncateText(release.title, 25)}
                        </h3>
                        <p class="release-artist">${release.artist}</p>
                        
                        <div class="release-meta">
                            <span class="release-date">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                </svg>
                                ${release.date}
                            </span>
                            
                            <span class="release-duration">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                </svg>
                                ${release.duration}
                            </span>
                        </div>
                        
                        <div class="release-genre">
                            <span class="genre-tag">${release.genre}</span>
                        </div>
                        
                        <div class="release-actions">
                            <button class="player-btn play-btn" data-track-id="${release.id}" title="Воспроизвести">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                            
                            <button class="player-btn add-to-playlist" title="Добавить в плейлист">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                </svg>
                            </button>
                            
                            <button class="player-btn more-options" title="Еще">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            `;
        });
        
        this.container.innerHTML = html;
        this.addEventListeners();
    }
    
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    addEventListeners() {
        // Кнопки воспроизведения
        document.querySelectorAll('.play-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const trackId = btn.dataset.trackId;
                this.playTrack(trackId);
            });
        });
        
        // Клик по карточке
        document.querySelectorAll('.release-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    const releaseId = card.dataset.releaseId;
                    this.showReleaseDetails(releaseId);
                }
            });
        });
        
        // Кнопки добавления в плейлист
        document.querySelectorAll('.add-to-playlist').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = btn.closest('.release-card');
                const releaseId = card.dataset.releaseId;
                this.addToPlaylist(releaseId);
            });
        });
        
        // Кнопки "Еще"
        document.querySelectorAll('.more-options').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = btn.closest('.release-card');
                const releaseId = card.dataset.releaseId;
                this.showMoreOptions(releaseId, e);
            });
        });
    }
    
    playTrack(trackId) {
        console.log('Воспроизведение трека:', trackId);
        // Здесь можно добавить логику для плеера
        alert(`Воспроизведение трека ID: ${trackId}`);
    }
    
    addToPlaylist(trackId) {
        console.log('Добавление в плейлист:', trackId);
        alert(`Трек ID: ${trackId} добавлен в плейлист`);
    }
    
    showMoreOptions(releaseId, event) {
        console.log('Показать дополнительные опции для релиза:', releaseId);
        event.preventDefault();
        // Можно показать выпадающее меню
    }
    
    showReleaseDetails(releaseId) {
        console.log('Показать детали релиза:', releaseId);
        window.location.href = `/track/${releaseId}`;
    }
}

// Создаем экземпляр и добавляем в глобальную область видимости
const releasesLoader = new ReleasesLoader();

// Инициализируем при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        releasesLoader.init();
    });
} else {
    releasesLoader.init();
}