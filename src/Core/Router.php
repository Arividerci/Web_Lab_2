<?php

namespace Myshop\Electronics\Core;

class Router {
    private array $routes = [];

    public function get(string $path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }
    
    public function post(string $path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $callback = $this->routes[$method][$uri] ?? null;

        if (is_callable($callback)) {
            return call_user_func($callback);
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}
