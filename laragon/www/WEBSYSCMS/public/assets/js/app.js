(function () {
    'use strict';

    var body = document.body;

    function requestCurrentPosition(onSuccess, onError, onFallback) {
        if (window.isSecureContext === false) {
            onError({ message: 'Location needs a secure HTTPS connection. Open this site using its https:// address.' });
            return;
        }
        if (!navigator.geolocation) {
            onError({ message: 'This browser cannot access location. Enter an address or choose a pin on the map.' });
            return;
        }

        navigator.geolocation.getCurrentPosition(onSuccess, function (error) {
            if (error.code === error.PERMISSION_DENIED) {
                onError(error);
                return;
            }
            if (onFallback) {
                onFallback();
            }
            navigator.geolocation.getCurrentPosition(onSuccess, onError, {
                enableHighAccuracy: false,
                maximumAge: 60000,
                timeout: 25000
            });
        }, {
            enableHighAccuracy: true,
            maximumAge: 10000,
            timeout: 12000
        });
    }

    function locationErrorMessage(error) {
        if (error && error.code === error.PERMISSION_DENIED) {
            return 'Location permission is blocked. Allow location for this site in your browser and phone settings, then try again.';
        }
        if (error && error.code === error.POSITION_UNAVAILABLE) {
            return 'Your phone could not determine a location. Turn on Location Services or GPS and try again.';
        }
        if (error && error.code === error.TIMEOUT) {
            return 'Location took too long. Keep this page open, turn on GPS, and try again.';
        }
        if (error && error.message) {
            return error.message;
        }
        return 'Could not get your location. Check your phone settings and try again.';
    }

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
            var targetUrl = notification.target_url || notificationTarget;

            toast.className = 'toast toast-info notification-toast';
            title.textContent = notification.title || 'New notification';
            link.href = targetUrl;
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
                    window.location.href = targetUrl;
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

    document.querySelectorAll('[data-location-form]').forEach(function (form) {
        var address = form.querySelector('[data-location-address]');
        var latitudeInput = form.querySelector('[data-location-latitude]');
        var longitudeInput = form.querySelector('[data-location-longitude]');
        var accuracyInput = form.querySelector('[data-location-accuracy]');
        var status = form.querySelector('[data-location-status]');
        var useLocationButton = form.querySelector('[data-use-location]');
        var savedAddressSelect = form.querySelector('[data-saved-address]');
        var mapElement = form.querySelector('[data-location-picker]');
        var geocodeUrl = form.getAttribute('data-geocode-url');
        var map = null;
        var marker = null;
        var accuracyCircle = null;
        var lastGeocodeAt = 0;

        function setLocation(latitude, longitude, accuracy) {
            latitudeInput.value = String(latitude);
            longitudeInput.value = String(longitude);
            accuracyInput.value = accuracy === null || typeof accuracy === 'undefined' ? '' : String(accuracy);

            if (map && window.L) {
                var point = [latitude, longitude];
                if (!marker) {
                    marker = window.L.marker(point).addTo(map);
                } else {
                    marker.setLatLng(point);
                }
                if (accuracyCircle) {
                    map.removeLayer(accuracyCircle);
                    accuracyCircle = null;
                }
                if (accuracy !== null && typeof accuracy !== 'undefined') {
                    accuracyCircle = window.L.circle(point, {
                        radius: accuracy,
                        color: '#2563eb',
                        fillOpacity: 0.08
                    }).addTo(map);
                }
                map.setView(point, 16);
            }
        }

        function reverseGeocode(latitude, longitude, accuracy) {
            var now = Date.now();
            if (now - lastGeocodeAt < 1000) {
                if (status) {
                    status.textContent = 'Location pin saved. Wait a moment before requesting another address lookup.';
                }
                return;
            }
            lastGeocodeAt = now;
            if (status) {
                status.textContent = 'Finding the nearby address…';
            }

            var query = new URLSearchParams({
                format: 'jsonv2',
                lat: String(latitude),
                lon: String(longitude)
            });
            fetch(geocodeUrl + '?' + query.toString(), {
                credentials: 'omit',
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Address lookup failed with status ' + response.status);
                    }
                    return response.json();
                })
                .then(function (result) {
                    if (!result || typeof result.display_name !== 'string' || !result.display_name.trim()) {
                        throw new Error('No nearby address was returned.');
                    }
                    address.value = result.display_name;
                    if (status) {
                        status.textContent = 'Address filled. GPS reports approximately ±' + Math.round(accuracy) + ' m accuracy; please verify the address.';
                    }
                })
                .catch(function (error) {
                    if (status) {
                        status.textContent = 'Location pin saved, but address lookup failed. Enter or verify the address manually.';
                    }
                    console.error(error);
                });
        }

        if (mapElement && window.L) {
            map = window.L.map(mapElement).setView([12.8797, 121.7740], 5);
            window.L.tileLayer(mapElement.getAttribute('data-tile-url'), {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            window.setTimeout(function () {
                map.invalidateSize({ pan: false });
            }, 150);
            window.addEventListener('resize', function () {
                map.invalidateSize({ pan: false });
            });
            var initialLatitude = Number(latitudeInput.value);
            var initialLongitude = Number(longitudeInput.value);
            if (latitudeInput.value && longitudeInput.value && Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude)) {
                setLocation(initialLatitude, initialLongitude, Number(accuracyInput.value) || null);
            }
            map.on('click', function (event) {
                setLocation(event.latlng.lat, event.latlng.lng, null);
                if (status) {
                    status.textContent = 'Map pin selected. The address was not looked up; type it or use your device location.';
                }
            });
        }

        if (useLocationButton) {
            useLocationButton.addEventListener('click', function () {
                useLocationButton.disabled = true;
                if (status) {
                    status.textContent = 'Requesting your location…';
                }
                requestCurrentPosition(function (position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;
                    var accuracy = position.coords.accuracy;
                    setLocation(latitude, longitude, accuracy);
                    reverseGeocode(latitude, longitude, accuracy);
                    useLocationButton.disabled = false;
                }, function (error) {
                    if (status) {
                        status.textContent = locationErrorMessage(error);
                    }
                    useLocationButton.disabled = false;
                }, function () {
                    if (status) {
                        status.textContent = 'GPS is taking longer. Trying your phone’s network location…';
                    }
                });
            });
        }

        if (savedAddressSelect) {
            savedAddressSelect.addEventListener('change', function () {
                var option = savedAddressSelect.options[savedAddressSelect.selectedIndex];
                if (!option || !option.value) {
                    return;
                }
                address.value = option.getAttribute('data-address') || '';
                var savedLatitude = option.getAttribute('data-latitude');
                var savedLongitude = option.getAttribute('data-longitude');
                if (savedLatitude && savedLongitude) {
                    setLocation(Number(savedLatitude), Number(savedLongitude), Number(option.getAttribute('data-accuracy')) || null);
                    if (status) {
                        status.textContent = 'Saved address and map pin selected.';
                    }
                } else {
                    latitudeInput.value = '';
                    longitudeInput.value = '';
                    accuracyInput.value = '';
                    if (status) {
                        status.textContent = 'Saved address selected without a map pin.';
                    }
                }
            });
        }
    });

    document.querySelectorAll('[data-live-location]').forEach(function (panel) {
        var endpoint = panel.getAttribute('data-endpoint');
        var role = panel.getAttribute('data-user-role');
        var mapElement = panel.querySelector('[data-tracking-map]');
        var status = panel.querySelector('[data-live-location-status]');
        var startButton = panel.querySelector('[data-start-location]');
        var stopButton = panel.querySelector('[data-stop-location]');
        var map = null;
        var customerMarker = null;
        var technicianMarker = null;
        var didFitBounds = false;
        var visibleMarkerCount = 0;
        var watchId = null;
        var lastSentAt = 0;
        var polling = false;

        if (mapElement && window.L) {
            map = window.L.map(mapElement).setView([12.8797, 121.7740], 5);
            window.L.tileLayer(panel.getAttribute('data-tile-url'), {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            window.setTimeout(function () {
                map.invalidateSize({ pan: false });
            }, 150);
            window.addEventListener('resize', function () {
                map.invalidateSize({ pan: false });
            });
        }

        function markerIcon(kind) {
            return window.L.divIcon({
                className: 'location-marker-shell',
                html: '<span class="map-pin map-pin-' + kind + '"></span>',
                iconSize: [22, 22],
                iconAnchor: [11, 11]
            });
        }

        function setMarker(existing, location, kind, label) {
            if (!map || !location) {
                return existing;
            }
            var point = [Number(location.latitude), Number(location.longitude)];
            if (!Number.isFinite(point[0]) || !Number.isFinite(point[1])) {
                return existing;
            }
            if (!existing) {
                existing = window.L.marker(point, { icon: markerIcon(kind), title: label }).addTo(map);
            } else {
                existing.setLatLng(point);
            }
            existing.bindTooltip(label);
            return existing;
        }

        function updateMap(payload) {
            if (!map) {
                return;
            }
            customerMarker = setMarker(customerMarker, payload.customer_location, 'customer', 'Service address');
            technicianMarker = setMarker(technicianMarker, payload.technician_location, 'technician', 'Technician');
            var markers = [customerMarker, technicianMarker].filter(Boolean);
            if (markers.length && (!didFitBounds || markers.length > visibleMarkerCount)) {
                map.fitBounds(window.L.featureGroup(markers).getBounds().pad(0.25), { maxZoom: 16 });
                didFitBounds = true;
            }
            visibleMarkerCount = markers.length;
        }

        function setStatus(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function refreshLocation() {
            if (polling) {
                return;
            }
            polling = true;
            fetch(endpoint, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Location refresh failed with status ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    updateMap(payload);
                    if (role === 'technician') {
                        if (payload.technician_location && payload.technician_location.is_live) {
                            setStatus('You are sharing your location with this request’s customer.');
                        } else if (watchId !== null) {
                            setStatus('Sharing is on. Waiting for the next GPS update…');
                        } else {
                            setStatus('Your location is not being shared.');
                        }
                    } else if (payload.technician_location && payload.technician_location.is_live) {
                        setStatus('The technician is sharing a live location. GPS accuracy may vary.');
                    } else if (payload.technician_location) {
                        setStatus('Showing the last location received; the technician is not currently sharing live location.');
                    } else {
                        setStatus('The technician has not started sharing their location.');
                    }
                })
                .catch(function (error) {
                    setStatus('Location is temporarily unavailable. Retrying.');
                    console.error(error);
                })
                .then(function () {
                    polling = false;
                });
        }

        function sendPosition(position) {
            var now = Date.now();
            if (now - lastSentAt < 15000) {
                return;
            }
            lastSentAt = now;
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                })
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Location update failed with status ' + response.status);
                    }
                    setStatus('Sharing your location with this request’s customer. Accuracy reported by GPS: ±' + Math.round(position.coords.accuracy) + ' m.');
                    refreshLocation();
                })
                .catch(function (error) {
                    setStatus('Could not update your location. Sharing will retry on the next GPS update.');
                    console.error(error);
                });
        }

        function stopSharing(sendRequest) {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            if (startButton) {
                startButton.hidden = false;
            }
            if (stopButton) {
                stopButton.hidden = true;
            }
            if (!sendRequest) {
                return;
            }
            fetch(endpoint, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                keepalive: true
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Stopping location sharing failed with status ' + response.status);
                    }
                    setStatus('Location sharing stopped.');
                    refreshLocation();
                })
                .catch(function (error) {
                    setStatus('Could not confirm the stop request. Leave this page open and try again.');
                    console.error(error);
                });
        }

        if (startButton && stopButton) {
            startButton.addEventListener('click', function () {
                startButton.disabled = true;
                setStatus('Requesting permission to share your location…');
                requestCurrentPosition(function (position) {
                    if (startButton) {
                        startButton.disabled = false;
                        startButton.hidden = true;
                    }
                    stopButton.hidden = false;
                    sendPosition(position);
                    watchId = navigator.geolocation.watchPosition(function (nextPosition) {
                        sendPosition(nextPosition);
                    }, function (error) {
                        stopSharing(false);
                        startButton.disabled = false;
                        setStatus(locationErrorMessage(error));
                    }, {
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 60000
                    });
                }, function (error) {
                    startButton.disabled = false;
                    setStatus(locationErrorMessage(error));
                }, function () {
                    setStatus('GPS is taking longer. Trying your phone’s network location…');
                });
            });
            stopButton.addEventListener('click', function () {
                stopSharing(true);
            });
            window.addEventListener('pagehide', function () {
                if (watchId !== null) {
                    stopSharing(true);
                }
            });
        }

        refreshLocation();
        window.setInterval(refreshLocation, 5000);
    });

    document.querySelectorAll('[data-request-chat]').forEach(function (shell) {
        var endpoint = shell.getAttribute('data-endpoint');
        var tileUrl = shell.getAttribute('data-tile-url');
        var userId = Number(shell.getAttribute('data-user-id'));
        var messagesBox = shell.querySelector('[data-request-chat-messages]');
        var form = shell.querySelector('[data-request-chat-form]');
        var textarea = form.querySelector('textarea');
        var status = document.querySelector('[data-request-chat-status]');
        var feedback = shell.querySelector('[data-request-chat-feedback]');
        var shareLocationButton = shell.querySelector('[data-share-chat-location]');
        var sendButton = shell.querySelector('[data-request-chat-send]');
        var scrollToLatestButton = shell.querySelector('[data-request-chat-scroll-bottom]');
        var lastId = Number(shell.getAttribute('data-last-message-id') || 0);
        var loading = false;
        var sending = false;
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function isNearLatestMessage() {
            return messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight < 100;
        }

        function scrollToLatestMessage() {
            messagesBox.scrollTop = messagesBox.scrollHeight;
            if (scrollToLatestButton) {
                scrollToLatestButton.hidden = true;
            }
        }

        function formatTime(value) {
            var date = new Date(value);
            return isNaN(date.getTime()) ? String(value || '') : date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        function mapUrl(latitude, longitude) {
            return 'https://www.openstreetmap.org/?mlat=' + encodeURIComponent(latitude)
                + '&mlon=' + encodeURIComponent(longitude)
                + '#map=17/' + encodeURIComponent(latitude) + '/' + encodeURIComponent(longitude);
        }

        function initChatMap(container) {
            if (!container || !window.L || container.getAttribute('data-map-ready') === '1') {
                return;
            }
            var latitude = Number(container.getAttribute('data-latitude'));
            var longitude = Number(container.getAttribute('data-longitude'));
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
            }
            var map = window.L.map(container, {
                attributionControl: false,
                scrollWheelZoom: false
            }).setView([latitude, longitude], 16);
            window.L.tileLayer(tileUrl, { maxZoom: 19 }).addTo(map);
            window.L.marker([latitude, longitude], {
                icon: window.L.divIcon({
                    className: 'location-marker-shell',
                    html: '<span class="map-pin map-pin-customer"></span>',
                    iconSize: [22, 22],
                    iconAnchor: [11, 11]
                })
            }).addTo(map);
            window.setTimeout(function () {
                map.invalidateSize({ pan: false });
            }, 150);
            window.addEventListener('resize', function () {
                map.invalidateSize({ pan: false });
            });
            container.setAttribute('data-map-ready', '1');
        }

        function appendMessage(message) {
            if (!message || !message.id || Number(message.id) <= lastId) {
                return;
            }
            var wasNearLatest = isNearLatestMessage();
            var isOwnMessage = Number(message.sender_id) === userId;
            lastId = Number(message.id);
            shell.setAttribute('data-last-message-id', String(lastId));
            var emptyMessage = messagesBox.querySelector('[data-request-chat-empty]');
            if (emptyMessage) {
                emptyMessage.remove();
            }
            var wrapper = document.createElement('div');
            wrapper.className = 'chat-message ' + (isOwnMessage ? 'is-self' : '');
            wrapper.setAttribute('data-message-id', String(message.id));
            var bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            var text = document.createElement('p');
            text.textContent = message.message || '';
            bubble.appendChild(text);

            var locationMap = null;
            if (message.message_type === 'location' && message.latitude !== null && message.longitude !== null) {
                locationMap = document.createElement('div');
                locationMap.className = 'chat-location-map';
                locationMap.setAttribute('data-chat-location-map', '');
                locationMap.setAttribute('data-latitude', String(message.latitude));
                locationMap.setAttribute('data-longitude', String(message.longitude));
                locationMap.setAttribute('aria-label', 'Map showing the shared location');
                bubble.appendChild(locationMap);

                var link = document.createElement('a');
                link.className = 'shared-location-link';
                link.href = mapUrl(message.latitude, message.longitude);
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = 'Open shared pin in OpenStreetMap';
                bubble.appendChild(link);
                if (message.accuracy !== null) {
                    var accuracy = document.createElement('small');
                    accuracy.textContent = 'Reported accuracy: ' + Math.round(Number(message.accuracy)) + ' m';
                    bubble.appendChild(accuracy);
                }
            }

            var meta = document.createElement('small');
            meta.textContent = (message.sender_name || 'User') + ' · ' + formatTime(message.created_at);
            bubble.appendChild(meta);
            wrapper.appendChild(bubble);
            messagesBox.appendChild(wrapper);
            initChatMap(locationMap);
            if (wasNearLatest || isOwnMessage) {
                scrollToLatestMessage();
            } else if (scrollToLatestButton) {
                scrollToLatestButton.hidden = false;
            }
        }

        function setConnectionState(connected) {
            if (!status) {
                return;
            }
            status.textContent = connected ? 'Live' : 'Reconnecting…';
            status.classList.toggle('success', connected);
        }

        function loadMessages() {
            if (loading) {
                return;
            }
            loading = true;
            fetch(endpoint + '?last_id=' + lastId, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Chat refresh failed with status ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || !Array.isArray(payload.messages)) {
                        throw new Error('Chat response was invalid.');
                    }
                    payload.messages.forEach(appendMessage);
                    setConnectionState(true);
                })
                .catch(function (error) {
                    setConnectionState(false);
                    if (feedback) {
                        feedback.textContent = 'Chat is temporarily unavailable. Retrying.';
                    }
                    console.error(error);
                })
                .then(function () {
                    loading = false;
                });
        }

        function send(payload) {
            return fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok) {
                        var error = body.error || (body.errors && Object.values(body.errors)[0][0]) || 'Message could not be sent.';
                        throw new Error(error);
                    }
                    return body;
                });
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var message = textarea.value.trim();
            if (!message || sending) {
                return;
            }
            sending = true;
            if (sendButton) {
                sendButton.disabled = true;
            }
            send({ message_type: 'text', message: message })
                .then(function (payload) {
                    appendMessage(payload.message);
                    textarea.value = '';
                    textarea.style.height = 'auto';
                    if (feedback) {
                        feedback.textContent = '';
                    }
                })
                .catch(function (error) {
                    if (feedback) {
                        feedback.textContent = error.message;
                    }
                })
                .then(function () {
                    sending = false;
                    if (sendButton) {
                        sendButton.disabled = false;
                    }
                });
        });

        if (shareLocationButton) {
            shareLocationButton.addEventListener('click', function () {
                shareLocationButton.disabled = true;
                feedback.textContent = 'Requesting your location…';
                requestCurrentPosition(function (position) {
                    send({
                        message_type: 'location',
                        message: 'Shared a one-time location pin',
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    })
                        .then(function (payload) {
                            appendMessage(payload.message);
                            feedback.textContent = 'Your one-time location pin was shared with the assigned technician.';
                        })
                        .catch(function (error) {
                            feedback.textContent = error.message;
                        })
                        .then(function () {
                            shareLocationButton.disabled = false;
                        });
                }, function (error) {
                    feedback.textContent = locationErrorMessage(error);
                    shareLocationButton.disabled = false;
                }, function () {
                    feedback.textContent = 'GPS is taking longer. Trying your phone’s network location…';
                });
            });
        }

        textarea.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
                return;
            }
            event.preventDefault();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        });

        textarea.addEventListener('input', function () {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
        });

        if (scrollToLatestButton) {
            scrollToLatestButton.addEventListener('click', scrollToLatestMessage);
            messagesBox.addEventListener('scroll', function () {
                if (isNearLatestMessage()) {
                    scrollToLatestButton.hidden = true;
                }
            });
        }

        messagesBox.querySelectorAll('[data-chat-location-map]').forEach(initChatMap);
        window.requestAnimationFrame(scrollToLatestMessage);
        loadMessages();
        window.setInterval(function () {
            if (!document.hidden) {
                loadMessages();
            }
        }, 2000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                loadMessages();
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
