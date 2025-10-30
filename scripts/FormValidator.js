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
        
        // ОТПРАВКА ФОРМЫ НА СЕРВЕР
        
        // Временная заглушка
        alert('Вход выполнен успешно!');
        window.location.href = 'index.html';
    }
    
    submitRegister(data) {
        console.log('Register data:', data);
        
        // ОТПРАВКА ФОРМЫ НА СЕРВЕР
        
        // Временная заглушка
        alert('Регистрация прошла успешно!');
        
        // Переключаем на форму входа
        if (window.tabSwitcher) {
            window.tabSwitcher.switchToTab('login');
        }
    }
}