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
        <span class="status-badge success" data-chat-status aria-live="polite">Connecting...</span>
    </div>
</div>

<div class="chat-shell" data-chat-shell data-user-id="<?= e($user->id) ?>" data-peer-id="<?= e($partner['id'] ?? 0) ?>" data-conversation-id="<?= e($conversation['id'] ?? 0) ?>" data-last-message-id="<?= e($chatMessages ? end($chatMessages)['id'] : 0) ?>" data-chat-base="<?= e(url('/chat')) ?>">
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
        background: #fff;
        border: 1px solid #e4ebef;
        border-radius: 19px;
        overflow: hidden;
        min-height: 520px;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .045);
    }
    .chat-sidebar {
        background: #f8fafb;
        border-right: 1px solid #e4ebef;
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
        display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; border-bottom: 1px solid #e8edf1; background: #fff;
    }
    .notification-pill { background: #f1f5f9; border-radius: 999px; padding: 6px 10px; font-size: 12px; color: #64748b; }
    .notification-pill.is-on { background: #e8f8f5; color: #075f58; }
    .chat-messages {
        flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; padding: 20px; background: #fff;
    }
    .chat-message { display: flex; }
    .chat-message.is-self { justify-content: flex-end; }
    .chat-bubble {
        max-width: min(70%, 420px); padding: 12px 14px; border-radius: 14px; background: #f3f6f8; border: 1px solid #e6edf1; color: #17243a;
    }
    .chat-message.is-self .chat-bubble { background: #e8f8f5; border-color: #c8ece6; }
    .chat-bubble p { margin: 0 0 8px; white-space: pre-wrap; }
    .chat-bubble small { color: #718096; }
    .empty-chat {
        margin: auto; text-align: center; padding: 32px; color: #718096;
    }
    .chat-form {
        display: flex; gap: 12px; padding: 18px 20px; background: #fff; border-top: 1px solid #e8edf1;
    }
    .chat-form textarea {
        width: 100%; resize: none; min-height: 44px; max-height: 140px; border: 1px solid #dce5ea; border-radius: 12px; background: #fff; color: #17243a; padding: 12px 14px;
    }
    .chat-form button { min-width: 110px; }
    .empty-inline { color: #718096; margin: 8px 0 0; }
    @media (max-width: 780px) {
        .chat-shell { grid-template-columns: 1fr; }
        .chat-sidebar { border-right: none; border-bottom: 1px solid #e4ebef; }
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
        var status = document.querySelector('[data-chat-status]');
        var lastId = Number(shell.getAttribute('data-last-message-id') || 0);
        var loading = false;

        function setConnectionState(isConnected) {
            status.textContent = isConnected ? 'Live' : 'Reconnecting...';
            status.classList.toggle('success', isConnected);
        }

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
            if (loading) {
                return;
            }

            loading = true;
            fetch(baseUrl + '/messages?last_id=' + lastId, {
                credentials: 'same-origin',
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

                    updateLastId(payload.messages);
                    payload.messages.forEach(function (message) {
                        appendMessage(message);
                    });
                    setConnectionState(true);
                })
                .catch(function () {
                    setConnectionState(false);
                })
                .then(function () {
                    loading = false;
                });
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
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (payload) {
                            throw new Error(payload.error || 'Message could not be sent.');
                        });
                    }

                    return response.json();
                })
                .then(function (payload) {
                    if (payload && payload.message) {
                        appendMessage(payload.message);
                    }
                    textarea.value = '';
                    textarea.style.height = 'auto';
                })
                .catch(function (error) {
                    window.alert(error.message);
                });
        });

        textarea.addEventListener('input', function () {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
        });

        setInterval(loadMessages, 3000);
        loadMessages();
    })();
</script>
@endsection
