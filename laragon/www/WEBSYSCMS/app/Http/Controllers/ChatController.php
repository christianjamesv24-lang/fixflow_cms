<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['admin', 'technician'], true)) {
            abort(403);
        }

        $peer = $this->getPeer($user->id, $user->role);

        $conversation = $peer ? $this->getConversation($user->id, $peer['id']) : null;
        $messages = $conversation
            ? db_all(
                'SELECT m.*, s.name AS sender_name FROM chat_messages m JOIN users s ON s.id = m.sender_id WHERE m.conversation_id = ? ORDER BY m.id ASC',
                [$conversation['id']]
            )
            : [];

        if ($conversation) {
            db_exec('UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?', [$conversation['id'], $user->id]);
        }

        db_exec('UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND type = ?', [$user->id, 'info']);

        return view('chat.index', [
            'title' => 'Live chat',
            'user' => $user,
            'peer' => $peer,
            'conversation' => $conversation,
            'messages' => $messages,
            'notificationCount' => (int) db_value('SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0', [$user->id]),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'technician'], true)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role === 'technician') {
            $this->notifySubscriptionExpiring((int) $user->id);
        }

        $afterId = max(0, (int) $request->query('after_id', 0));
        $notifications = db_all(
            'SELECT id, title, body, type, target_url, created_at FROM user_notifications
             WHERE user_id = ? AND is_read = 0 AND id > ?
             ORDER BY id ASC LIMIT 20',
            [$user->id, $afterId]
        );

        return response()->json([
            'notifications' => $notifications,
            'notification_count' => (int) db_value(
                'SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0',
                [$user->id]
            ),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function notifySubscriptionExpiring(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            $subscription = DB::table('subscriptions')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->orderByDesc('current_period_end')
                ->lockForUpdate()
                ->first();

            if (! $subscription || ! $subscription->current_period_end || $subscription->expiring_notification_sent_at) {
                return;
            }

            $now = now();
            $periodEnd = Carbon::parse($subscription->current_period_end);
            if ($periodEnd->lessThanOrEqualTo($now) || $periodEnd->greaterThan($now->copy()->addDays(3))) {
                return;
            }

            $updated = DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->whereNull('expiring_notification_sent_at')
                ->update([
                    'expiring_notification_sent_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($updated === 0) {
                return;
            }

            DB::table('user_notifications')->insert([
                'user_id' => $userId,
                'title' => 'Subscription ending soon',
                'body' => 'Your technician subscription ends on '.$periodEnd->format('M j, Y g:i A').'. Extend it before then to keep technician access.',
                'type' => 'info',
                'is_read' => false,
                'target_url' => url('/technician/subscription'),
                'created_at' => $now,
            ]);
        });
    }

    public function messages(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'technician'], true)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $peer = $this->getPeer($user->id, $user->role);
        if (! $peer) {
            return response()->json(['messages' => [], 'peer' => null]);
        }

        $conversation = $this->getConversation($user->id, $peer['id']);
        if (! $conversation) {
            return response()->json([
                'messages' => [],
                'peer' => $peer,
                'notification_count' => (int) db_value(
                    'SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0',
                    [$user->id]
                ),
            ]);
        }

        $lastId = (int) ($request->query('last_id', 0));
        $sql = 'SELECT m.*, s.name AS sender_name FROM chat_messages m JOIN users s ON s.id = m.sender_id WHERE m.conversation_id = ?';
        $params = [$conversation['id']];
        if ($lastId > 0) {
            $sql .= ' AND m.id > ?';
            $params[] = $lastId;
        }
        $sql .= ' ORDER BY m.id ASC';

        $messages = db_all($sql, $params);

        db_exec('UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?', [$conversation['id'], $user->id]);

        return response()->json([
            'messages' => $messages,
            'peer' => $peer,
            'notification_count' => (int) db_value('SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0', [$user->id]),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'technician'], true)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json(['error' => 'Message cannot be empty.'], 422);
        }

        $peer = $this->getPeer($user->id, $user->role);
        if (! $peer) {
            return response()->json(['error' => 'No technician or admin is available.'], 422);
        }

        $timestamp = now()->toDateTimeString();
        $conversation = $this->getConversation($user->id, $peer['id']);
        if (! $conversation) {
            $conversationId = (int) db_insert(
                'INSERT INTO chat_conversations (user_one, user_two, last_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [min($user->id, $peer['id']), max($user->id, $peer['id']), $timestamp, $timestamp, $timestamp]
            );
            $conversation = ['id' => $conversationId];
        }

        $messageId = (int) db_insert(
            'INSERT INTO chat_messages (conversation_id, sender_id, receiver_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, ?)',
            [$conversation['id'], $user->id, $peer['id'], $message, $timestamp]
        );

        db_exec(
            'UPDATE chat_conversations SET last_message_at = ?, updated_at = ? WHERE id = ?',
            [$timestamp, $timestamp, $conversation['id']]
        );

        db_insert(
            'INSERT INTO user_notifications (user_id, title, body, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, ?)',
            [$peer['id'], 'New message', $user->name.': '.$message, 'info', $timestamp]
        );

        $record = db_one('SELECT m.*, s.name AS sender_name FROM chat_messages m JOIN users s ON s.id = m.sender_id WHERE m.id = ?', [$messageId]);

        return response()->json([
            'message' => $record,
            'conversation_id' => $conversation['id'],
        ]);
    }

    public function markRead(): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'technician'], true)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $peer = $this->getPeer($user->id, $user->role);
        if (! $peer) {
            return response()->json(['ok' => true]);
        }

        $conversation = $this->getConversation($user->id, $peer['id']);
        if (! $conversation) {
            return response()->json(['ok' => true]);
        }

        db_exec('UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?', [$conversation['id'], $user->id]);
        db_exec('UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND type = ?', [$user->id, 'info']);

        return response()->json(['ok' => true]);
    }

    public function requestChat(int $id)
    {
        $user = $this->authUser();
        $serviceRequest = $this->findRequestConversation($id, $user);
        $peerId = (int) $serviceRequest['customer_id'] === (int) $user->id
            ? (int) $serviceRequest['technician_id']
            : (int) $serviceRequest['customer_id'];
        $peer = db_one('SELECT id, name, role, avatar_color FROM users WHERE id = ?', [$peerId]);
        $messages = db_all(
            'SELECT m.*, s.name AS sender_name
             FROM request_chat_messages m
             JOIN users s ON s.id = m.sender_id
             WHERE m.request_id = ? ORDER BY m.id ASC LIMIT 100',
            [$id]
        );

        db_exec(
            'UPDATE request_chat_messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ?',
            [$id, $user->id]
        );

        return view('chat.request', [
            'title' => 'Chat · '.$serviceRequest['ticket_no'],
            'user' => $user,
            'peer' => $peer,
            'serviceRequest' => $serviceRequest,
            'messages' => $messages,
        ]);
    }

    public function requestMessages(Request $request, int $id): JsonResponse
    {
        $user = $this->authUser();
        $this->findRequestConversation($id, $user);
        $lastId = max(0, (int) $request->query('last_id', 0));
        $messages = db_all(
            'SELECT m.*, s.name AS sender_name
             FROM request_chat_messages m
             JOIN users s ON s.id = m.sender_id
             WHERE m.request_id = ? AND m.id > ?
             ORDER BY m.id ASC LIMIT 100',
            [$id, $lastId]
        );

        db_exec(
            'UPDATE request_chat_messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND id > ?',
            [$id, $user->id, $lastId]
        );

        return response()->json(['messages' => $messages])
            ->header('Cache-Control', 'private, no-store');
    }

    public function sendRequestMessage(Request $request, int $id): JsonResponse
    {
        $user = $this->authUser();
        $data = $request->validate([
            'message_type' => ['required', 'in:text,location'],
            'message' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['required_if:message_type,location', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:message_type,location', 'nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);
        $messageType = $data['message_type'];
        $message = trim((string) ($data['message'] ?? ''));

        if ($messageType === 'text' && $message === '') {
            return response()->json(['error' => 'Message cannot be empty.'], 422);
        }

        $messageId = DB::transaction(function () use ($id, $user, $data, $messageType, $message): int {
            $serviceRequest = $this->findRequestConversation($id, $user, true);
            $receiverId = (int) $serviceRequest['customer_id'] === (int) $user->id
                ? (int) $serviceRequest['technician_id']
                : (int) $serviceRequest['customer_id'];

            return (int) DB::table('request_chat_messages')->insertGetId([
                'request_id' => $id,
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'message_type' => $messageType,
                'message' => $messageType === 'location'
                    ? ($message !== '' ? $message : 'Shared a location')
                    : $message,
                'latitude' => $messageType === 'location' ? $data['latitude'] : null,
                'longitude' => $messageType === 'location' ? $data['longitude'] : null,
                'accuracy' => $messageType === 'location' ? ($data['accuracy'] ?? null) : null,
                'is_read' => false,
            ]);
        });

        $record = db_one(
            'SELECT m.*, s.name AS sender_name
             FROM request_chat_messages m JOIN users s ON s.id = m.sender_id
             WHERE m.id = ?',
            [$messageId]
        );

        return response()->json(['message' => $record])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * @return array<string, mixed>
     */
    private function findRequestConversation(int $id, User $user, bool $forUpdate = false): array
    {
        $query = DB::table('service_requests')->where('id', $id);
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        $serviceRequest = $query->first();

        abort_if(
            ! $serviceRequest
                || ! in_array($serviceRequest->status, ['assigned', 'accepted', 'in_progress'], true)
                || ! (
                    ($user->role === 'customer' && (int) $serviceRequest->customer_id === (int) $user->id)
                    || ($user->role === 'technician' && (int) $serviceRequest->technician_id === (int) $user->id)
                ),
            404
        );

        return (array) $serviceRequest;
    }

    protected function getConversation(int $userId, int $peerId): ?array
    {
        $low = min($userId, $peerId);
        $high = max($userId, $peerId);

        return db_one(
            'SELECT * FROM chat_conversations WHERE user_one = ? AND user_two = ?',
            [$low, $high]
        );
    }

    protected function getPeer(int $userId, string $role): ?array
    {
        if ($role === 'admin') {
            return db_one("SELECT * FROM users WHERE role = 'technician' AND status = 'active' ORDER BY name ASC LIMIT 1");
        }

        return db_one("SELECT * FROM users WHERE role = 'admin' AND status = 'active' ORDER BY name ASC LIMIT 1");
    }
}
