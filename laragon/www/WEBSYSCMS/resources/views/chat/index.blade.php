@extends('layouts.app')

@section('content')
<?php
$partner = $peer ?? null;
$chatMessages = $messages ?? [];
$unreadBadge = $notificationCount ?? 0;
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Support desk</p>
        <h1>Live chat</h1>
        <p>Real-time communication with the other team role.</p>
    </div>
    <div class="page-head-actions">
        <span class="status-badge success">Live</span>
    </div>
</div>

<div class="chat-shell" data-chat-shell data-user-id="<?= e($user->id) ?>" data-peer-id="<?= e($partner['id'] ?? 0) ?>" data-conversation-id="<?= e($conversation['id'] ?? 0) ?>" data-chat-base="<?= e(url('/chat')) ?>">
    <aside class="chat-sidebar">
        <div class="chat-user-card">
            <span class="avatar avatar-lg" style="background: <?= e($user->avatar_color ?? '#2563eb') ?>;"><?= e(strtoupper(substr($user->name, 0, 1))) ?></span>
            <div>
                <strong><?= e($user->name) ?></strong>
                <small><?= e(ucfirst($user->role)) ?> account</small>
            </div>
        </div>

        <div class="chat-target">
            <span class="eyebrow">Connected to</span>
            <?php if ($partner): ?>
                <div class="chat-person">
                    <span class="avatar avatar-sm" style="background: <?= e($partner['avatar_color'] ?? '#2563eb') ?>;"><?= e(strtoupper(substr($partner['name'], 0, 1))) ?></span>
                    <div>
                        <strong><?= e($partner['name']) ?></strong>
                        <small><?= e($partner['role'] === 'admin' ? 'Administrator' : 'Technician') ?></small>
                    </div>
                </div>
            <?php else: ?>
                <p class="empty-inline">No active partner is available right now.</p>
            <?php endif; ?>
        </div>
    </aside>

    <section class="chat-panel">
        <div class="chat-header">
            <div>
                <strong><?= e($partner['name'] ?? 'No contact') ?></strong>
                <small><?= $partner ? 'Online' : 'Offline' ?></small>
            </div>
            <span class="notification-pill<?= $unreadBadge > 0 ? ' is-on' : '' ?>"><?= $unreadBadge ?> new</span>
        </div>

        <div class="chat-messages" data-chat-messages>
            <?php if ($chatMessages): ?>
                <?php foreach ($chatMessages as $message): ?>
                    <div class="chat-message <?= (int) $message['sender_id'] === (int) $user->id ? 'is-self' : '' ?>">
                        <div class="chat-bubble">
                            <p><?= e($message['message']) ?></p>
                            <small><?= e($message['sender_name']) ?> · <?= e(date('h:i A', strtotime($message['created_at']))) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-chat">
                    <h3>No messages yet</h3>
                    <p>Start the conversation with <?= e($partner['name'] ?? 'your teammate') ?>.</p>
                </div>
            <?php endif; ?>
        </div>

        <form class="chat-form" data-chat-form>
            <textarea name="message" rows="1" placeholder="Type a message..." required></textarea>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </section>
</div>

