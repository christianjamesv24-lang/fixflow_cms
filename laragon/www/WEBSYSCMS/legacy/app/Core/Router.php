<?php

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $handler, array $roles = []): void
    {
        $this->add('GET', $pattern, $handler, $roles);
    }

    public function post(string $pattern, string $handler, array $roles = []): void
    {
        $this->add('POST', $pattern, $handler, $roles);
    }

    private function add(string $method, string $pattern, string $handler, array $roles): void
    {
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^'.rtrim($regex, '/').'/?$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern === '/' ? '#^/?$#' : $regex,
            'handler' => $handler,
            'roles' => $roles,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $base = base_url_path();

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/'.trim($path, '/');

        if ($method === 'POST' && ! verify_csrf()) {
            http_response_code(419);
            View::render('errors/419', ['title' => 'Session expired']);

            return;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (! preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            if (! empty($route['roles'])) {
                Auth::requireRole($route['roles']);
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (! is_int($key)) {
                    $params[$key] = urldecode($value);
                }
            }

            [$controllerName, $action] = explode('@', $route['handler']);
            $file = BASE_PATH.'/app/Controllers/'.$controllerName.'.php';

            if (! is_file($file)) {
                http_response_code(500);
                echo 'Controller not found.';

                return;
            }

            require_once $file;
            $controller = new $controllerName;
            $controller->$action($params);

            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Page not found']);
    }
}
