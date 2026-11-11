<?php

use Illuminate\Support\Facades\DB;

function db_all(string $sql, array $params = []): array
{
    return array_map(static fn ($row) => (array) $row, DB::select($sql, $params));
}

function db_one(string $sql, array $params = []): ?array
{
    $rows = DB::select($sql, $params);

    return $rows === [] ? null : (array) $rows[0];
}

function db_value(string $sql, array $params = [])
{
    $rows = DB::select($sql, $params);

    if ($rows === []) {
        return null;
    }

    $values = array_values((array) $rows[0]);

    return $values[0] ?? null;
}

function db_exec(string $sql, array $params = []): int
{
    return DB::affectingStatement($sql, $params);
}

function db_insert(string $sql, array $params = []): int
{
    DB::insert($sql, $params);

    return (int) DB::getPdo()->lastInsertId();
}

function settings(bool $refresh = false): array
{
    static $settings = null;

    if ($refresh || $settings === null) {
        $settings = [];

        try {
            foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $settings = [];
        }
    }

    return $settings;
}

function setting(string $key, $default = '')
{
    $settings = settings();

    return $settings[$key] ?? $default;
}

function icon(string $name, string $class = ''): string
{
    $icons = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/>',
        'wrench' => '<path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L3 17.3V21h3.7l5.9-5.9a4 4 0 0 0 4.9-5.6l-2.8 2.8-2.1-2.1z"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2"/><path d="M9 11h6M9 15h6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.11-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6h.09A1.7 1.7 0 0 0 10 3.05V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9v.09a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.55 1z"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'chart' => '<path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6"/><rect x="12" y="7" width="3" height="10"/><rect x="17" y="4" width="3" height="13"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pin' => '<path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6-5.4-2.8L6.6 19.7l1-6L3.2 9.4l6.1-.9z"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'credit-card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'send' => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'refresh' => '<path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.9-3.5L23 10M1 14l4.6 4.5A9 9 0 0 0 20.5 15"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
        'zap' => '<path d="M13 2 3 14h8l-1 8 11-13h-8z"/>',
        'tool' => '<path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L3 17.3V21h3.7l5.9-5.9a4 4 0 0 0 4.9-5.6l-2.8 2.8-2.1-2.1z"/>',
        'snowflake' => '<path d="M12 2v20M4.5 6.5l15 11M19.5 6.5l-15 11"/><path d="m9 4 3 3 3-3M9 20l3-3 3 3M4 10l3 3-3 3M20 10l-3 3 3 3"/>',
        'fridge' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M6 10h12M9 6h1M9 14h1"/>',
        'washer' => '<rect x="4" y="2" width="16" height="20" rx="3"/><circle cx="12" cy="14" r="5"/><path d="M8 6h.01M11 6h.01"/>',
        'oven' => '<rect x="3" y="3" width="18" height="18" rx="2"/><rect x="6" y="11" width="12" height="7" rx="1"/><path d="M6 7h4M15 7h3"/>',
        'microwave' => '<rect x="2" y="5" width="20" height="14" rx="2"/><rect x="5" y="8" width="11" height="8" rx="1"/><path d="M19 9v1M19 13v1"/>',
        'droplet' => '<path d="M12 2.5S6 9 6 14a6 6 0 0 0 12 0c0-5-6-11.5-6-11.5z"/>',
        'award' => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 8 5-3 5 3-1.5-8"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 21h14"/>',
        'external' => '<path d="M14 3h7v7"/><path d="M21 3 10 14"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>',
    ];

    $inner = $icons[$name] ?? $icons['info'];

    return '<svg class="icon '.e($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
}

