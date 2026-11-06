<?php

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $data['errors'] = $data['errors'] ?? ($_SESSION['_errors'] ?? []);
        unset($_SESSION['_errors']);

        extract($data, EXTR_SKIP);

        $viewFile = BASE_PATH.'/app/Views/'.$view.'.php';
        $layoutFile = BASE_PATH.'/app/Views/layouts/'.$layout.'.php';

        if (! is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: '.htmlspecialchars($view);

            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;

        unset($_SESSION['_old']);
    }
}
