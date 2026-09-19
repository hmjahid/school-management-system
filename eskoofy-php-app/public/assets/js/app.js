document.addEventListener('DOMContentLoaded', function () {
    // Mobile menu toggle (public site header)
    var mobileMenuBtn = document.getElementById('mobile-menu-btn');
    var mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function (e) {
            e.preventDefault();
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Auto-hide flash messages after 5 seconds
    var flashMessages = document.querySelectorAll('[class*="border-l-4"]');
    flashMessages.forEach(function (el) {
        setTimeout(function () {
            el.style.display = 'none';
        }, 5000);
    });
});
