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

    var notificationControl = document.querySelector('[data-notification-poll]');
    if (notificationControl) {
        var notificationUrl = notificationControl.getAttribute('data-notification-url');
        var notificationTarget = notificationControl.getAttribute('data-notification-target');
        var notificationStatus = document.querySelector('[data-notification-status]');
        var notificationToasts = document.querySelector('[data-notification-toasts]');
        var lastNotificationId = Number(notificationControl.getAttribute('data-last-notification-id') || 0);
        var pollingNotifications = false;

        function updateNotificationCount(count) {
            document.querySelectorAll('[data-notification-count]').forEach(function (badge) {
                badge.textContent = String(count);
                badge.hidden = count === 0;
            });

            notificationControl.setAttribute('aria-label', count + ' unread notifications');
        }

        function appendNotification(notification) {
            var toast = document.createElement('div');
            var title = document.createElement('strong');
            var link = document.createElement('a');
            var close = document.createElement('button');

            toast.className = 'toast toast-info notification-toast';
            title.textContent = notification.title || 'New notification';
            link.href = notificationTarget;
            link.appendChild(title);
            link.appendChild(document.createElement('br'));
            link.appendChild(document.createTextNode(notification.body || ''));
            close.type = 'button';
            close.className = 'toast-close';
            close.setAttribute('aria-label', 'Dismiss notification');
            close.textContent = '×';
            close.addEventListener('click', function () {
                toast.remove();
            });
            toast.appendChild(link);
            toast.appendChild(close);
            notificationToasts.appendChild(toast);
            window.setTimeout(function () {
                toast.remove();
            }, 10000);

            if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
                var browserNotification = new Notification(notification.title || 'New notification', {
                    body: notification.body || ''
                });

                browserNotification.onclick = function () {
                    window.focus();
                    window.location.href = notificationTarget;
                };
            }
        }

        function pollNotifications() {
            if (pollingNotifications) {
                return;
            }

            pollingNotifications = true;
            fetch(notificationUrl + '?after_id=' + lastNotificationId, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Notification refresh failed with status ' + response.status);
                    }

                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || !Array.isArray(payload.notifications)) {
                        throw new Error('Notification response was invalid.');
                    }

                    payload.notifications.forEach(function (notification) {
                        lastNotificationId = Math.max(lastNotificationId, Number(notification.id || 0));
                        appendNotification(notification);
                    });

                    notificationControl.setAttribute('data-last-notification-id', String(lastNotificationId));
                    updateNotificationCount(Number(payload.notification_count || 0));
                    notificationStatus.textContent = '';
                })
                .catch(function () {
                    notificationStatus.textContent = 'Notifications are temporarily unavailable. Retrying.';
                })
                .then(function () {
                    pollingNotifications = false;
                });
        }

        pollNotifications();
        window.setInterval(pollNotifications, 3000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollNotifications();
            }
        });
    }

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
