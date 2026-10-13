<?php
declare(strict_types=1);

use App\Controladores\AutenticacionController;
use App\Controllers\ResumenController;
use App\Controllers\ModuloController;
use App\Controllers\RegistroController;
use App\Controllers\ConexionController;
use App\Controllers\RevisionController;
use App\Controllers\ResultadoController;
use App\Controllers\ConfiguracionController;
use App\Controllers\ReporteController;

$router->get('/instalacion', [AutenticacionController::class, 'instalacion']);
$router->post('/instalacion', [AutenticacionController::class, 'crearAdministrador']);
$router->get('/ingresar', [AutenticacionController::class, 'ingresar']);
$router->post('/ingresar', [AutenticacionController::class, 'autenticar']);
$router->post('/salir', [AutenticacionController::class, 'salir'], true);
$router->get('/', [ResumenController::class, 'index'], true);
$router->get('/resultados', [ResumenController::class, 'results'], true);
$router->get('/resultados/detalle', [ResultadoController::class, 'detalle'], true);
$router->get('/reportes/continuidad.csv', [ReporteController::class, 'continuidadCsv'], true);
$router->get('/reportes/presion.csv', [ReporteController::class, 'presionCsv'], true);
$router->post('/resultados/calcular', [ResultadoController::class, 'calcular'], true);
$router->get('/registros', [ModuloController::class, 'records'], true);
$router->post('/registros', [RegistroController::class, 'guardar'], true);
$router->get('/conexiones', [ModuloController::class, 'connections'], true);
$router->post('/conexiones', [ConexionController::class, 'guardar'], true);
$router->get('/revision', [ModuloController::class, 'review'], true);
$router->get('/revision/detalle', [ModuloController::class, 'reviewDetail'], true);
$router->post('/revision', [RevisionController::class, 'cambiarEstado'], true);
$router->get('/predicciones', [ModuloController::class, 'forecasts'], true);
$router->get('/configuracion', [ConfiguracionController::class, 'index'], true);
$router->post('/configuracion/metas', [ConfiguracionController::class, 'guardarMetas'], true);
