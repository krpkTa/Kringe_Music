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
                this.displayMessage(result.message, 'success');
                setTimeout(() => {
                window.location.href = result.redirect || '/';
            }, 1000);
        } else {
            this.displayMessage(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        this.displayMessage('Произошла ошибка при входе', 'error');
    });
    }
    
    submitRegister(data) {
        console.log('Register data:', data);
        
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
            this.displayMessage(result.message, 'success');
            setTimeout(() => {
                window.location.href = result.redirect || '/';
            }, 1000);
        } else {
            this.displayMessage(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        this.displayMessage('Произошла ошибка при регистрации', 'error');
    });
}

// Добавьте эту вспомогательную функцию для отображения сообщений
displayMessage(message, type) {
    // Создаем или находим контейнер для сообщений
    let messageContainer = document.getElementById('message-container');
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = 'message-container';
        messageContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1000;';
        document.body.appendChild(messageContainer);
    }
    
    const messageElement = document.createElement('div');
    messageElement.textContent = message;
    messageElement.style.cssText = `
        padding: 15px;
        margin: 10px 0;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        max-width: 300px;
        ${type === 'success' ? 'background: #4CAF50;' : 'background: #f44336;'}
    `;
    
    messageContainer.appendChild(messageElement);
    
    // Автоматически удаляем сообщение через 5 секунд
    setTimeout(() => {
        messageElement.remove();
    }, 5000);}
}