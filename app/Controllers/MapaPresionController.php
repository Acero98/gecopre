<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Core\View;
use App\Repositorios\RepositorioMapaPresion;
use App\Servicios\ServicioAutenticacion;
use App\Servicios\ServicioExcelUbicaciones;

final class MapaPresionController
{
    public function index(): void
    {
        header('Cache-Control: no-store, max-age=0');
        $repositorio = new RepositorioMapaPresion();
        $periodos = $repositorio->periodosDisponibles();
        $periodoId = filter_input(INPUT_GET, 'periodo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: (int) ($periodos[0]['id'] ?? 0);
        $puntoId = filter_input(INPUT_GET, 'punto', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $indicador = ($_GET['indicador'] ?? 'presion') === 'continuidad' ? 'continuidad' : 'presion';
        $puntos = $periodoId > 0 ? $repositorio->puntosParaMapa($periodoId, $indicador) : [];
        foreach ($puntos as &$punto) {
            $imagen = basename((string) ($punto['imagen_referencia'] ?? ''));
            $punto['imagen_url'] = $imagen !== '' ? base_url() . '/uploads/referencias/' . rawurlencode($imagen) : null;
        }
        unset($punto);
        View::render('mapa_presion/index', [
            'title' => 'Mapa de presión',
            'active' => '/mapa-presion',
            'periodos' => $periodos,
            'periodoId' => $periodoId,
            'puntoId' => $puntoId,
            'indicador' => $indicador,
            'puntos' => $puntos,
            'maptilerApiKey' => trim((string) (getenv('MAPTILER_API_KEY') ?: '')),
            'recursos' => [
                'css_externos' => ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'],
                'js_externos' => ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'],
                'js' => ['sweetalert2.all.min.js', 'mapa-presion.js'],
            ],
        ]);
    }

    public function ubicaciones(): void
    {
        View::render('mapa_presion/ubicaciones', ['title' => 'Ubicaciones de puntos', 'active' => '/mapa-presion', 'puntos' => (new RepositorioMapaPresion())->puntosUbicacion(), 'recursos' => ['js' => ['sweetalert2.all.min.js']]]);
    }

    public function guardarUbicacion(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) { Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.'); $this->redirigirUbicaciones(); }
        $puntoId = filter_var($_POST['punto_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $latitud = filter_var($_POST['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitud = filter_var($_POST['longitud'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($puntoId === false || $latitud === false || $longitud === false || $latitud < -18 || $latitud > -17 || $longitud < -72 || $longitud > -70) { Sesion::mensaje('error', 'Ingresa coordenadas válidas para Ilo.'); $this->redirigirUbicaciones(); }
        $imagen = null;
        try {
            $imagen = $this->guardarImagenReferencia((int) $puntoId);
            $repositorio = new RepositorioMapaPresion();
            $repositorio->actualizarCoordenadas((int) $puntoId, (float) $latitud, (float) $longitud, (int) ServicioAutenticacion::usuario()['id']);
            if ($imagen !== null) {
                $anterior = $repositorio->actualizarImagenReferencia((int) $puntoId, $imagen, (int) ServicioAutenticacion::usuario()['id']);
                if ($anterior !== null) $this->eliminarImagenReferencia($anterior);
            }
            Sesion::mensaje('exito', $imagen !== null ? 'Ubicación y foto de referencia actualizadas.' : 'Ubicación actualizada.');
        } catch (\DomainException $error) {
            if ($imagen !== null) $this->eliminarImagenReferencia($imagen);
            Sesion::mensaje('error', $error->getMessage());
        } catch (\Throwable) {
            if ($imagen !== null) $this->eliminarImagenReferencia($imagen);
            Sesion::mensaje('error', 'No se pudo actualizar la ubicación.');
        }
        header('Location: ' . url('/mapa-presion') . '&punto=' . (int) $puntoId . '&actualizado=' . time());
        exit;
    }

    public function descargarPlantillaUbicaciones(): void
    {
        (new ServicioExcelUbicaciones())->descargar((new RepositorioMapaPresion())->puntosUbicacion());
    }

    public function importarUbicaciones(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.');
            $this->redirigirUbicaciones();
        }
        $archivo = $_FILES['archivo_ubicaciones'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($archivo['tmp_name'] ?? ''))) {
            Sesion::mensaje('error', 'Selecciona un archivo Excel válido para importar.');
            $this->redirigirUbicaciones();
        }
        if ((int) ($archivo['size'] ?? 0) > 5 * 1024 * 1024 || strtolower(pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_EXTENSION)) !== 'xlsx') {
            Sesion::mensaje('error', 'El archivo debe ser un Excel .xlsx de hasta 5 MB.');
            $this->redirigirUbicaciones();
        }
        try {
            $excel = new ServicioExcelUbicaciones();
            $resultado = (new RepositorioMapaPresion())->importarUbicaciones($excel->leer((string) $archivo['tmp_name']), (int) ServicioAutenticacion::usuario()['id']);
            $texto = "Importación finalizada: {$resultado['creados']} creados, {$resultado['actualizados']} actualizados y {$resultado['omitidos']} sin cambios.";
            if ($resultado['errores'] !== []) $texto .= ' ' . count($resultado['errores']) . ' filas no se procesaron por datos inválidos o relaciones inexistentes.';
            $huboCambios = $resultado['creados'] + $resultado['actualizados'] > 0;
            $tieneErrores = $resultado['errores'] !== [];
            Sesion::mensaje($huboCambios || !$tieneErrores ? 'exito' : 'error', $texto, $tieneErrores && !$huboCambios ? 'Importación con observaciones' : 'Importación finalizada');
        } catch (\DomainException $error) {
            Sesion::mensaje('error', $error->getMessage());
        } catch (\Throwable) {
            Sesion::mensaje('error', 'No se pudo importar el archivo. Verifica que uses la plantilla descargada.');
        }
        $this->redirigirUbicaciones();
    }

    private function redirigirUbicaciones(): never { header('Location: ' . url('/mapa-presion/ubicaciones')); exit; }

    private function guardarImagenReferencia(int $puntoId): ?string
    {
        $archivo = $_FILES['imagen_referencia'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($archivo['tmp_name'] ?? ''))) {
            throw new \DomainException('No se pudo cargar la imagen de referencia.');
        }
        if ((int) ($archivo['size'] ?? 0) > 5 * 1024 * 1024) throw new \DomainException('La imagen de referencia no debe superar los 5 MB.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $archivo['tmp_name']);
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensiones[$mime]) || @getimagesize((string) $archivo['tmp_name']) === false) {
            throw new \DomainException('La foto debe estar en formato JPG, PNG o WebP.');
        }
        $directorio = BASE_PATH . '/public/uploads/referencias';
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new \RuntimeException('No se pudo preparar el almacenamiento de imágenes.');
        }
        $nombre = 'punto-' . $puntoId . '-' . bin2hex(random_bytes(8)) . '.' . $extensiones[$mime];
        if (!move_uploaded_file((string) $archivo['tmp_name'], $directorio . '/' . $nombre)) {
            throw new \RuntimeException('No se pudo guardar la imagen de referencia.');
        }
        return $nombre;
    }

    private function eliminarImagenReferencia(string $nombre): void
    {
        $archivo = BASE_PATH . '/public/uploads/referencias/' . basename($nombre);
        if (is_file($archivo)) @unlink($archivo);
    }
}
