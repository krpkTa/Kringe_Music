// scripts/auth.js
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Инициализация табов
        window.tabSwitcher = new TabSwitcher();
        window.tabSwitcher.init();
        
        // Инициализация валидации форм
        window.formValidator = new FormValidator();
        window.formValidator.init();
        
        // Автоматически заполняем форму входа, если есть данные в localStorage
        this.autoFillLoginForm();
        
        // Инициализация AuthManager на странице логина
        if (typeof AuthManager !== 'undefined') {
            window.authManager = new AuthManager();
        }
        
        console.log('Auth system initialized successfully');
    } catch (error) {
        console.error('Error initializing auth system:', error);
    }
});

// Функция для авто-заполнения формы
function autoFillLoginForm() {
    try {
        const userData = JSON.parse(localStorage.getItem('userData'));
        if (userData && userData.email) {
            const emailField = document.getElementById('login-email');
            if (emailField) {
                emailField.value = userData.email;
            }
        }
    } catch (error) {
        console.error('Error auto-filling login form:', error);
    }
}

// Модифицируем класс FormValidator
class FormValidator {
    constructor() {
        this.forms = {
            login: document.getElementById('loginForm'),
            register: document.getElementById('registerForm')
        };
        this.validationRules = {
            login: this.validateLogin.bind(this),
            register: this.validateRegister.bind(this)
        };
    }
    
    init() {
        Object.entries(this.forms).forEach(([formName, formElement]) => {
            if (formElement) {
                formElement.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.validationRules[formName]();
                });
            }
        });
        
        this.setupRealTimeValidation();
    }
    
    validateLogin() {
        const data = {
            email: this.getValue('login-email'),
            password: this.getValue('login-password')
        };
        
        const errors = [];
        
        if (!data.email.trim()) {
            errors.push({ field: 'login-email', message: 'Введите email или имя пользователя' });
        }
        
        if (!data.password) {
            errors.push({ field: 'login-password', message: 'Введите пароль' });
        } else if (data.password.length < 6) {
            errors.push({ field: 'login-password', message: 'Пароль должен содержать минимум 6 символов' });
        }
        
        this.displayErrors(errors);
        
        if (errors.length === 0) {
            this.submitLogin(data);
        }
    }
    
    validateRegister() {
        const data = {
            username: this.getValue('register-username'),
            email: this.getValue('register-email'),
            password: this.getValue('register-password'),
            confirmPassword: this.getValue('register-confirm-password')
        };
        
        const errors = [];
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!data.username.trim()) {
            errors.push({ field: 'register-username', message: 'Введите имя пользователя' });
        } else if (data.username.length < 3) {
            errors.push({ field: 'register-username', message: 'Имя пользователя должно содержать минимум 3 символа' });
        }
        
        if (!data.email.trim()) {
            errors.push({ field: 'register-email', message: 'Введите email' });
        } else if (!emailRegex.test(data.email)) {
            errors.push({ field: 'register-email', message: 'Введите корректный email' });
        }
        
        if (!data.password) {
            errors.push({ field: 'register-password', message: 'Введите пароль' });
        } else if (data.password.length < 6) {
            errors.push({ field: 'register-password', message: 'Пароль должен содержать минимум 6 символов' });
        }
        
        if (!data.confirmPassword) {
            errors.push({ field: 'register-confirm-password', message: 'Подтвердите пароль' });
        } else if (data.password !== data.confirmPassword) {
            errors.push({ field: 'register-confirm-password', message: 'Пароли не совпадают' });
        }
        
        this.displayErrors(errors);
        
        if (errors.length === 0) {
            this.submitRegister(data);
        }
    }
    
    getValue(fieldId) {
        const element = document.getElementById(fieldId);
        return element ? element.value : '';
    }
    
    displayErrors(errors) {
        // Очищаем все ошибки
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
        });
        
        // Показываем новые ошибки
        errors.forEach(error => {
            const errorElement = document.getElementById(`${error.field}-error`);
            if (errorElement) {
                errorElement.textContent = error.message;
            }
        });
    }
    
    setupRealTimeValidation() {
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                const errorElement = document.getElementById(`${input.id}-error`);
                if (errorElement) {
                    errorElement.textContent = '';
                }
            });
        });
    }
    
    submitLogin(data) {
        console.log('Login data:', data);
        
        // Добавляем параметр запоминания страницы-источника
        const urlParams = new URLSearchParams(window.location.search);
        const returnTo = urlParams.get('from') || '/';
        
        // Показываем индикатор загрузки
        const submitBtn = document.querySelector('#loginForm .btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Вход...';
        
        fetch('auth.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                email: data.email,
                password: data.password
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Сохраняем данные пользователя в localStorage
                if (result.userData) {
                    localStorage.setItem('userData', JSON.stringify(result.userData));
                    
                    // Вызываем глобальную функцию для обновления UI
                    if (typeof window.updateUserAfterLogin === 'function') {
                        window.updateUserAfterLogin(result.userData);
                    }
                }
                
                this.displayMessage(result.message, 'success');
                
                // Редирект с задержкой
                setTimeout(() => {
                    window.location.href = returnTo;
                }, 1500);
            } else {
                this.displayMessage(result.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.displayMessage('Произошла ошибка при входе', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }
    
    submitRegister(data) {
        console.log('Register data:', data);
        
        // Показываем индикатор загрузки
        const submitBtn = document.querySelector('#registerForm .btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Регистрация...';
        
        fetch('auth.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'username': data.username,
                'email': data.email,
                'password': data.password,
                'confirm_password': data.confirmPassword
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Сохраняем данные пользователя в localStorage
                if (result.userData) {
                    localStorage.setItem('userData', JSON.stringify(result.userData));
                    
                    // Вызываем глобальную функцию для обновления UI
                    if (typeof window.updateUserAfterLogin === 'function') {
                        window.updateUserAfterLogin(result.userData);
                    }
                }
                
                this.displayMessage(result.message, 'success');
                
                // Автоматически переключаем на вкладку логина
                setTimeout(() => {
                    window.tabSwitcher.switchToTab('login');
                    // Автозаполняем форму логина
                    const emailField = document.getElementById('login-email');
                    if (emailField) {
                        emailField.value = data.email;
                    }
                    // Сбрасываем кнопку
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 1500);
            } else {
                this.displayMessage(result.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.displayMessage('Произошла ошибка при регистрации', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }
    
    displayMessage(message, type) {
        // Создаем или находим контейнер для сообщений
        let messageContainer = document.getElementById('message-container');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.id = 'message-container';
            messageContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 300px;
            `;
            document.body.appendChild(messageContainer);
        }
        
        const messageElement = document.createElement('div');
        messageElement.textContent = message;
        messageElement.style.cssText = `
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
            ${type === 'success' 
                ? 'background: linear-gradient(135deg, #4CAF50, #2E7D32); border: 1px solid rgba(76, 175, 80, 0.5);' 
                : 'background: linear-gradient(135deg, #ff4757, #ff3838); border: 1px solid rgba(244, 67, 54, 0.5);'
            }
        `;
        
        messageContainer.appendChild(messageElement);
        
        // Автоматически удаляем сообщение через 5 секунд
        setTimeout(() => {
            messageElement.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                messageElement.remove();
            }, 300);
        }, 5000);
    }
}

// Добавляем CSS анимации для сообщений
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);