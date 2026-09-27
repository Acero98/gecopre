<?php
declare(strict_types=1);

use App\Core\ConexionBaseDatos;

require dirname(__DIR__) . '/app/bootstrap.php';

$archivo = $argv[1] ?? dirname(__DIR__) . '/storage/importaciones/catalogo_red_2026.json';
if (!is_file($archivo)) {
    fwrite(STDERR, "No existe el archivo de catálogo: {$archivo}" . PHP_EOL);
    exit(1);
}

$contenido = file_get_contents($archivo);
$catalogo = json_decode($contenido ?: '', true, 512, JSON_THROW_ON_ERROR);
$puntos = $catalogo['puntos'] ?? [];
if (count($puntos) !== 108) {
    fwrite(STDERR, 'El archivo debe contener exactamente 108 puntos de control.' . PHP_EOL);
    exit(1);
}

$conexion = ConexionBaseDatos::obtener();
$obtenerId = static function (string $tabla, array $datos, array $claves) use ($conexion): int {
    $columnas = array_keys($datos);
    $marcadores = array_map(static fn (string $columna): string => ':' . $columna, $columnas);
    $actualizaciones = implode(', ', array_map(static fn (string $columna): string => "{$columna} = VALUES({$columna})", array_filter($columnas, static fn (string $columna): bool => !in_array($columna, $claves, true))));
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
        $tabla,
        implode(', ', $columnas),
        implode(', ', $marcadores),
        $actualizaciones === '' ? implode(', ', array_map(static fn (string $columna): string => "{$columna} = {$columna}", $claves)) : $actualizaciones
    );
    $sentencia = $conexion->prepare($sql);
    $sentencia->execute($datos);

    $condiciones = [];
    $parametros = [];
    foreach ($claves as $clave) {
        $condiciones[] = "{$clave} = :clave_{$clave}";
        $parametros['clave_' . $clave] = $datos[$clave];
    }
    $consulta = $conexion->prepare(sprintf('SELECT id FROM %s WHERE %s LIMIT 1', $tabla, implode(' AND ', $condiciones)));
    $consulta->execute($parametros);
    return (int) $consulta->fetchColumn();
};

try {
    $conexion->beginTransaction();

    foreach ($puntos as $punto) {
        $localidadId = $obtenerId('localidades', [
            'codigo' => 'ILO',
            'nombre' => $punto['localidad'],
            'activo' => 1,
        ], ['codigo']);

        $zonaGeograficaId = $obtenerId('zonas_geograficas', [
            'localidad_id' => $localidadId,
            'codigo' => preg_replace('/[^A-Z0-9]+/u', '_', strtoupper($punto['zona_geografica'])) ?: 'ZONA',
            'nombre' => $punto['zona_geografica'],
            'activo' => 1,
        ], ['localidad_id', 'codigo']);

        $fuenteId = $obtenerId('fuentes_abastecimiento', [
            'localidad_id' => $localidadId,
            'codigo' => preg_replace('/\s+/u', '', $punto['fuente']),
            'nombre' => 'Reservorio ' . $punto['fuente'],
            'activo' => 1,
        ], ['localidad_id', 'codigo']);

        $sectorId = $obtenerId('sectores_operacionales', [
            'zona_geografica_id' => $zonaGeograficaId,
            'codigo' => $punto['sector'],
            'nombre' => 'Sector ' . $punto['sector'],
            'activo' => 1,
        ], ['zona_geografica_id', 'codigo']);

        $fuenteSector = $conexion->prepare(
            'INSERT IGNORE INTO fuentes_sectores (fuente_abastecimiento_id, sector_operacional_id, vigente_desde) VALUES (:fuente_id, :sector_id, :vigente_desde)'
        );
        $fuenteSector->execute(['fuente_id' => $fuenteId, 'sector_id' => $sectorId, 'vigente_desde' => '2026-01-01']);

        $zonaPresionId = $obtenerId('zonas_presion', [
            'sector_operacional_id' => $sectorId,
            'nombre' => $punto['zona_presion'],
            'activo' => 1,
        ], ['sector_operacional_id', 'nombre']);

        $obtenerId('puntos_control', [
            'codigo' => $punto['codigo'],
            'direccion' => $punto['direccion'],
            'zona_presion_id' => $zonaPresionId,
            'activo' => 1,
        ], ['codigo']);
    }

    $conexion->commit();
    $resumen = $conexion->query(
        'SELECT
            (SELECT COUNT(*) FROM localidades) AS localidades,
            (SELECT COUNT(*) FROM zonas_geograficas) AS zonas_geograficas,
            (SELECT COUNT(*) FROM fuentes_abastecimiento) AS fuentes,
            (SELECT COUNT(*) FROM sectores_operacionales) AS sectores,
            (SELECT COUNT(*) FROM zonas_presion) AS zonas_presion,
            (SELECT COUNT(*) FROM puntos_control) AS puntos_control'
    )->fetch(\PDO::FETCH_ASSOC);
    echo 'Catálogo importado correctamente.' . PHP_EOL;
    echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    fwrite(STDERR, 'No se pudo importar el catálogo: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
