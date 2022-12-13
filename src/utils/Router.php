<?php

namespace NorsysBank\utils;

use NorsysBank\enums\HttpMethod;

class Router {
    private static ?Router $instance = null;

    private function __construct(
        private array $routes = []
    )
    {}

    public static function instantiate()
    {
        if (is_null(static::$instance)) {
            static::$instance = new Router();
        }

        return static::$instance;
    }

    public function route(string $url, callable $callback, HttpMethod $httpMethod)
    {
        $url = str_replace('//', '/', $url);

        if (empty($this->routes[$httpMethod->value])) {
            $this->routes[$httpMethod->value] = [];
        }

        $this->routes[$httpMethod->value][$url] = $callback;
    }

    public function match(string $uri, string $httpMethod, string $queryString)
    {
        $uri= str_replace('?' . $queryString, '', $uri);

        $httpMethodFound = false;

        // code 200
        foreach ($this->routes as $httpM => $routes) {
            if ($httpMethod === $httpM) {
                foreach ($routes as $route => $callback) {
                    if ($route === $uri) {
                        return $callback();
                    }
                }

                $httpMethodFound = true;
                break;
            }
        }

        $code = $httpMethodFound ? 404 : 400;

        http_response_code($code);

        if ($code === 404) {
            return "PAGE NOT FOUND";
        } else {
            return "BAD REQUEST";
        }
    }

    public function getBaseUrl(): string
    {
        return ($_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'];
    }

    public function getReferrer(): string
    {
        return substr($_SERVER['HTTP_REFERER'], 0, strlen($_SERVER['HTTP_REFERER']) - 1);
    }
}