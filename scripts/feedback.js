class FeedbackManager {
    constructor() {
        this.form = document.getElementById('feedbackForm');
        this.messageContainer = document.getElementById('feedback-message');
        this.userDisplay = document.getElementById('user-display');
        this.userEmail = document.getElementById('user-email');
        
        this.init();
    }
    
    async init() {
        
        await this.checkUserAuth();
        
        
        if (this.form) {
            this.setupFormValidation();
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        
        this.autoFillEmail();
    }
    
    async checkUserAuth() {
        try {
            
            const response = await fetch('/php_scripts/get_user_session.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            if (response.ok) {
                const text = await response.text();
                
                
                try {
                    const userData = JSON.parse(text);
                    
                    if (userData.logged_in) {
                        localStorage.setItem('userData', JSON.stringify(userData));
                        this.updateUserDisplay(userData);
                        return;
                    }
                } catch (parseError) {
                    console.error('Error parsing user data:', parseError);
                }
            }
        } catch (error) {
            console.error('Error checking user auth:', error);
        }
        
        
        const storedData = localStorage.getItem('userData');
        if (storedData) {
            try {
                const user = JSON.parse(storedData);
                if (user.logged_in) {
                    this.updateUserDisplay(user);
                    return;
                }
            } catch (error) {
                console.error('Error parsing stored user data:', error);
            }
        }
        
        
        this.showGuestDisplay();
    }
    
    showGuestDisplay() {
        if (this.userDisplay) {
            this.userDisplay.textContent = 'Гость';
            this.userDisplay.classList.remove('logged-in');
        }
        if (this.userEmail) {
            this.userEmail.textContent = '';
            this.userEmail.style.display = 'none';
        }
    }
    
    updateUserDisplay(user) {
        if (this.userDisplay) {
            this.userDisplay.textContent = user.login || user.email || 'Пользователь';
            this.userDisplay.classList.add('logged-in');
        }
        if (this.userEmail) {
            if (user.email) {
                this.userEmail.textContent = user.email;
                this.userEmail.style.display = 'inline';
            } else {
                this.userEmail.style.display = 'none';
            }
        }
    }
    
    autoFillEmail() {
        const userData = localStorage.getItem('userData');
        if (userData) {
            try {
                const user = JSON.parse(userData);
                if (user.email && user.logged_in) {
                    const emailField = document.getElementById('feedback-email');
                    if (emailField) {
                        emailField.value = user.email;
                        emailField.readOnly = true;
                        emailField.style.backgroundColor = 'rgba(64, 153, 153, 0.1)';
                    }
                }
            } catch (error) {
                console.error('Error auto-filling email:', error);
            }
        }
    }
    
    setupFormValidation() {
        
        const textFields = ['email', 'comment'];
        
        textFields.forEach(field => {
            const element = document.getElementById(`feedback-${field}`);
            if (element) {
                element.addEventListener('input', () => {
                    this.clearFieldError(field);
                });
                
                element.addEventListener('blur', () => {
                    this.validateField(field, element.value);
                });
            }
        });
        
        
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        ratingInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.clearFieldError('rating');
            });
        });
    }
    
    clearFieldError(field) {
        const errorElement = document.getElementById(`feedback-${field}-error`);
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }
    
    validateField(field, value) {
        let isValid = true;
        let errorMessage = '';
        
        switch (field) {
            case 'email':
                if (!value || !value.trim()) {
                    errorMessage = 'Email обязателен для заполнения';
                    isValid = false;
                } else if (!this.isValidEmail(value)) {
                    errorMessage = 'Введите корректный email адрес';
                    isValid = false;
                }
                break;
                
            case 'rating':
                if (!value) {
                    errorMessage = 'Пожалуйста, выберите оценку';
                    isValid = false;
                } else if (parseInt(value) < 1 || parseInt(value) > 5) {
                    errorMessage = 'Оценка должна быть от 1 до 5';
                    isValid = false;
                }
                break;
                
            case 'comment':
                if (!value || !value.trim()) {
                    errorMessage = 'Комментарий обязателен для заполнения';
                    isValid = false;
                } else if (value.trim().length < 10) {
                    errorMessage = 'Комментарий должен содержать минимум 10 символов';
                    isValid = false;
                } else if (value.trim().length > 1000) {
                    errorMessage = 'Комментарий не должен превышать 1000 символов';
                    isValid = false;
                }
                break;
        }
        
        if (!isValid) {
            this.displayFieldError(field, errorMessage);
        }
        
        return isValid;
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    displayFieldError(field, message) {
        const errorElement = document.getElementById(`feedback-${field}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }
    
    showMessage(message, type) {
        if (this.messageContainer) {
            this.messageContainer.textContent = message;
            this.messageContainer.className = `feedback-message ${type}`;
            this.messageContainer.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    this.messageContainer.style.display = 'none';
                }, 5000);
            }
        }
    }
    
    validateForm() {
        let isValid = true;
        
        
        const emailElement = document.getElementById('feedback-email');
        if (!this.validateField('email', emailElement ? emailElement.value : '')) {
            isValid = false;
        }
        
        
        const ratingElement = document.querySelector('input[name="rating"]:checked');
        if (!this.validateField('rating', ratingElement ? ratingElement.value : '')) {
            isValid = false;
        }
        
        
        const commentElement = document.getElementById('feedback-comment');
        if (!this.validateField('comment', commentElement ? commentElement.value : '')) {
            isValid = false;
        }
        
        return isValid;
    }
    
    async handleSubmit(event) {
        event.preventDefault();
        
        
        if (!this.validateForm()) {
            this.showMessage('Пожалуйста, исправьте ошибки в форме', 'error');
            return;
        }
        
        const ratingElement = document.querySelector('input[name="rating"]:checked');
        
        const formData = {
            email: document.getElementById('feedback-email').value.trim(),
            rating: ratingElement ? ratingElement.value : null,
            comment: document.getElementById('feedback-comment').value.trim()
        };
        
        
        const submitBtn = this.form.querySelector('.btn-submit');
        let originalText = 'Отправить отзыв';
        if (submitBtn) {
            originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
        }
        
        try {
            const response = await fetch('/php_scripts/feedback_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const responseText = await response.text();
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Server response:', responseText.substring(0, 500));
                throw new Error('Сервер вернул некорректный ответ');
            }
            
            if (result.success) {
                this.showMessage(result.message || 'Спасибо за ваш отзыв!', 'success');
                this.form.reset();
                this.autoFillEmail();
            } else {
                this.showMessage(result.message || 'Ошибка при отправке', 'error');
                
                if (result.errors && typeof result.errors === 'object') {
                    Object.keys(result.errors).forEach(field => {
                        this.displayFieldError(field, result.errors[field]);
                    });
                }
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            this.showMessage('Произошла ошибка при отправке. Попробуйте еще раз.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    }
}


document.addEventListener('DOMContentLoaded', () => {
    window.feedbackManager = new FeedbackManager();
});