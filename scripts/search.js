class SearchManager {
    constructor() {
        this.searchForms = document.querySelectorAll('.search-form');
        this.searchInputs = document.querySelectorAll('.search-form input[type="search"]');

        // Настройки
        this.minSearchLength = 3;      // минимальное количество символов для живого поиска
        this.debounceDelay = 400;      // задержка перед запросом при вводе, мс

        // Для предотвращения гонок запросов
        this.lastRequestId = 0;
        this.currentSearchTerm = '';
        this.currentMode = null;       // 'realtime' | 'submit'

        // Для отдельных таймеров на каждый input
        this.debounceTimers = new Map();

        this.init();
    }

    init() {
        if (this.searchForms.length === 0) {
            console.log('SearchManager: .search-form не найдены');
            return;
        }

        this.searchForms.forEach(form => this.setupForm(form));
        this.searchInputs.forEach(input => this.setupInput(input));

        console.log('SearchManager инициализирован');
    }

    setupForm(form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const input = form.querySelector('input[type="search"]');
            if (!input) return;

            const searchTerm = input.value.trim();
            if (!searchTerm) {
                this.hideResults();
                return;
            }

            this.performSearch(searchTerm, {
                mode: 'submit',
                input
            });
        });
    }

    setupInput(input) {
        input.addEventListener('input', (e) => {
            const searchTerm = e.target.value.trim();

            if (searchTerm.length === 0) {
                this.clearSearchResults();
            }

            const oldTimer = this.debounceTimers.get(input);
            if (oldTimer) {
                clearTimeout(oldTimer);
            }

            if (searchTerm.length >= this.minSearchLength) {
                const timerId = setTimeout(() => {
                    this.performSearch(searchTerm, {
                        mode: 'realtime',
                        input
                    });
                }, this.debounceDelay);

                this.debounceTimers.set(input, timerId);
            }
        });
    }

    async performSearch(searchTerm, { mode = 'realtime', input = null } = {}) {
        this.currentSearchTerm = searchTerm;
        this.currentMode = mode;

        const requestId = ++this.lastRequestId;

        try {
            const encodedTerm = encodeURIComponent(searchTerm);
            const apiUrl = `/php_scripts/api.php?action=searchTracks&search=${encodedTerm}`;

            const response = await fetch(apiUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                console.warn(`Search API вернул статус ${response.status}`);
                if (mode === 'realtime') {
                    this.hideResults();
                } else {
                    this.displaySearchResults([], searchTerm);
                }
                return;
            }

            const responseText = await response.text();
            let json;

            try {
                json = JSON.parse(responseText);
            } catch (err) {
                console.error('Search API: не удалось распарсить JSON', responseText.substring(0, 300));
                if (mode === 'realtime') {
                    this.hideResults();
                } else {
                    this.displaySearchResults([], searchTerm);
                }
                return;
            }

            if (requestId !== this.lastRequestId) {
                return;
            }

            if (json && json.error) {
                console.error('Search API error:', json.error);
                if (mode === 'realtime') {
                    this.hideResults();
                } else {
                    this.displaySearchResults([], searchTerm);
                }
                return;
            }

            let tracks = [];
            if (Array.isArray(json)) {
                tracks = json;
            } else if (Array.isArray(json.tracks)) {
                tracks = json.tracks;
            }

            if (mode === 'realtime') {
                this.displayRealTimeResults(tracks, searchTerm, input);
            } else {
                this.displaySearchResults(tracks, searchTerm);
            }
        } catch (error) {
            console.error('Search error:', error);
            if (mode === 'realtime') {
                this.hideResults();
            } else {
                this.displaySearchResults([], searchTerm);
            }
        }
    }

    displayRealTimeResults(tracks, searchTerm, input) {
        let resultsContainer = document.getElementById('search-results-popup');

        if (!resultsContainer) {
            resultsContainer = document.createElement('div');
            resultsContainer.id = 'search-results-popup';
            resultsContainer.className = 'search-results-popup';
            document.body.appendChild(resultsContainer);
        }

        resultsContainer.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'search-results-header';
        header.innerHTML = `
            <h4>Результаты поиска: "${this.escapeHtml(searchTerm)}"</h4>
            <p>${tracks && tracks.length ? `Найдено треков: ${tracks.length}` : 'Ничего не найдено'}</p>
            <button class="close-results" type="button" aria-label="Закрыть результаты">×</button>
        `;

        const closeBtn = header.querySelector('.close-results');
        closeBtn.addEventListener('click', () => this.hideResults());

        resultsContainer.appendChild(header);

        const resultsList = document.createElement('div');
        resultsList.className = 'search-results-list';

        if (!tracks || tracks.length === 0) {
            resultsList.innerHTML = `
                <div class="no-results">
                    <p>По запросу "${this.escapeHtml(searchTerm)}" ничего не найдено</p>
                </div>
            `;
        } else {
            tracks.forEach(track => {
                const trackElement = this.createTrackElement(track);
                resultsList.appendChild(trackElement);
            });
        }

        resultsContainer.appendChild(resultsList);

        this.positionPopupRelativeToInput(resultsContainer, input);

        resultsContainer.style.display = 'block';

        setTimeout(() => {
            const clickHandler = (e) => {
                if (!resultsContainer.contains(e.target) &&
                    !e.target.closest('.search-form')) {
                    this.hideResults();
                    document.removeEventListener('click', clickHandler);
                }
            };
            document.addEventListener('click', clickHandler);
        }, 0);
    }

    positionPopupRelativeToInput(container, input) {
        const targetInput = input || document.querySelector('.search-form input[type="search"]');
        if (!targetInput) return;

        const rect = targetInput.getBoundingClientRect();

        container.style.position = 'fixed';
        container.style.top = `${rect.bottom + 8}px`;
        container.style.left = `${rect.left}px`;
        container.style.width = `${rect.width}px`;
        container.style.maxWidth = '500px';
    }

    displaySearchResults(tracks, searchTerm) {
        let pageResultsContainer = document.getElementById('search-results-page');

        if (!pageResultsContainer) {
            pageResultsContainer = document.createElement('div');
            pageResultsContainer.id = 'search-results-page';
            pageResultsContainer.className = 'search-results-page';

            const main = document.querySelector('main');
            if (main) {
                main.insertBefore(pageResultsContainer, main.firstChild);
            } else {
                document.body.appendChild(pageResultsContainer);
            }
        }

        const safeTerm = this.escapeHtml(searchTerm);

        pageResultsContainer.innerHTML = `
            <div class="search-page-header">
                <h2>Результаты поиска: "${safeTerm}"</h2>
                <p>${tracks && tracks.length ? `Найдено треков: ${tracks.length}` : 'Ничего не найдено'}</p>
            </div>
            <div class="search-page-results">
                ${tracks && tracks.length
                ? this.createResultsHTML(tracks)
                : '<p class="no-results">Ничего не найдено</p>'
            }
            </div>
        `;

        const items = pageResultsContainer.querySelectorAll('.search-track-item');
        items.forEach((item, index) => {
            item.addEventListener('click', () => {
                if (!tracks || !tracks[index]) return;
                this.playTrack(tracks[index]);
            });
        });
    }

    createResultsHTML(tracks) {
        return tracks.map(track => `
            <div class="search-track-item" data-track-id="${this.escapeHtml(track.id)}">
                <div class="track-cover">
                    <img src="${track.img_url || 'images/default-cover.jpg'}"
                         alt="${this.escapeHtml(track.title || 'Трек')}">
                </div>
                <div class="track-info">
                    <h3>${this.escapeHtml(track.title || 'Без названия')}</h3>
                    <p class="artist">${this.escapeHtml(track.artist_name || 'Неизвестный исполнитель')}</p>
                    ${track.artist_genre ? `<p class="genre">${this.escapeHtml(track.artist_genre)}</p>` : ''}
                </div>
                <div class="track-duration">
                    ${this.formatDuration(track.duration)}
                </div>
            </div>
        `).join('');
    }

    createTrackElement(track) {
        const element = document.createElement('div');
        element.className = 'search-result-item';

        element.innerHTML = `
            <img src="${track.img_url || 'images/default-cover.jpg'}"
                 alt="${this.escapeHtml(track.title || 'Трек')}">
            <div class="track-info">
                <div class="track-title">${this.escapeHtml(track.title || 'Без названия')}</div>
                <div class="track-artist">${this.escapeHtml(track.artist_name || 'Неизвестный исполнитель')}</div>
            </div>
        `;

        element.addEventListener('click', () => {
            this.playTrack(track);
            this.hideResults();
        });

        return element;
    }

    playTrack(track) {
        if (!track || !track.track_url) {
            console.warn('Невозможно воспроизвести трек: нет track_url', track);
            return;
        }

        const player = window.musicPlayer;
        if (!player) {
            console.warn('musicPlayer не инициализирован');
            return;
        }

        let played = false;

        if (Array.isArray(player.playlist)) {
            const index = player.playlist.findIndex(t => String(t.id) === String(track.id));
            if (index !== -1) {
                player.currentTrackIndex = index;
                if (typeof player.loadTrack === 'function') {
                    player.loadTrack(index);
                }
                if (typeof player.play === 'function') {
                    player.play();
                }
                played = true;
            }
        }

        if (!played) {
            if (player.audio) {
                player.audio.src = track.track_url;
            }
            if (typeof player.play === 'function') {
                player.play();
            }
        }

        if (player.nowPlayingCover) {
            player.nowPlayingCover.src = track.img_url || 'images/default-cover.jpg';
            player.nowPlayingCover.alt = track.title || 'Трек';
        }

        if (player.trackInfoSpan) {
            const title = track.title || 'Без названия';
            const artist = track.artist_name || 'Неизвестный исполнитель';
            player.trackInfoSpan.textContent = `${title} - ${artist}`;
        }
    }

    hideResults() {
        // Спрятать только попап с подсказками
        const popup = document.getElementById('search-results-popup');
        if (popup) {
            popup.style.display = 'none';
        }
    }

    // Полная очистка результатов поиска:
    // вызывается ТОЛЬКО когда поле поиска стало пустым
    clearSearchResults() {
        // Сначала просто спрячем попап
        this.hideResults();

        // Потом уберём блок результатов на странице (если он есть)
        const pageResults = document.getElementById('search-results-page');
        if (pageResults) {
            pageResults.remove(); // или pageResults.innerHTML = '';
        }
    }

    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    formatDuration(seconds) {
        const s = Number(seconds);
        if (!Number.isFinite(s) || s <= 0) return '0:00';
        const mins = Math.floor(s / 60);
        const secs = Math.floor(s % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.searchManager = new SearchManager();
});