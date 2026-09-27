<?php
declare(strict_types=1);

use App\Core\ConexionBaseDatos;

require dirname(__DIR__) . '/app/bootstrap.php';

$archivo = $argv[1] ?? dirname(__DIR__) . '/storage/importaciones/mediciones_2026.json';
if (!is_file($archivo)) {
    fwrite(STDERR, "No existe el archivo de mediciones: {$archivo}" . PHP_EOL);
    exit(1);
}

$datos = json_decode((string) file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR);
$registros = $datos['registros'] ?? [];
$conexiones = $datos['conexiones'] ?? [];
if (count($registros) !== 504 || count($conexiones) !== 252) {
    fwrite(STDERR, 'La carga debe contener 504 mediciones y 252 registros de conexiones.' . PHP_EOL);
    exit(1);
}

$conexion = ConexionBaseDatos::obtener();
try {
    $conexion->beginTransaction();
    $localidadId = (int) $conexion->query("SELECT id FROM localidades WHERE codigo = 'ILO' LIMIT 1")->fetchColumn();
    if ($localidadId === 0) {
        throw new RuntimeException('No se encontró la localidad ILO. Primero importa el catálogo.');
    }
    $indicadores = $conexion->query('SELECT codigo, id FROM indicadores')->fetchAll(\PDO::FETCH_KEY_PAIR);
    $puntos = $conexion->query('SELECT codigo, id FROM puntos_control')->fetchAll(\PDO::FETCH_KEY_PAIR);
    $zonas = $conexion->query('SELECT CONCAT(so.codigo, "|", zp.nombre) AS clave, zp.id FROM zonas_presion zp INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id')->fetchAll(\PDO::FETCH_KEY_PAIR);

    $periodo = $conexion->prepare(
        "INSERT INTO periodos (localidad_id, anio, mes, estado) VALUES (:localidad_id, :anio, :mes, 'en_revision') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
    );
    $periodos = [];
    for ($mes = 1; $mes <= 7; $mes++) {
        $periodo->execute(['localidad_id' => $localidadId, 'anio' => 2026, 'mes' => $mes]);
        $periodos[$mes] = (int) $conexion->lastInsertId();
    }

    $guardarRegistro = $conexion->prepare(
        "INSERT INTO registros_mensuales (periodo_id, punto_control_id, indicador_id, valor_decimal, estado_dato, observacion)
         VALUES (:periodo_id, :punto_control_id, :indicador_id, :valor_decimal, 'registrado', 'Importado desde la matriz de cálculo 2026.')
         ON DUPLICATE KEY UPDATE valor_decimal = VALUES(valor_decimal), estado_dato = 'registrado', observacion = VALUES(observacion)"
    );
    foreach ($registros as $registro) {
        $puntoId = $puntos[$registro['codigo_punto']] ?? null;
        $indicadorId = $indicadores[$registro['indicador']] ?? null;
        if ($puntoId === null || $indicadorId === null) {
            throw new RuntimeException('No se pudo relacionar una medición con su punto o indicador.');
        }
        $guardarRegistro->execute([
            'periodo_id' => $periodos[(int) $registro['mes']],
            'punto_control_id' => $puntoId,
            'indicador_id' => $indicadorId,
            'valor_decimal' => $registro['valor'],
        ]);
    }

    $guardarConexion = $conexion->prepare(
        "INSERT INTO conexiones_mensuales (periodo_id, zona_presion_id, cantidad, estado_dato, observacion)
         VALUES (:periodo_id, :zona_presion_id, :cantidad, 'registrado', 'Importado desde la matriz de cálculo 2026.')
         ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), estado_dato = 'registrado', observacion = VALUES(observacion)"
    );
    foreach ($conexiones as $conexionMensual) {
        $zonaId = $zonas[$conexionMensual['sector'] . '|' . $conexionMensual['zona_presion']] ?? null;
        if ($zonaId === null) {
            throw new RuntimeException('No se pudo relacionar una conexión con una zona de presión.');
        }
        $guardarConexion->execute([
            'periodo_id' => $periodos[(int) $conexionMensual['mes']],
            'zona_presion_id' => $zonaId,
            'cantidad' => $conexionMensual['cantidad'],
        ]);
    }
    $conexion->commit();
    echo 'Mediciones históricas importadas correctamente.' . PHP_EOL;
} catch (\Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    fwrite(STDERR, 'No se pudieron importar las mediciones: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
