PS C:\laragon\www\gecopre> .\node_modules\.bin\tailwindcss.cmd -i .\resources\css\input.css -o .\public\assets\css\app.css --watch
≈ tailwindcss v4.3.3

# GECOPRE

Base MVC para continuidad y presión. PHP 8.1+, Tailwind CSS 4 y Bootstrap Icons locales.

## Estado de esta entrega

Incluye router, autoload de clases App, controladores, modelo de referencia, vistas, plantilla adaptable, alertas JS accesibles y navegación. El resumen y resultados presentan una copia identificada de los valores del Excel revisado. El formulario solo valida un ejemplo en el navegador. No hay autenticación, persistencia MySQL ni cálculos oficiales todavía.

## Estilos e iconos

```powershell
npm.cmd ci
npm.cmd run build
npm.cmd run dev
```

`build` copia Bootstrap Icons (con licencia) a public y compila CSS minificado. `dev` copia los iconos e inicia el observador. Editar `resources/css/input.css`, nunca el CSS compilado. Las clases PHP se detectan en app/Views. Los recursos compilados y los iconos públicos se conservan para poder ejecutar PHP sin Node en producción.

## Ejecutar con PHP 8.1.10 en este equipo

El comando global `php` apuntaba a PHP 7.4 al preparar esta entrega. Usar explícitamente:

```powershell
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' -S 127.0.0.1:8088 -t public tools/serve.php
```

Abrir http://127.0.0.1:8088. Mantener la terminal abierta; Ctrl+C detiene el servidor. Es un servidor local de desarrollo, no de producción.

## Laragon / Apache

1. Seleccionar PHP 8.1.10 o superior en Laragon y reiniciar sus servicios.
2. Usar `public/` como DocumentRoot. Hay una plantilla en docs/gecopre.test.conf.example para gecopre.test; el dominio requiere resolución local en hosts.
3. Alternativamente, con Apache encendido, abrir http://localhost/gecopre/public/.

El .htaccess de la raíz redirige la entrada a public y deniega las demás rutas del proyecto. No publicar con un servidor que ignore esos controles: usar siempre public como raíz. Las rutas mediante index.php?route= funcionan sin depender de enlaces reescritos; .htaccess también admite rutas limpias. No se modificó la configuración global de Laragon, Apache ni hosts.

## Estructura

```text
app/Core/          Router y renderizado
app/Controllers/   Coordinación de pantallas
app/Models/        Datos de referencia, futura capa de dominio
app/Views/         Plantilla y vistas PHP
config/            Aplicación y navegación
routes/            Rutas permitidas
public/            Única raíz pública
resources/css/     Entrada de Tailwind
tools/             Compilación de recursos y servidor de desarrollo
tests/             Comprobación HTTP básica
```

## Verificación

Con el servidor local abierto:

```powershell
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' tests/http-smoke.php
```

Comprueba pantallas, recursos, 404, 405, ruta inválida y que archivos privados no se sirvan. PHP 8.1.10 es la compatibilidad solicitada; para producción planificar una versión PHP soportada.

## Siguiente etapa

Convención de nombres: mantener carpetas técnicas (Controllers, Models, Views, config, public) y sufijos como Controller. Usar nombres de dominio en español, sin tildes: ModuloController.php, ResumenController.php, ResumenReferencia.php, navegacion.php y preferencias.php. Las tablas futuras se nombrarán en español con snake_case.

La primera migración de MySQL está en `database/migrations/001_estructura_inicial.sql`. Usa `.env.example` como referencia para crear un archivo `.env` local. La aplicación carga ese archivo local al iniciar, y `.env` está ignorado por Git.

Para crear la base de datos desde PowerShell, primero copia `.env.example` como `.env`, completa la contraseña local de MySQL y ejecuta este comando. El cliente pedirá la contraseña sin mostrarla:

```powershell
Get-Content .\database\migrations\001_estructura_inicial.sql | & 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe' -uroot -p
```

Después se puede revisar con:

```powershell
& 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe' -uroot -p -e "USE gecopre; SHOW TABLES;"
```

También puedes comprobar que PHP lea la configuración de `.env` y se conecte a MySQL:

```powershell
& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' tools\verificar_conexion_base_datos.php
```

## Cargar el catálogo inicial desde el Excel

El catálogo de 108 puntos de control se obtiene de la hoja `CONTINUIDAD`. La extracción no incorpora mediciones, conexiones ni fórmulas del Excel.

```powershell
& 'C:\Users\ASUS\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' tools\extraer_catalogo_excel.py 'C:\Users\ASUS\Downloads\01.00 MATRIZ DE CALCULO DE CONTINUIDAD Y PRESION - 2026.xlsx' .\storage\importaciones\catalogo_red_2026.json

& 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe' tools\importar_catalogo_excel.php
```

## Primer acceso

Al abrir GECOPRE por primera vez, el sistema redirige a `instalacion` para crear el administrador inicial. Luego solicita inicio de sesión antes de acceder a los módulos. Las contraseñas se guardan con `password_hash` y los formularios usan un token de sesión para evitar envíos desde otros sitios.

Esquema MySQL, acceso y permisos, catálogos y registro persistente. Las reglas de ceros, cobertura de monitoreo y metas se deben confirmar antes de generar resultados oficiales. Ver docs/PRELIMINAR_GECOPRE.md.
