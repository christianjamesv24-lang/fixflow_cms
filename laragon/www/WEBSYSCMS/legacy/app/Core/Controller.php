<?php

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function authUser(): array
    {
        Auth::requireAuth();

        return Auth::user();
    }

    protected function input(string $key, $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : (string) $value;
    }

    protected function getParam(array $params, string $key): int
    {
        return isset($params[$key]) ? (int) $params[$key] : 0;
    }

    protected function flash(string $type, string $message): void
    {
        Flash::set($type, $message);
    }

    protected function backWithErrors(array $errors, string $fallback = '/'): void
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $_POST;
        Flash::set('error', 'Please review the highlighted fields and try again.');
        back($fallback);
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }

    protected function log(string $action, string $description): void
    {
        log_activity($action, $description);
    }

    protected function notFound(string $message = 'The page you are looking for does not exist.'): void
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Page not found', 'message' => $message]);
        exit;
    }

    protected function paginate(int $total, int $perPage, int $requested): array
    {
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $requested), $pages);

        return [
            'page' => $page,
            'pages' => $pages,
            'offset' => ($page - 1) * $perPage,
            'total' => $total,
        ];
    }
}
