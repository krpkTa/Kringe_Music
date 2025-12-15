// scripts/player.js

class MusicPlayer {
    constructor() {
        console.log('🎵 Инициализация MusicPlayer...');
        
        this.audio = new Audio();
        this.isPlaying = false;
        this.isShuffle = false;
        this.repeatMode = 0;
        this.currentTrackIndex = 0;
        this.playlist = [];
        this.originalPlaylist = [];
        
        this.initElements();
        this.loadTracksFromDB();
    }
    
    async loadTracksFromDB() {
        try {
            console.log('🔄 Загрузка треков из БД...');
            
            const response = await fetch('php_scripts/api.php?action=getAllTracks');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📦 Ответ от API:', data);
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (!Array.isArray(data) || data.length === 0) {
                throw new Error('В базе данных нет треков');
            }
            
            this.playlist = data.map(track => ({
                id: track.id,
                title: track.title || 'Без названия',
                artist: track.artist_name || 'Неизвестный исполнитель',
                genre: track.artist_genre || '',
                duration: parseInt(track.duration) || 0,
                src: track.track_url,
                cover: track.img_url || 'images/default-cover.jpg'
            }));
            
            this.originalPlaylist = [...this.playlist];
            
            console.log('✅ Загружено треков:', this.playlist.length);
            console.log('📋 Плейлист:', this.playlist);
            
            this.initEventListeners();
            
            if (this.playlist.length > 0) {
                this.loadTrack(0);
            }
            
        } catch (error) {
            console.error('❌ Ошибка загрузки треков:', error);
            alert(
                '❌ Не удалось загрузить треки из базы данных\n\n' +
                'Ошибка: ' + error.message + '\n\n' +
                'Откройте php_scripts/test.php для диагностики'
            );
        }
    }
    
    initElements() {
        this.playPauseBtn = document.querySelector('.play-pause-btn');
        this.prevBtn = document.querySelector('.prev-btn');
        this.nextBtn = document.querySelector('.next-btn');
        this.shuffleBtn = document.querySelector('.shuffle-btn');
        this.repeatBtn = document.querySelector('.repeat-btn');
        this.volumeSlider = document.querySelector('.volume');
        this.volumeIcon = document.querySelector('.volume-icon');
        this.progressBar = document.querySelector('progress');
        this.timeCurrent = document.querySelector('.time-current');
        this.timeTotal = document.querySelector('.time-total');
        this.nowPlayingCover = document.querySelector('.now-playing-cover');
        this.trackInfoSpan = document.querySelector('.track-info span');
    }
    
    initEventListeners() {
        this.playPauseBtn?.addEventListener('click', () => this.togglePlayPause());
        this.prevBtn?.addEventListener('click', () => this.previousTrack());
        this.nextBtn?.addEventListener('click', () => this.nextTrack());
        this.shuffleBtn?.addEventListener('click', () => this.toggleShuffle());
        this.repeatBtn?.addEventListener('click', () => this.toggleRepeat());
        this.volumeSlider?.addEventListener('input', (e) => this.changeVolume(e.target.value));
        this.volumeIcon?.addEventListener('click', () => this.toggleMute());
        this.progressBar?.addEventListener('click', (e) => this.seek(e));
        
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.handleTrackEnd());
        this.audio.addEventListener('loadedmetadata', () => this.updateDuration());
        this.audio.addEventListener('error', (e) => this.handleAudioError(e));
        this.audio.addEventListener('canplay', () => console.log('✅ Трек готов'));
        
        if (this.volumeSlider) {
            this.audio.volume = this.volumeSlider.value / 100;
        }
        
