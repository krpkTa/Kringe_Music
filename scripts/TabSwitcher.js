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
    }
    
    switchToTab(tabName) {
        this.tabs.forEach(tab => tab.classList.remove('active'));
        this.forms.forEach(form => form.classList.remove('active'));
        
        // Активируем выбранный таб и форму
        const targetTab = this.container.querySelector(`.tab[data-tab="${tabName}"]`);
        const targetForm = this.container.querySelector(`#${tabName}-form`);
        
        if (targetTab) targetTab.classList.add('active');
        if (targetForm) targetForm.classList.add('active');
    }
}