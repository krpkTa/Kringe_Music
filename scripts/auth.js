document.addEventListener('DOMContentLoaded', function() {
    try {
        window.tabSwitcher = new TabSwitcher();
        window.tabSwitcher.init();
        
        window.formValidator = new FormValidator();
        window.formValidator.init();
        
        console.log('Auth system initialized successfully');
    } catch (error) {
        console.error('Error initializing auth system:', error);
    }
});