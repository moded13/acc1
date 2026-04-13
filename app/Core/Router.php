<?php
namespace App\Core;

class Router
{
    protected array $routes = [];

    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$method])) {
            $this->sendJsonError(405, 'HTTP method not supported.');
        }

        if (!isset($this->routes[$method][$uri])) {
            $this->sendJsonError(404, 'Route not found.');
        }

        [$controllerClass, $action] = $this->routes[$method][$uri];

        if (!class_exists($controllerClass)) {
            $this->sendJsonError(500, "Controller $controllerClass not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            $this->sendJsonError(500, "Action $action not found in $controllerClass.");
        }

        call_user_func([$controller, $action]);
    }

    protected function sendJsonError(int $code, string $message): void
    {
        http_response_code($code);

        // حتى لو كانت صفحة Web، نرجع JSON واضح لأن معظم الأخطاء هنا من API calls
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'status'  => 'error',
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}