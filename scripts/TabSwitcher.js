// scripts/TabSwitcher.js
class TabSwitcher {
    constructor(container = document) {
        this.container = container;
        this.tabs = this.container.querySelectorAll('.tab');
        this.forms = this.container.querySelectorAll('.form-container');
    }
    
    init() {
        this.tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.switchToTab(tab.dataset.tab);
            });
        });
        
        // Инициализируем активную вкладку
        const activeTab = this.container.querySelector('.tab.active');
        if (activeTab) {
            this.switchToTab(activeTab.dataset.tab);
        }
    }
    
    switchToTab(tabName) {
        // Деактивируем все табы
        this.tabs.forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        
        // Скрываем все формы
        this.forms.forEach(form => {
            form.classList.remove('active');
            form.setAttribute('aria-hidden', 'true');
        });
        
        // Активируем выбранный таб и форму
        const targetTab = this.container.querySelector(`.tab[data-tab="${tabName}"]`);
        const targetForm = this.container.querySelector(`#${tabName}-form`);
        
        if (targetTab) {
            targetTab.classList.add('active');
            targetTab.setAttribute('aria-selected', 'true');
        }
        
        if (targetForm) {
            targetForm.classList.add('active');
            targetForm.setAttribute('aria-hidden', 'false');
        }
        
        // Очищаем ошибки при переключении табов
        this.clearFormErrors();
    }
    
    clearFormErrors() {
        const errorElements = this.container.querySelectorAll('.error-message');
        errorElements.forEach(el => {
            el.textContent = '';
        });
        
        const inputs = this.container.querySelectorAll('input');
        inputs.forEach(input => {
            input.classList.remove('error');
        });
    }
}