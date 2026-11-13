<?php
$flashMessages = session('_toasts', []);
$flashIcons = [
    'success' => 'check-circle',
    'error' => 'alert',
    'warning' => 'alert',
    'info' => 'info',
];
?>

@if ($flashMessages)
    <div class="toast-stack" role="status" aria-live="polite">
        @foreach ($flashMessages as $message)
            <div class="toast toast-{{ e($message['type']) }}" data-toast>
                <span class="toast-icon">{!! icon($flashIcons[$message['type']] ?? 'info') !!}</span>
                <p>{{ e($message['message']) }}</p>
                <button type="button" class="toast-close" data-toast-close aria-label="Dismiss notification">{!! icon('x') !!}</button>
            </div>
        @endforeach
    </div>
@endif