<style>
    .chat-shell {
        display: grid;
        grid-template-columns: 270px minmax(0, 1fr);
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(148,163,184,0.2);
        border-radius: 18px;
        overflow: hidden;
        min-height: 520px;
    }
    .chat-sidebar {
        background: rgba(15,23,42,0.38);
        border-right: 1px solid rgba(148,163,184,0.14);
        padding: 20px;
    }
    .chat-user-card, .chat-person {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .chat-user-card strong, .chat-person strong { display: block; }
    .chat-target { margin-top: 24px; }
    .chat-panel { display: flex; flex-direction: column; min-height: 0; }
    .chat-header {
        display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; border-bottom: 1px solid rgba(148,163,184,0.14);
    }
    .notification-pill { background: rgba(148,163,184,0.14); border-radius: 999px; padding: 6px 10px; font-size: 12px; }
    .notification-pill.is-on { background: rgba(59,130,246,0.22); }
    .chat-messages {
        flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; padding: 20px; background: rgba(2,6,23,0.18);
    }
    .chat-message { display: flex; }
    .chat-message.is-self { justify-content: flex-end; }
    .chat-bubble {
        max-width: min(70%, 420px); padding: 12px 14px; border-radius: 14px; background: rgba(148,163,184,0.12); border: 1px solid rgba(148,163,184,0.15);
    }
    .chat-message.is-self .chat-bubble { background: rgba(59,130,246,0.28); }
    .chat-bubble p { margin: 0 0 8px; white-space: pre-wrap; }
    .chat-bubble small { color: rgba(226,232,240,0.7); }
    .empty-chat {
        margin: auto; text-align: center; padding: 32px; color: rgba(226,232,240,0.8);
    }
    .chat-form {
        display: flex; gap: 12px; padding: 18px 20px; background: rgba(15,23,42,0.18); border-top: 1px solid rgba(148,163,184,0.14);
    }
    .chat-form textarea {
        width: 100%; resize: none; min-height: 44px; max-height: 140px; border: 1px solid rgba(148,163,184,0.2); border-radius: 12px; background: rgba(15,23,42,0.3); color: #fff; padding: 12px 14px;
    }
    .chat-form button { min-width: 110px; }
    .empty-inline { color: rgba(226,232,240,0.7); margin: 8px 0 0; }
    @media (max-width: 780px) {
        .chat-shell { grid-template-columns: 1fr; }
        .chat-sidebar { border-right: none; border-bottom: 1px solid rgba(148,163,184,0.14); }
    }
</style>

<script>
    (function () {
        var shell = document.querySelector('[data-chat-shell]');
        if (!shell) {
            return;
        }

        var messagesBox = shell.querySelector('[data-chat-messages]');
        var form = shell.querySelector('[data-chat-form]');
        var textarea = form.querySelector('textarea');
        var baseUrl = shell.getAttribute('data-chat-base');
        var lastId = 0;

        function updateLastId(messages) {
            if (!Array.isArray(messages) || !messages.length) {
                return;
            }
            lastId = Math.max(lastId, Number(messages[messages.length - 1].id || 0));
        }

        function appendMessage(message) {
            if (!message || !message.id) {
                return;
            }

            lastId = Math.max(lastId, Number(message.id || 0));
            var wrapper = document.createElement('div');
            wrapper.className = 'chat-message ' + ((Number(message.sender_id) === Number(shell.getAttribute('data-user-id'))) ? 'is-self' : '');
            wrapper.innerHTML = '<div class="chat-bubble"><p>' + escapeHtml(message.message) + '</p><small>' + escapeHtml((message.sender_name || 'User')) + ' · ' + escapeHtml(formatTime(message.created_at)) + '</small></div>';
            messagesBox.appendChild(wrapper);
            messagesBox.scrollTop = messagesBox.scrollHeight;

            if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
                new Notification('FixitNow chat', { body: (message.sender_name || 'User') + ': ' + message.message });
            }
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatTime(value) {
            var date = new Date(value);
            return isNaN(date.getTime()) ? value : date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        function loadMessages() {
            fetch(baseUrl + '/messages?last_id=' + lastId, { credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (payload && payload.messages) {
                        updateLastId(payload.messages);
                        payload.messages.forEach(function (message) {
                            appendMessage(message);
                        });
                    }
                })
                .catch(function () {});
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var value = textarea.value.trim();
            if (!value) {
                return;
            }

            fetch(baseUrl + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message: value })
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (payload && payload.message) {
                        appendMessage(payload.message);
                    }
                    textarea.value = '';
                    textarea.style.height = 'auto';
                })
                .catch(function () {});
        });

        textarea.addEventListener('input', function () {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
        });

        if ('Notification' in window) {
            Notification.requestPermission().catch(function () {});
        }

        setInterval(loadMessages, 3000);
        loadMessages();
    })();
</script>
@endsection
