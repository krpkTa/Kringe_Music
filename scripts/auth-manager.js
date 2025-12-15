class AuthManager {
    constructor() {
        this.userDisplay = document.getElementById('user-display');
        this.userEmail = document.getElementById('user-email');
        this.loginBtn = document.querySelector('.login-btn');
        this.userInfo = document.querySelector('.user-info');
        
        this.init();
    }
    
    init() {
        this.updateUI();
        this.setupEventListeners();
        this.checkAuthStatus();
    }
    
    updateUI() {
        const user = this.getUserData();
        
        if (user && user.logged_in) {
            if (this.userDisplay) {
                this.userDisplay.textContent = user.login || user.email;
            }
            
            if (this.userEmail && user.email) {
                this.userEmail.textContent = user.email;
            }
            
            if (this.loginBtn) {
                this.loginBtn.textContent = 'Выйти';
                this.loginBtn.dataset.action = 'logout';
                this.loginBtn.classList.add('logout');
            }
            
            if (this.userInfo) {
                this.userInfo.style.display = 'flex';
            }
        } else {

            if (this.userDisplay) {
                this.userDisplay.textContent = 'Гость';
            }
            
            if (this.userEmail) {
                this.userEmail.textContent = '';
            }
            
            if (this.loginBtn) {
                this.loginBtn.textContent = 'Вход';
                this.loginBtn.dataset.action = 'login';
                this.loginBtn.classList.remove('logout');
            }
        }
    }
    
    getUserData() {
        try {
            const userData = localStorage.getItem('userData');
            return userData ? JSON.parse(userData) : null;
        } catch (e) {
            console.error('Error parsing user data:', e);
            return null;
        }
    }
    
    saveUserData(userData) {
        localStorage.setItem('userData', JSON.stringify(userData));
        this.updateUI();
    }
    
    clearUserData() {
        localStorage.removeItem('userData');
        this.updateUI();
    }
    
    setupEventListeners() {
        if (this.loginBtn) {
            this.loginBtn.removeEventListener('click', this.handleAuthClick);
            this.loginBtn.addEventListener('click', (e) => this.handleAuthClick(e));
        }
    }
    
    handleAuthClick(e) {
        e.preventDefault();
        
        const action = this.loginBtn.dataset.action;
        
        if (action === 'login') {
            const currentPage = window.location.pathname + window.location.search;
            window.location.href = `/login?from=${encodeURIComponent(currentPage)}`;
        } else if (action === 'logout') {
            this.logout();
        }
    }
    
    async logout() {
        try {
            const response = await fetch('/php_scripts/auth.php?action=logout', {
                method: 'GET',
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.clearUserData();
                
                if (window.location.pathname === '/login') {
                    window.location.href = '/';
                } else {
                    window.location.reload();
                }
            } else {
                alert('Ошибка при выходе: ' + (result.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Logout error:', error);
            alert('Ошибка при выходе');
        }
    }
    
    async checkAuthStatus() {
        try {
            const response = await fetch('/php_scripts/get_user_session.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            if (response.ok) {
                const userData = await response.json();
                if (userData.logged_in) {
                    this.saveUserData(userData);
                    return true;
                }
            }
        } catch (error) {
            console.error('Auth check error:', error);
        }
        
        return false;
    }
}

window.updateUserAfterLogin = function(userData) {
    if (window.authManager) {
        window.authManager.saveUserData(userData);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.authManager = new AuthManager();
});