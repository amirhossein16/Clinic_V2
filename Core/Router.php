<?php

namespace Core;

class Router
{
    private array $routes = [];
    private Request $request;

    function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(string $path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }
    public function post(string $path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }
    public function put(string $path, $callback)
    {
        $this->routes['put'][$path] = $callback;
    }
    public function delete(string $path, $callback)
    {
        $this->routes['delete'][$path] = $callback;
    }

    public function resolve()
    {

        $path = $this->request->requestHandler();
        $method = $this->request->method();

        $callback = $this->routes[$method][$path] ?? null;

        if (is_null($callback)) {
            (new Response)->setStatus(404);
            include __DIR__ . './../View/layouts/404.php';
            exit;
        };
        if (is_array($callback)) {
            $callback[0] = new $callback[0];
        }

        echo call_user_func($callback);
    }
}
