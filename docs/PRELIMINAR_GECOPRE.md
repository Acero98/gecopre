# Propuesta preliminar de GECOPRE

Fecha: 27 de septiembre de 2026. Alcance: diseño funcional y técnico para revisión. No constituye una aplicación implementada.

## 1. Objetivo y base del diseño

Centralizar registros mensuales de continuidad y presión, controlar su revisión, consolidar resultados ponderados por conexiones activas y estimar uno o dos meses posteriores al último período disponible.

Fuente revisada: `01.00 MATRIZ DE CALCULO DE CONTINUIDAD Y PRESION - 2026.xlsx`. Contiene dos hojas independientes, 108 puntos catalogados, 12 sectores, 36 agrupaciones de presión y dos zonas geográficas. Hay 36 valores por indicador y mes entre enero y julio de 2026. El archivo no permite establecer por sí solo el plan obligatorio de muestreo ni reconstruir todas las lecturas de campo.

Primera versión propuesta: captura mensual compatible con el Excel. Una ampliación podrá registrar lecturas de campo con fecha, hora, instrumento y responsable. Esas lecturas necesitarán una regla explícita de consolidación mensual; no se mezclarán automáticamente con los valores mensuales importados.

## 2. Interfaz y módulos

Interfaz adaptable con menú lateral en escritorio y navegación compacta en móvil. Tailwind CSS para composición y estilos, Bootstrap Icons para iconografía, JavaScript para interacción, validaciones de apoyo y alertas. Todos los controles deben tener etiquetas y acceso por teclado.

| Módulo | Funciones propuestas |
| --- | --- |
| Acceso | Inicio y cierre de sesión, recuperación controlada de acceso, permisos por rol y ámbito |
| Resumen | Indicadores del mes y acumulados separados, conexiones, evolución, cobertura y pendientes |
| Registro mensual | Captura por período, sector y punto; continuidad y presión independientes; borradores y observaciones |
| Conexiones | Cantidad mensual por zona de presión, carga única compartida por ambos indicadores |
| Revisión | Lista de pendientes, devoluciones con motivo, aprobación y cierre |
| Resultados | Detalle por punto, zona de presión, sector, reservorio, zona geográfica y localidad; trazabilidad a entradas |
| Predicciones | Indicador, ámbito, fecha de corte, horizonte, método, error histórico, supuestos e incertidumbre |
| Reportes | Excel/PDF, filtros por período y ámbito, reporte de faltantes y comparación con metas |
| Importación | Vista previa, mapeo, validación, duplicados, conciliación y confirmación de lotes |
| Configuración | Catálogos, metas, plan de monitoreo, usuarios, roles y reglas versionadas |
| Auditoría | Autor, fecha, valores anteriores/nuevos, motivos, importaciones y reaperturas |

Los gráficos y reportes oficiales solo utilizan información aprobada según la versión de cálculo. Los borradores se muestran en vistas de trabajo claramente identificadas. La cobertura se compara contra el plan de monitoreo del período, no contra los 108 puntos del catálogo por defecto.

## 3. Roles propuestos

- Administrador: catálogos, reglas, metas y permisos; reaperturas justificadas.
- Operador: captura e importación dentro de los sectores asignados, corrección de observaciones.
- Supervisor: revisión, devolución, aprobación y cierre dentro de su ámbito.
- Consulta: lectura y exportación de resultados autorizados.

Los permisos se aplican en el servidor a cada solicitud y registro, no solamente ocultando botones.

## 4. Flujo operativo

1. Configurar estructura de red, metas vigentes y puntos previstos para cada período.
2. Abrir período mensual para cada localidad e indicador.
3. Registrar datos manualmente o importar un lote en borrador.
4. Registrar conexiones activas compartidas del mes.
5. Validar duplicados, unidades, rangos, puntos esperados y disponibilidad de conexiones.
6. Enviar a revisión. El supervisor devuelve con observación o aprueba.
7. Ejecutar el cálculo con la regla vigente y guardar una versión de resultados y sus entradas.
8. Cerrar el período y habilitar reportes oficiales. Las predicciones usan una fecha de corte explícita.
9. Una corrección posterior exige reapertura con motivo, auditoría y una nueva versión; la anterior se conserva.

Estados: borrador → en revisión → aprobado → cerrado. La devolución regresa a borrador con observaciones. La reapertura de un período cerrado corresponde a un permiso específico.

