<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vista no disponible.');
        }
        $layout = $data['layout'] ?? 'layouts/main';
        $usuario = \App\Servicios\ServicioAutenticacion::usuario();
        $mensaje = \App\Core\Sesion::tomarMensaje();
        $tokenFormulario = \App\Core\ProteccionFormulario::token();
        extract($data, EXTR_SKIP);
        $recursosVista = is_array($data['recursos'] ?? null) ? $data['recursos'] : [];
        $recursosBase = $layout === 'layouts/main'
            ? ['css' => ['simple-datatables.css'], 'js' => ['simple-datatables.js']]
            : ['css' => [], 'js' => []];
        $recursos = [
            'css' => array_values(array_unique(array_merge($recursosBase['css'], $recursosVista['css'] ?? []))),
            'js' => array_values(array_unique(array_merge($recursosBase['js'], $recursosVista['js'] ?? []))),
        ];
        $navigation = require BASE_PATH . '/config/navegacion.php';
        ob_start();
        require $file;
        $content = ob_get_clean();
        require BASE_PATH . '/app/Views/' . $layout . '.php';
    }
}
