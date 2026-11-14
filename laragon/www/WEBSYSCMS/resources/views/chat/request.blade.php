@extends('layouts.app')

@section('content')
<?php
$chatMessages = $messages ?? [];
$lastMessageId = $chatMessages ? (int) end($chatMessages)['id'] : 0;
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="page-head">
    <div>
        <p class="eyebrow">Service request · <?= e($serviceRequest['ticket_no']) ?></p>
        <h1>Chat with <?= e($peer['name'] ?? 'your service contact') ?></h1>
        <p><?= e($serviceRequest['title']) ?> · Messages are visible only to this request's customer and assigned technician.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e($user->role === 'customer' ? url('/customer/requests/'.$serviceRequest['id']) : url('/technician/jobs/'.$serviceRequest['id'])) ?>">Back to request</a>
        <span class="status-badge success" data-request-chat-status aria-live="polite">Connecting...</span>
    </div>
</div>

<div class="chat-shell request-chat-shell"
     data-request-chat
     data-user-id="<?= e($user->id) ?>"
     data-user-role="<?= e($user->role) ?>"
     data-endpoint="<?= e(url('/service-requests/'.$serviceRequest['id'].'/chat/messages')) ?>"
     data-tile-url="<?= e(config('services.location.tile_url')) ?>"
     data-last-message-id="<?= $lastMessageId ?>">
    <section class="chat-panel">
        <div class="chat-header">
            <div class="chat-person">
                <span class="avatar avatar-sm" style="background: <?= e($peer['avatar_color'] ?? '#2563eb') ?>;"><?= e(strtoupper(substr($peer['name'] ?? 'U', 0, 1))) ?></span>
                <div>
                    <strong><?= e($peer['name'] ?? 'Service contact') ?></strong>
                    <small><?= e(ucfirst($peer['role'] ?? '')) ?></small>
                </div>
            </div>
            <span class="text-sm text-muted"><?= e($serviceRequest['ticket_no']) ?></span>
        </div>

        <div class="chat-messages" data-request-chat-messages aria-live="polite">
            <?php foreach ($chatMessages as $message): ?>
                <div class="chat-message <?= (int) $message['sender_id'] === (int) $user->id ? 'is-self' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
                    <div class="chat-bubble">
                        <?php if ($message['message_type'] === 'location' && $message['latitude'] !== null && $message['longitude'] !== null): ?>
                            <?php $mapUrl = 'https://www.openstreetmap.org/?mlat='.rawurlencode((string) $message['latitude']).'&mlon='.rawurlencode((string) $message['longitude']).'#map=17/'.rawurlencode((string) $message['latitude']).'/'.rawurlencode((string) $message['longitude']); ?>
                            <p><?= e($message['message']) ?></p>
                            <div class="chat-location-map"
                                 data-chat-location-map
                                 data-latitude="<?= e($message['latitude']) ?>"
                                 data-longitude="<?= e($message['longitude']) ?>"
                                 data-tile-url="<?= e(config('services.location.tile_url')) ?>"
                                 aria-label="Map showing the shared location"></div>
                            <a class="shared-location-link" href="<?= e($mapUrl) ?>" target="_blank" rel="noopener noreferrer">Open shared pin in OpenStreetMap</a>
                            <?php if ($message['accuracy'] !== null): ?><small>Reported accuracy: <?= e(round((float) $message['accuracy'])) ?> m</small><?php endif; ?>
                        <?php else: ?>
                            <p><?= e($message['message']) ?></p>
                        <?php endif; ?>
                        <small><?= e($message['sender_name']) ?> · <?= e(date('h:i A', strtotime($message['created_at']))) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (! $chatMessages): ?>
                <div class="empty-chat" data-request-chat-empty>
                    <h3>No messages yet</h3>
                    <p>Use this chat to coordinate the service visit.</p>
                </div>
            <?php endif; ?>
        </div>

        <form class="chat-form" data-request-chat-form>
            <textarea name="message" rows="1" placeholder="Type a message..." maxlength="2000" aria-label="Message"></textarea>
            <?php if ($user->role === 'customer'): ?>
                <button type="button" class="btn btn-soft" data-share-chat-location>Share my location</button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
        <p class="field-hint request-chat-hint" data-request-chat-feedback aria-live="polite">Your location is shared only when you choose the button above. It is not tracked continuously.</p>
    </section>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection
