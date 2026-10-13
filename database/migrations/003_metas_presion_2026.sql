-- Metas de presión tomadas de la matriz 2026.
INSERT INTO metas_indicador (indicador_id, ambito, referencia, valor, vigente_desde, activo)
SELECT id, 'localidad', 'SUNASS 2026', 28.0000, '2026-01-01', 1
FROM indicadores WHERE codigo = 'presion'
ON DUPLICATE KEY UPDATE valor = VALUES(valor), activo = VALUES(activo);

INSERT INTO metas_indicador (indicador_id, ambito, referencia, valor, vigente_desde, activo)
SELECT id, 'eps', 'EPS ILO 2026', 28.0000, '2026-01-01', 1
FROM indicadores WHERE codigo = 'presion'
ON DUPLICATE KEY UPDATE valor = VALUES(valor), activo = VALUES(activo);

INSERT INTO migraciones (archivo) VALUES ('003_metas_presion_2026.sql')
ON DUPLICATE KEY UPDATE aplicado_en = aplicado_en;
