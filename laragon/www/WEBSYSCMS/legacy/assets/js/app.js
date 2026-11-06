(function () {
    'use strict';

    var body = document.body;

    var header = document.querySelector('[data-header]');
    if (header) {
        var syncHeader = function () {
            if (window.scrollY > 8) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        };
        syncHeader();
        window.addEventListener('scroll', syncHeader, { passive: true });
    }

    var navToggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-toast]').forEach(function (toast) {
        var close = toast.querySelector('[data-toast-close]');

        var hide = function () {
            toast.classList.add('is-hiding');
            window.setTimeout(function () {
                toast.remove();
            }, 220);
        };

        if (close) {
            close.addEventListener('click', hide);
        }

        window.setTimeout(hide, 6000);
    });

    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!target) {
                return;
            }
            var show = target.type === 'password';
            target.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    document.querySelectorAll('[data-demo-email]').forEach(function (button) {
        button.addEventListener('click', function () {
            var email = document.getElementById('email');
            var password = document.getElementById('password');

            if (email) {
                email.value = button.getAttribute('data-demo-email');
            }
            if (password) {
                password.value = button.getAttribute('data-demo-password');
            }

            if (email) {
                email.focus();
            }
        });
    });

    var shell = document.querySelector('[data-app-shell]');
    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    var sidebarClose = document.querySelector('[data-sidebar-close]');

    if (shell && sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            body.classList.toggle('sidebar-open');
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function () {
            body.classList.remove('sidebar-open');
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm') || 'Are you sure you want to continue?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('a[data-confirm]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var message = link.getAttribute('data-confirm') || 'Are you sure you want to continue?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (nav && navToggle && nav.classList.contains('is-open')) {
            if (!nav.contains(event.target) && !navToggle.contains(event.target)) {
                nav.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        }

        var menu = document.querySelector('.user-menu[open]');
        if (menu && !menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
})();
