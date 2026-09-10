<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $groupMiddleware = [];
    private string $prefix = '';

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $oldPrefix = $this->prefix;
        $oldGroupMiddleware = $this->groupMiddleware;
        $this->prefix = $oldPrefix . $prefix;
        $this->groupMiddleware = array_merge($oldGroupMiddleware, $middleware);
        $callback($this);
        $this->prefix = $oldPrefix;
        $this->groupMiddleware = $oldGroupMiddleware;
    }

    public function get(string $path, $controller, $method = null, array $middleware = []): void
    {
        if ($controller instanceof \Closure) {
            $this->addClosure('GET', $path, $controller, $middleware);
        } else {
            $this->addRoute('GET', $path, $controller, $method, $middleware);
        }
    }

    public function post(string $path, $controller, $method = null, array $middleware = []): void
    {
        if ($controller instanceof \Closure) {
            $this->addClosure('POST', $path, $controller, $middleware);
        } else {
            $this->addRoute('POST', $path, $controller, $method, $middleware);
        }
    }

    public function put(string $path, $controller, $method = null, array $middleware = []): void
    {
        if ($controller instanceof \Closure) {
            $this->addClosure('PUT', $path, $controller, $middleware);
        } else {
            $this->addRoute('PUT', $path, $controller, $method, $middleware);
        }
    }

    public function delete(string $path, $controller, $method = null, array $middleware = []): void
    {
        if ($controller instanceof \Closure) {
            $this->addClosure('DELETE', $path, $controller, $middleware);
        } else {
            $this->addRoute('DELETE', $path, $controller, $method, $middleware);
        }
    }

    private function addRoute(string $httpMethod, string $path, string $controller, string $method, array $middleware): void
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method'     => $httpMethod,
            'path'       => $fullPath,
            'controller' => $controller,
            'action'     => $method,
            'closure'    => null,
            'middleware'  => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    private function addClosure(string $httpMethod, string $path, \Closure $closure, array $middleware): void
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method'     => $httpMethod,
            'path'       => $fullPath,
            'controller' => null,
            'action'     => null,
            'closure'    => $closure,
            'middleware'  => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            $pattern = $this->toRegex($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($route['middleware'] as $mw) {
                    $this->runMiddleware($mw);
                }

                if (!empty($route['closure'])) {
                    call_user_func_array($route['closure'], $params);
                    return;
                }

                $controllerClass = $route['controller'];
                $action = $route['action'];
                $controller = new $controllerClass();
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        http_response_code(404);
        View::render('errors.404');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function toRegex(string $path): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    private function runMiddleware(string $name): void
    {
        $class = 'App\\Core\\Middleware\\' . $name;
        if (class_exists($class)) {
            $instance = new $class();
            $instance->handle();
        }
    }
}
