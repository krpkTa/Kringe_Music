// auth.js - обработка авторизации

// Переключение между вкладках
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const tabName = tab.getAttribute('data-tab');
        
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));
        
        tab.classList.add('active');
        document.getElementById(`${tabName}-form`).classList.add('active');
    });
});

// Обработка формы входа
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Показываем загрузку
    submitBtn.textContent = 'Вход...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message, 'success');
            if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            }
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Ошибка сети', 'error');
    } finally {
        submitBtn.textContent = 'Войти';
        submitBtn.disabled = false;
    }
});

// Обработка формы регистрации
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Проверка совпадения паролей
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('register-confirm-password').value;
    
    if (password !== confirmPassword) {
        showMessage('Пароли не совпадают', 'error');
        // Подсвечиваем поле подтверждения пароля
        const confirmPasswordField = document.getElementById('register-confirm-password');
        const errorElement = document.getElementById('register-confirm-password-error');
        
        if (errorElement) {
            errorElement.textContent = 'Пароли не совпадают';
            errorElement.style.display = 'block';
        }
        
        if (confirmPasswordField) {
            confirmPasswordField.style.borderColor = '#ff6b6b';
        }
        
        return; // Прерываем выполнение
    }
    
    // Показываем загрузку
    submitBtn.textContent = 'Регистрация...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message, 'success');
            // УБРАН автоматический переход на главную страницу
            // Пользователь остается на странице регистрации
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Ошибка сети', 'error');
    } finally {
        submitBtn.textContent = 'Зарегистрироваться';
        submitBtn.disabled = false;
    }
});

// Функция показа сообщений
function showMessage(message, type) {
    // Создаем или находим контейнер для сообщений
    let messageContainer = document.querySelector('.message-container');
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.className = 'message-container';
        // Вставляем перед формами
        const authContainer = document.querySelector('.auth-container');
        const tabs = document.querySelector('.tabs');
        if (tabs && authContainer) {
            authContainer.insertBefore(messageContainer, tabs.nextSibling);
        } else {
            document.querySelector('.auth-container').prepend(messageContainer);
        }
    }
    
    messageContainer.innerHTML = `
        <div class="message ${type}">
            ${message}
        </div>
    `;
    
    setTimeout(() => {
        if (messageContainer && messageContainer.innerHTML) {
            messageContainer.innerHTML = '';
        }
    }, 5000);
}

// Очистка ошибок при вводе в поле подтверждения пароля
document.getElementById('register-confirm-password')?.addEventListener('input', function() {
    const errorElement = document.getElementById('register-confirm-password-error');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
    this.style.borderColor = '';
});