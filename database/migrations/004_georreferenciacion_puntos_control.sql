ALTER TABLE puntos_control
    ADD COLUMN latitud DECIMAL(10,7) NULL AFTER direccion,
    ADD COLUMN longitud DECIMAL(10,7) NULL AFTER latitud,
    ADD COLUMN coordenadas_referenciales TINYINT(1) NOT NULL DEFAULT 1 AFTER longitud;

UPDATE puntos_control pc
INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id
INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id
SET pc.latitud = CASE
        WHEN zg.nombre LIKE '%PUERTO%' THEN -17.6400000 - ((pc.id % 9) * 0.0011000)
        ELSE -17.6690000 - ((pc.id % 9) * 0.0011000)
    END,
    pc.longitud = CASE
        WHEN zg.nombre LIKE '%PUERTO%' THEN -71.3470000 + (FLOOR((pc.id - 1) / 9) * 0.0010000)
        ELSE -71.3360000 + (FLOOR((pc.id - 1) / 9) * 0.0010000)
    END
WHERE pc.latitud IS NULL OR pc.longitud IS NULL;
