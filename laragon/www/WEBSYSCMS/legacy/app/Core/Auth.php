<?php

class Auth
{
    private static ?array $user = null;

    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;

        if (empty($_SESSION['user_id'])) {
            return self::$user = null;
        }

        $user = Database::one('SELECT * FROM users WHERE id = ?', [(int) $_SESSION['user_id']]);

        if (! $user || $user['status'] !== 'active') {
            unset($_SESSION['user_id']);

            return self::$user = null;
        }

        return self::$user = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user['role'] ?? null;
    }

    public static function is(string $role): bool
    {
        return self::role() === $role;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return false;
        }

        if ($user['status'] !== 'active') {
            return false;
        }

        self::loginAs($user);

        return true;
    }

    public static function loginAs(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
        self::$loaded = true;
        log_activity('login', 'Signed in to the portal');
    }

    public static function logout(): void
    {
        if (! empty($_SESSION['user_id'])) {
            log_activity('logout', 'Signed out of the portal');
        }

        unset($_SESSION['user_id']);
        self::$user = null;
        self::$loaded = true;
    }

    public static function requireAuth(): void
    {
        if (! self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '';
            Flash::set('error', 'Please sign in to continue.');
            redirect('/login');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireAuth();

        if (! in_array(self::role(), $roles, true)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Access denied']);
            exit;
        }
    }
}
