<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('notifications.index', [
            'title' => 'Notifications',
            'notifications' => DB::table('user_notifications')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'unreadCount' => DB::table('user_notifications')
                ->where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
        ]);
    }

    public function open(int $id): RedirectResponse
    {
        $notification = $this->findForCurrentUser($id);

        DB::table('user_notifications')
            ->where('id', $notification->id)
            ->update(['is_read' => true]);

        return redirect()->to($this->destination($notification));
    }

    public function markRead(int $id): RedirectResponse
    {
        $notification = $this->findForCurrentUser($id);

        DB::table('user_notifications')
            ->where('id', $notification->id)
            ->update(['is_read' => true]);

        return redirect('/notifications');
    }

    public function markAllRead(): RedirectResponse
    {
        DB::table('user_notifications')
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect('/notifications');
    }

    private function findForCurrentUser(int $id): object
    {
        $notification = DB::table('user_notifications')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        abort_if(! $notification, 404);

        return $notification;
    }

    private function destination(object $notification): string
    {
        $targetUrl = trim((string) $notification->target_url);
        if ($targetUrl === '') {
            return $notification->title === 'New message' ? '/chat' : '/notifications';
        }

        $parts = parse_url($targetUrl);
        if ($parts === false
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true))) {
            return '/notifications';
        }

        $allowedHosts = array_filter([
            request()->getHost(),
            parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);
        if (isset($parts['host']) && ! in_array(strtolower($parts['host']), array_map('strtolower', $allowedHosts), true)) {
            return '/notifications';
        }

        $path = $parts['path'] ?? '/notifications';
        if (! str_starts_with($path, '/')) {
            return '/notifications';
        }

        return $path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
