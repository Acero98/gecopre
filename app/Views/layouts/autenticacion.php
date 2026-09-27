<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · GECOPRE</title>
    <link rel="stylesheet" href="<?= e(asset('css/inter.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('icons/bootstrap-icons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>
<body class="min-h-screen bg-shell font-sans text-slate-700 antialiased">
<main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center p-4 sm:p-6">
    <section class="w-full max-w-md rounded-2xl border border-brand-100 bg-white p-6 shadow-sm sm:p-8">
        <a href="<?= e(url('/')) ?>" class="mb-8 block"><img src="<?= e(asset('images/logo-eps-ilo.png')) ?>" alt="EPS Ilo" class="h-12 w-auto"></a>
        <?= $content ?>
    </section>
</main>
<?php if ($mensaje !== null): ?>
<dialog id="app-alert" data-auto-open="true" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border-0 bg-white p-0 text-slate-700 shadow-xl backdrop:bg-slate-900/40" aria-labelledby="alert-title" aria-describedby="alert-message">
    <div class="p-6"><div class="mb-4 flex size-11 items-center justify-center rounded-full <?= $mensaje['tipo'] === 'error' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700' ?> text-xl"><i class="bi <?= $mensaje['tipo'] === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i></div><h2 id="alert-title" class="text-lg font-semibold text-slate-900"><?= $mensaje['tipo'] === 'error' ? 'No se pudo completar la acción' : 'Operación realizada' ?></h2><p id="alert-message" class="mt-3 text-sm leading-6"><?= e($mensaje['texto']) ?></p><form method="dialog" class="mt-6 flex justify-end"><button class="button-primary">Entendido</button></form></div>
</dialog>
<?php endif; ?>
</body>
</html>
