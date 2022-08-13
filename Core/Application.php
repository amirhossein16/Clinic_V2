<?php

namespace Core;

class Application
{
    public Router $route;
    public Request $request;
    public function __construct()
    {
        $this->request = new Request();
        $this->route = new Router($this->request);
    }

    public function run()
    {
        $this->route->resolve();
    }
}