## 5. Lógica de cálculo

### Entradas

- Continuidad: capturar `HH:MM`, incluyendo `24:00`. Guardar minutos enteros entre 0 y 1440; convertir a horas decimales como minutos / 60.
- Presión: decimal en m.c.a. Valor no negativo; umbral superior y advertencias configurables, no un límite inventado.
- Conexiones: entero no negativo por zona de presión y período. Cero conexiones es distinto de conexiones no informadas.
- Ausencia: valor NULL más estado/motivo. Un valor cero se preserva como observación real.
- Unicidad mensual: punto + período + indicador. Las correcciones se auditan; múltiples lecturas de campo requieren otro modelo.

### Consolidación

1. Zona de presión: media de observaciones elegibles del mes, conforme a la regla aprobada.
2. Sector: suma de (media de zona de presión × conexiones de esa zona) / suma de conexiones correspondientes.
3. Zona geográfica: suma de (resultado sectorial × conexiones sectoriales) / suma de conexiones de esos sectores.
4. Localidad: misma ponderación sobre zonas geográficas.
5. Acumulado disponible: suma de (resultado mensual × conexiones mensuales) / suma de conexiones mensuales, siguiendo el Excel revisado. No es suma de horas ni promedio simple de los meses.
6. Reservorio: agrupación por fuente según relaciones vigentes; el Excel muestra el acumulado. Evitar duplicar conexiones si un sector tiene varias fuentes.
7. Cumplimiento: mínimo entre resultado/meta y 1, únicamente si resultado y meta son válidos. Conservar resultado sin tope para análisis. La meta debe ser positiva, vigente y del ámbito correcto.

El reservorio es una dimensión de agrupación; no es un paso obligatorio entre el sector y la zona geográfica. El catálogo incluye una fuente combinada R8/R9. Si en el futuro se requiere separar varias fuentes por sector, modelar la relación y definir explícitamente la asignación, sin contar conexiones dos veces.

### Reglas que deben resolverse antes de emitir resultados oficiales

- El Excel excluye ceros mediante COUNTIF mayor que cero. Propuesta: preservar ceros reales e incluirlos salvo que la metodología aprobada indique otra cosa. Comparar en migración el cálculo heredado y el propuesto.
- Metas de continuidad: SUNASS 17; EPS visible 19,2, pero fórmula EPS usa 19. No decidir automáticamente cuál es la meta oficial.
- Existen 12 fórmulas de presión con rangos que omiten puntos; la agrupación del sistema debe basarse en relaciones, no en filas fijas.
- Confirmar qué representa el valor mensual de cada punto y cuál es su procedimiento de obtención.
- Confirmar plan de monitoreo y tratamiento de puntos sin lectura. No interpretar 36/108 como incumplimiento automáticamente.
- Con conexiones pero sin lectura, no publicar un agregado completo: marcarlo incompleto o parcial con cobertura y denominador explícitos. No eliminar silenciosamente ese grupo del denominador.
- Con denominador cero, mostrar no disponible con su causa. No convertir una división inválida en un cero operativo.
- Cambios en agrupaciones y catálogos deben tener vigencia para no modificar retrospectivamente resultados históricos.

## 6. Predicción a uno o dos meses

Fase inicial: continuidad y presión mensuales por localidad. Extensión por sector únicamente cuando haya series suficientemente completas. Con siete meses, el resultado será exploratorio; no se puede estimar una estacionalidad anual confiable.

Comparar último valor observado, promedio móvil y suavizamiento exponencial simple. Evaluar una tendencia lineal solo si mejora las pruebas fuera de muestra y no produce extrapolaciones inadmisibles. Estos métodos pueden implementarse en servicios PHP sin exigir Python en producción.

Validación temporal con orígenes sucesivos: entrenar solo con meses anteriores y evaluar horizontes de uno y dos meses por separado. Comparar MAE en horas y m.c.a.; evitar MAPE si existen ceros. Ajustar parámetros sin acceder al período que se usa para evaluar. Con pocas ventanas, informar la limitación y preferir un método simple cuando las diferencias no sean concluyentes.

Guardar serie usada, corte, método, parámetros, horizonte, error de prueba, resultado, versión y supuesto de conexiones. Mantener separadas predicciones y observaciones. Cuando llegue el dato real, medir el error sin sobrescribir el pronóstico original.

