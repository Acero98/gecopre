<?php
declare(strict_types=1);

use App\Controladores\AutenticacionController;
use App\Controllers\ResumenController;
use App\Controllers\ModuloController;

$router->get('/instalacion', [AutenticacionController::class, 'instalacion']);
$router->post('/instalacion', [AutenticacionController::class, 'crearAdministrador']);
$router->get('/ingresar', [AutenticacionController::class, 'ingresar']);
$router->post('/ingresar', [AutenticacionController::class, 'autenticar']);
$router->post('/salir', [AutenticacionController::class, 'salir'], true);
$router->get('/', [ResumenController::class, 'index'], true);
$router->get('/resultados', [ResumenController::class, 'results'], true);
$router->get('/registros', [ModuloController::class, 'records'], true);
$router->get('/conexiones', [ModuloController::class, 'connections'], true);
$router->get('/revision', [ModuloController::class, 'review'], true);
$router->get('/predicciones', [ModuloController::class, 'forecasts'], true);
$router->get('/configuracion', [ModuloController::class, 'settings'], true);