function status_badge(string $status): string
{
    $map = [
        'pending' => ['Pending', 'warn'],
        'assigned' => ['Assigned', 'info'],
        'accepted' => ['Accepted', 'info'],
        'in_progress' => ['In progress', 'brand'],
        'completed' => ['Completed', 'success'],
        'cancelled' => ['Cancelled', 'danger'],
        'scheduled' => ['Scheduled', 'info'],
        'confirmed' => ['Confirmed', 'brand'],
        'rescheduled' => ['Rescheduled', 'warn'],
        'no_show' => ['No show', 'danger'],
        'active' => ['Active', 'success'],
        'inactive' => ['Inactive', 'muted'],
        'new' => ['New', 'warn'],
        'read' => ['Read', 'muted'],
        'replied' => ['Replied', 'success'],
        'published' => ['Published', 'success'],
        'draft' => ['Draft', 'muted'],
        'low' => ['Low', 'muted'],
        'medium' => ['Medium', 'info'],
        'high' => ['High', 'warn'],
        'urgent' => ['Urgent', 'danger'],
        'repair' => ['Repair', 'info'],
        'maintenance' => ['Maintenance', 'success'],
        'installation' => ['Installation', 'warn'],
        'inspection' => ['Inspection', 'muted'],
    ];

    [$label, $tone] = $map[$status] ?? [ucwords(str_replace('_', ' ', $status)), 'muted'];

    return '<span class="badge badge-'.$tone.'">'.e($label).'</span>';
}

function money($amount, bool $fallback = true): string
{
    if ($amount === null || $amount === '') {
        return $fallback ? '—' : '';
    }

    $symbol = setting('currency_symbol', '₱');

    return $symbol.number_format((float) $amount, 2);
}

function format_date($date, string $format = 'M j, Y'): string
{
    if (! $date || $date === '0000-00-00') {
        return '—';
    }

    $timestamp = strtotime((string) $date);

    return $timestamp ? date($format, $timestamp) : '—';
}

function format_time($time): string
{
    if (! $time) {
        return '—';
    }

    $timestamp = strtotime((string) $time);

    return $timestamp ? date('g:i A', $timestamp) : '—';
}

function format_datetime($datetime): string
{
    return format_date($datetime, 'M j, Y g:i A');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $parts = array_filter($parts);
    $first = $parts[0] ?? 'U';
    $second = count($parts) > 1 ? $parts[count($parts) - 1] : '';

    return strtoupper(mb_substr($first, 0, 1).mb_substr($second, 0, 1));
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    return trim($text, '-');
}

function rating_stars($rating): string
{
    $rating = (float) $rating;
    $html = '<span class="stars" aria-label="'.number_format($rating, 1).' out of 5">';

    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= round($rating) ? ' filled' : '';
        $html .= '<span class="star'.$filled.'">'.icon('star').'</span>';
    }

    return $html.'</span>';
}

function dashboard_url(?string $role = null): string
{
    $role = $role ?: (auth()->check() ? auth()->user()->role : null);

    return match ($role) {
        'admin' => '/admin',
        'technician' => '/technician',
        'customer' => '/customer',
        default => '/',
    };
}

function log_activity(string $action, string $description): void
{
    try {
        DB::insert(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())',
            [auth()->id(), $action, $description, request()->ip()]
        );
    } catch (Throwable $e) {
        // Logging should never break the request.
    }
}

function generate_ticket_no(): string
{
    try {
        $count = (int) db_value('SELECT COUNT(*) FROM service_requests WHERE YEAR(created_at) = YEAR(CURDATE())');
    } catch (Throwable $e) {
        $count = 0;
    }

    return 'SR-'.date('Y').'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
}

function truncate(string $text, int $limit = 120): string
{
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit)).'…';
}

function request_statuses(): array
{
    return ['pending', 'assigned', 'accepted', 'in_progress', 'completed', 'cancelled'];
}

function query_url(array $overrides = []): string
{
    $params = array_merge(request()->query(), $overrides);
    $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null);

    $path = '/'.ltrim(request()->path(), '/');
    $query = http_build_query($params);

    return $path.($query !== '' ? '?'.$query : '');
}

function active_nav(string $path): string
{
    $current = '/'.trim(request()->path(), '/');

    if ($path === '/') {
        return $current === '/' ? 'active' : '';
    }

    return str_starts_with($current, $path) ? 'active' : '';
}

function flash(string $type, string $message): void
{
    $messages = session()->get('_toasts', []);
    $messages[] = ['type' => $type, 'message' => $message];
    session()->flash('_toasts', $messages);
}