No mostrar un intervalo como “95 % de confianza” sin un procedimiento válido y suficientes residuos. Si el historial no lo permite, mostrar incertidumbre no estimable o un rango de escenario explícitamente exploratorio. Respetar continuidad entre 0 y 24 horas; registrar cualquier restricción aplicada al pronóstico. La presión no debe predecirse negativa.

Si se pronostica directamente la serie de localidad, declararlo. Si se agregan pronósticos sectoriales, utilizar las mismas ponderaciones y un escenario de conexiones compartido para ambos indicadores. No presentar ambos caminos como equivalentes.

## 7. Arquitectura MVC

Plataforma solicitada: PHP 8.1.10, MySQL 8.0.30, Apache/Laragon; MVC propio con Composer y autoload PSR-4. PDO para acceso a MySQL. Tailwind compilado como CSS estático para producción. Bootstrap Icons sin cargar todo el CSS de Bootstrap. Alertas JavaScript mediante una biblioteca como SweetAlert2 o un componente accesible equivalente, con versión compatible fijada.

```text
gecopre/
  app/
    Controllers/    Auth, Registro, Revision, Resultado, Prediccion, Importacion
    Models/         Punto, RegistroMensual, ConexionMensual, Periodo, Meta, Usuario
    Services/       CalculoService, PrediccionService, ImportacionService, CierreService
    Repositories/   Consultas PDO y persistencia
    Validators/     Reglas de entradas y cobertura
    Middleware/     Sesion, Permisos, CSRF
    Views/          layouts/, resumen/, registros/, revision/, resultados/, predicciones/
  config/          app.php, database.php
  routes/          web.php
  public/          index.php, assets/css/, assets/js/, assets/icons/
  resources/       css/, js/
  database/        migrations/, seeds/
  storage/         imports/, exports/, logs/
  tests/           Unit/, Integration/
  docs/
  composer.json
  .env.example
```

Petición → router → middleware → controlador → servicio → repositorio/modelo → MySQL. El controlador devuelve una vista PHP o JSON para las interacciones. Las reglas de cálculo viven en servicios, no en las vistas ni duplicadas en JavaScript. `public/` será el único directorio expuesto por Apache.

Node se utiliza únicamente durante la compilación de estilos y recursos; el servidor de producción ejecuta PHP y sirve los archivos compilados. Seleccionar y fijar dependencias compatibles con PHP 8.1, sin asumir que la versión más reciente de cada paquete es compatible.

## 8. Modelo preliminar de MySQL

Usar InnoDB, utf8mb4, claves foráneas, fechas de creación/modificación y transacciones. DECIMAL para presión, metas y resultados; evitar FLOAT para los valores que deben conciliar con reportes.

| Tabla o grupo | Propósito y campos principales |
| --- | --- |
| localidades, zonas_geograficas, fuentes_abastecimiento, sectores, zonas_presion, puntos_control | Identificadores estables, códigos únicos, nombres, dirección, activo |
| asignaciones_red | Relaciones con vigencia desde/hasta; validar que no se superpongan |
| periodos | Localidad, año, mes; unicidad por localidad/año/mes |
| cierres_indicador | Período, indicador, estado, versión vigente, revisor y fecha |
| plan_monitoreo | Período, punto, indicador, requerido; cobertura esperada |
| registros_mensuales | Punto, período, indicador, valor nullable, estado del dato, observación, autor, lote, versión; único punto/período/indicador |
| conexiones_mensuales | Zona de presión, período, cantidad nullable, estado y autor; único zona/período |
| metas | Indicador, ámbito, entidad de referencia, vigencia, valor y versión; impedir vigencias superpuestas |
| versiones_calculo | Período, indicador, regla, fecha, autor y huella de entradas |
| resultados_calculo | Versión, ámbito, valor nullable, conexiones, cobertura, estado de completitud |
| entradas_calculo | Referencias y copia versionada de entradas y asignaciones utilizadas |
| ejecuciones_prediccion, predicciones | Corte, serie, ámbito, método, parámetros, métricas, horizonte, mes objetivo, valor y rango nullable |
| lotes_importacion, filas_importacion | Archivo/hash, hoja/celda, valor original, mapeo, errores, estado y usuario |
| usuarios, roles, permisos, usuario_rol, rol_permiso, usuario_ambito | Autenticación y autorización |
| auditoria | Acción, entidad, antes/después, motivo, autor y fecha |

