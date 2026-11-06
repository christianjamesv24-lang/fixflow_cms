<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    protected function authUser()
    {
        return auth()->user();
    }

    protected function input(string $key, $default = ''): string
    {
        $value = request()->input($key, $default);

        return is_string($value) ? trim($value) : (string) $value;
    }

    protected function flash(string $type, string $message): void
    {
        flash($type, $message);
    }

    protected function backWithErrors(array $errors, string $fallback = '/'): RedirectResponse
    {
        flash('error', 'Please review the highlighted fields and try again.');

        $referer = request()->headers->get('referer');
        $redirect = $referer ? redirect()->to($referer) : redirect($fallback);

        return $redirect->withInput()->with('_form_errors', $errors);
    }

    protected function requirePost(): void
    {
        if (! request()->isMethod('POST')) {
            abort(405, 'Method not allowed');
        }
    }

    protected function log(string $action, string $description): void
    {
        log_activity($action, $description);
    }

    protected function notFound(string $message = 'The page you are looking for does not exist.'): void
    {
        abort(404, $message);
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
