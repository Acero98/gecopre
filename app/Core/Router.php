<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, bool $requiereAutenticacion = false): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'requiere_autenticacion' => $requiereAutenticacion];
    }

    public function post(string $path, array $handler, bool $requiereAutenticacion = false): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'requiere_autenticacion' => $requiereAutenticacion];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = $method === 'HEAD' ? 'GET' : $method;
        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            View::render('errors/not-found', ['title' => 'Página no encontrada', 'active' => '']);
            return;
        }
        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST, HEAD');
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Método no permitido.';
            return;
        }
        $route = $this->routes[$method][$path];
        if ($route['requiere_autenticacion'] && \App\Servicios\ServicioAutenticacion::usuario() === null) {
            header('Location: ' . url('/ingresar'));
            return;
        }
        [$controller, $action] = $route['handler'];
        (new $controller())->$action();
    }
}
