<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title) ?> · GECOPRE</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= e(asset('css/inter.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('icons/bootstrap-icons.css')) ?>">
    <?php foreach ($recursos['css'] as $hojaEstilo): ?>
        <link rel="stylesheet" href="<?= e(asset('css/' . $hojaEstilo)) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php foreach ($recursos['js'] as $script): ?><script defer src="<?= e(asset('js/' . $script)) ?>"></script>
    <?php endforeach; ?>
    <script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>

<body class="bg-shell font-sans text-slate-700 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3">Saltar al contenido</a>
    <div class="app-shell min-h-screen lg:grid">
        <aside id="sidebar" class="app-sidebar border-r border-slate-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
            <div class="flex items-center justify-between gap-2 px-4 py-5">
                <a class="brand-logo" href="<?= e(url()) ?>" aria-label="EPS Ilo, GECOPRE, inicio">
                    <img src="<?= e(asset('images/logo-eps-ilo.png')) ?>" alt="EPS Ilo" width="190" height="48">
                    <span class="brand-mark" style="--brand-logo-url: url('<?= e(asset("images/logo-eps-ilo.png")) ?>')" aria-hidden="true"></span>
                </a>
                <button type="button" id="sidebar-collapse" class="sidebar-toggle hidden lg:inline-flex" aria-controls="sidebar" aria-pressed="false" aria-label="Contraer menú a iconos" data-tooltip="Contraer menú"><i class="bi bi-layout-sidebar-inset text-lg" aria-hidden="true"></i></button>
                <button type="button" id="menu-toggle" class="button-secondary lg:hidden" aria-controls="main-navigation" aria-expanded="true" aria-label="Mostrar u ocultar navegación"><i class="bi bi-list text-xl" aria-hidden="true"></i></button>
            </div>
            <nav id="main-navigation" class="px-4 pb-6" aria-label="Navegación principal">
                <p class="nav-section-label mb-3 px-3 text-[11px] font-semibold tracking-widest text-slate-400">GESTIÓN DEL SERVICIO</p>
                <div class="flex flex-wrap gap-1 lg:flex-col">
                    <?php foreach ($navigation as $item): ?>
                        <a href="<?= e(url($item['path'])) ?>" aria-label="<?= e($item['label']) ?>" data-tooltip="<?= e($item['label']) ?>" class="nav-item <?= $active === $item['path'] ? 'nav-active' : '' ?>" <?= $active === $item['path'] ? 'aria-current="page"' : '' ?>>
                            <i class="bi bi-<?= e($item['icon']) ?> text-lg" aria-hidden="true"></i><span class="nav-label"><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
            <div class="sidebar-footer mx-6 mt-auto hidden border-t border-slate-100 py-6 lg:block">
                <p class="text-sm font-semibold text-slate-800">Provincia de Ilo</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">2 zonas · 12 sectores<br>108 puntos en el archivo de referencia</p>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4 lg:px-9">
                <p class="text-sm"><span class="text-slate-400">Gestión del servicio</span><span class="mx-3 text-slate-300">/</span><span class="font-medium text-slate-700"><?= e($title) ?></span></p>
                <?php if ($usuario !== null): ?>
                    <div class="flex items-center gap-3"><span class="hidden text-right sm:block"><span class="block text-sm font-semibold text-slate-800"><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></span><span class="block text-xs text-slate-500"><?= e($usuario['roles']) ?></span></span>
                        <form method="post" action="<?= e(url('/salir')) ?>"><input type="hidden" name="token" value="<?= e($tokenFormulario) ?>"><button class="sidebar-toggle" type="submit" aria-label="Cerrar sesión" data-tooltip="Cerrar sesión"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button></form>
                    </div>
                <?php endif; ?>
            </header>
            <main id="main" class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8 xl:px-10"><?= $content ?></main>
            <footer class="mx-4 border-t border-slate-200 py-5 text-xs text-slate-500 sm:mx-6 lg:mx-8 xl:mx-10">GECOPRE · Catálogo inicial conectado a MySQL. Mediciones mensuales pendientes de carga.</footer>
        </div>
    </div>
    <dialog id="app-alert" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border-0 bg-white p-0 text-slate-700 shadow-xl backdrop:bg-slate-900/40" aria-labelledby="alert-title" aria-describedby="alert-message">
        <div class="p-6">
            <div class="mb-4 flex size-11 items-center justify-center rounded-full bg-teal-50 text-xl text-teal-700"><i class="bi bi-info-circle" aria-hidden="true"></i></div>
            <h2 id="alert-title" class="text-lg font-semibold text-slate-900">Información</h2>
            <p id="alert-message" class="mt-3 text-sm leading-6"></p>
            <form method="dialog" class="mt-6 flex justify-end"><button class="button-primary">Entendido</button></form>
        </div>
    </dialog>
    <?php if ($mensaje !== null): ?><span id="flash-message" hidden data-type="<?= e($mensaje['tipo']) ?>" data-message="<?= e($mensaje['texto']) ?>"></span><?php endif; ?>
</body>

</html>