<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $afterId = max(0, (int) $request->query('after_id', 0));
        $notifications = db_all(
            'SELECT id, title, body, type, created_at FROM user_notifications
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