Índices: registros por período/indicador/estado, conexiones por período, resultados por versión/ámbito, predicciones por corte/ámbito/indicador, auditoría por entidad/fecha. Validar ámbitos y relaciones mediante claves foráneas o tablas de asociación; no permitir identificadores polimórficos huérfanos.

Para `registros_mensuales`, el valor numérico puede ser DECIMAL con CHECK que exija minutos enteros de 0 a 1440 en continuidad y presión no negativa, más validaciones PHP. Guardar unidad en el catálogo de indicadores, no recibirla libremente del navegador. La representación exacta del esquema se definirá en las migraciones, no es un SQL ejecutable en esta propuesta.

## 9. Importación del Excel

1. Subir archivo con límites de tamaño y tipo. Leer celdas, sin ejecutar macros ni tratar textos del documento como instrucciones.
2. Reconocer hojas y encabezados; resolver celdas combinadas para la jerarquía.
3. Mostrar puntos, período, unidad y valores que serán importados; importar entradas, no fórmulas como reglas del sistema.
4. Convertir horas Excel a minutos. Conservar referencia de hoja y celda como trazabilidad.
5. Conciliar conexiones entre las dos hojas y crear una sola fuente compartida. Señalar discrepancias.
6. Detectar duplicados por clave y lotes repetidos mediante hash. No sobrescribir datos aprobados automáticamente.
7. Validar y confirmar el lote con transacción; conservar errores por fila para corrección.
8. Comparar resultados calculados con los del archivo y registrar diferencias por correcciones de metodología.

## 10. Seguridad y operación

- Contraseñas con password_hash/password_verify, regeneración de sesión tras autenticación, cookies HttpOnly, SameSite y Secure bajo HTTPS.
- Consultas preparadas PDO, validación del lado servidor, protección CSRF y escape de salida contra XSS.
- Restricción de intentos de acceso, permisos por ámbito y control de concurrencia para evitar sobrescrituras.
- Secretos fuera de public y del repositorio; usuario MySQL con permisos mínimos.
- Importaciones fuera de public; límites de tamaño, archivos y filas; exportaciones seguras frente a fórmulas inyectadas.
- Copias de seguridad y prueba de restauración. Transacciones y auditoría en aprobación, importación y cierre.
- Guardar tiempos técnicos de auditoría en UTC y mostrar en la zona horaria configurada; períodos como año/mes, sin depender de la zona horaria del servidor.

PHP 8.1 finalizó soporte oficial el 31 de diciembre de 2025. Se puede diseñar compatibilidad para el entorno solicitado, pero antes de producción se debe planificar una actualización a una rama soportada y una versión de mantenimiento vigente de MySQL. No se ha actualizado el entorno en este trabajo.

## 11. Pruebas y criterios de aceptación

- Conciliar enero–julio con el Excel bajo la regla heredada: continuidad acumulada 16,62448083168214 horas; presión 24,62934338234285 m.c.a.; julio 32.110 conexiones.
- Casos 00:00, 24:00, blanco, presión cero, sin conexiones, lectura faltante y meta ausente/cero.
- Agregar un punto sin editar fórmulas; verificar pertenencia por vigencia y cobertura planificada.
- Evitar duplicados, rangos incompletos, redondeo prematuro y doble conteo de conexiones/fuentes.
- Bloquear escritura en meses cerrados y operaciones fuera del ámbito del usuario, incluso accediendo directamente a una URL.
- Importar dos veces el mismo archivo sin duplicar; probar corrección, devolución y reapertura.
- Verificar validación temporal de predicciones sin usar datos posteriores al corte.
- Mostrar origen de cada resultado, período cubierto, regla y estado; exportaciones consistentes con pantalla.

## 12. Entregas propuestas

1. Base: acceso, roles, catálogos, captura mensual, conexiones y revisión.
2. Cálculos y reportes: reglas aprobadas, trazabilidad, importación y conciliación con Excel.
3. Predicciones: comparación de métodos, validación histórica y registro de errores.
4. Ampliaciones según necesidad: lecturas de campo, adjuntos y mapas cuando existan coordenadas verificadas.

La primera decisión funcional es definir el valor mensual, el plan de monitoreo, la política de ceros y las metas oficiales. El diseño de navegación puede revisarse antes de resolver esas reglas; el cálculo oficial depende de ellas.

Referencias técnicas consultadas:
- https://www.php.net/eol.php
- https://tailwindcss.com/docs/installation/play-cdn
- https://icons.getbootstrap.com/