        this.initTrackCardListeners();
    }
    
    handleAudioError(e) {
        const track = this.playlist[this.currentTrackIndex];
        console.error('❌ Ошибка аудио:', {
            code: this.audio.error?.code,
            message: this.audio.error?.message,
            src: this.audio.src,
            track: track
        });
        
        const errors = {
            1: 'Загрузка прервана',
            2: 'Ошибка сети',
            3: 'Ошибка декодирования',
            4: 'Формат не поддерживается или файл не найден'
        };
        
        const msg = errors[this.audio.error?.code] || 'Неизвестная ошибка';
        alert(
            `❌ Ошибка воспроизведения: ${msg}\n\n` +
            `Трек: ${track?.title}\n` +
            `Путь: ${track?.src}\n\n` +
            `Откройте php_scripts/test.php для проверки файлов`
        );
    }
    
    initTrackCardListeners() {
        const trackCards = document.querySelectorAll('.track-card');
        trackCards.forEach((card, index) => {
            card.addEventListener('click', () => {
                if (index < this.playlist.length) {
                    this.currentTrackIndex = index;
                    this.loadTrack(index);
                    this.play();
                }
            });
        });
    }
    
    loadTrack(index) {
        if (!this.playlist || this.playlist.length === 0) {
            console.warn('⚠️ Плейлист пуст');
            return;
        }
        
        const track = this.playlist[index];
        console.log(`🎵 Загрузка: ${track.title}`);
        console.log(`📁 Путь: ${track.src}`);
        
        this.audio.src = track.src;
        
        if (this.nowPlayingCover) {
            this.nowPlayingCover.src = track.cover;
            this.nowPlayingCover.onerror = () => {
                this.nowPlayingCover.src = 'images/default-cover.jpg';
            };
        }
        
        if (this.trackInfoSpan) {
            this.trackInfoSpan.textContent = `${track.title} - ${track.artist}`;
        }
        
        document.title = `${track.title} - ${track.artist} | Kringe-Music`;
    }
    
    togglePlayPause() {
        this.isPlaying ? this.pause() : this.play();
    }
    
    play() {
        const playPromise = this.audio.play();
        if (playPromise !== undefined) {
            playPromise
                .then(() => {
                    this.isPlaying = true;
                    this.updatePlayPauseButton();
                    console.log('▶️ Воспроизведение');
                })
                .catch((error) => {
                    console.error('❌ Ошибка play():', error);
                    alert('Не удалось воспроизвести трек');
                });
        }
    }
    
    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.updatePlayPauseButton();
        console.log('⏸️ Пауза');
    }
    
    updatePlayPauseButton() {
        const svg = this.playPauseBtn?.querySelector('svg');
        if (!svg) return;
        
        if (this.isPlaying) {
            svg.innerHTML = '<path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>';
            this.playPauseBtn.setAttribute('aria-label', 'Пауза');
        } else {
            svg.innerHTML = '<path d="M8 5v14l11-7z"/>';
            this.playPauseBtn.setAttribute('aria-label', 'Воспроизвести');
        }
    }
    
    previousTrack() {
        if (this.audio.currentTime > 3) {
            this.audio.currentTime = 0;
            return;
        }
        
        this.currentTrackIndex = this.currentTrackIndex > 0 
            ? this.currentTrackIndex - 1 
            : this.playlist.length - 1;
        
        this.loadTrack(this.currentTrackIndex);
        if (this.isPlaying) this.play();
    }
    
    nextTrack() {
        this.currentTrackIndex = this.currentTrackIndex < this.playlist.length - 1 
            ? this.currentTrackIndex + 1 
            : 0;
        
        this.loadTrack(this.currentTrackIndex);
        if (this.isPlaying) this.play();
    }
    
    toggleShuffle() {
        this.isShuffle = !this.isShuffle;
        
        if (this.isShuffle) {
            this.shuffleBtn?.classList.add('active');
            this.shufflePlaylist();
            console.log('🔀 Перемешивание включено');
        } else {
            this.shuffleBtn?.classList.remove('active');
            this.playlist = [...this.originalPlaylist];
            console.log('🔀 Перемешивание выключено');
        }
    }
    
    shufflePlaylist() {
        const currentTrack = this.playlist[this.currentTrackIndex];
        for (let i = this.playlist.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [this.playlist[i], this.playlist[j]] = [this.playlist[j], this.playlist[i]];
        }
        this.currentTrackIndex = this.playlist.findIndex(t => t === currentTrack);
    }
    
    toggleRepeat() {
        this.repeatMode = (this.repeatMode + 1) % 3;
        this.repeatBtn?.classList.remove('active', 'repeat-one');
        
        if (this.repeatMode === 1) {
            this.repeatBtn?.classList.add('active');
            console.log('🔁 Повтор всех');
        } else if (this.repeatMode === 2) {
            this.repeatBtn?.classList.add('active', 'repeat-one');
            console.log('🔂 Повтор одного');
        } else {
            console.log('➡️ Повтор выключен');
        }
    }
    
    handleTrackEnd() {
        if (this.repeatMode === 2) {
            this.audio.currentTime = 0;
            this.play();
        } else if (this.currentTrackIndex < this.playlist.length - 1) {
            this.nextTrack();
        } else if (this.repeatMode === 1) {
            this.currentTrackIndex = 0;
            this.loadTrack(0);
            this.play();
        } else {
            this.isPlaying = false;
            this.updatePlayPauseButton();
        }
    }
    
    changeVolume(value) {
        this.audio.volume = value / 100;
        this.updateVolumeIcon(value);
    }
    
    updateVolumeIcon(volume) {
        const svg = this.volumeIcon?.querySelector('svg');
        if (!svg) return;
        
        if (volume == 0) {
            svg.innerHTML = '<path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>';
        } else if (volume < 50) {
            svg.innerHTML = '<path d="M7 9v6h4l5 5V4l-5 5H7z"/>';
        } else {
            svg.innerHTML = '<path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>';
        }
    }
    
    toggleMute() {
        if (this.audio.volume > 0) {
            this.audio.dataset.previousVolume = this.audio.volume;
            this.audio.volume = 0;
            if (this.volumeSlider) this.volumeSlider.value = 0;
            this.updateVolumeIcon(0);
        } else {
            const vol = parseFloat(this.audio.dataset.previousVolume) || 0.5;
            this.audio.volume = vol;
            if (this.volumeSlider) this.volumeSlider.value = vol * 100;
            this.updateVolumeIcon(vol * 100);
        }
    }
    
    seek(e) {
        if (!this.audio.duration) return;
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    }
    
    updateProgress() {
        if (this.audio.duration && this.progressBar && this.timeCurrent) {
            const percent = (this.audio.currentTime / this.audio.duration) * 100;
            this.progressBar.value = percent;
            this.timeCurrent.textContent = this.formatTime(this.audio.currentTime);
        }
    }
    
    updateDuration() {
        if (this.timeTotal && this.audio.duration) {
            this.timeTotal.textContent = this.formatTime(this.audio.duration);
        }
    }
    
    formatTime(seconds) {
        if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('🎵 DOM загружен, создаем плеер...');
    const player = new MusicPlayer();
    window.musicPlayer = player;
});