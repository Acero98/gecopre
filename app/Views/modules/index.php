<p class="eyebrow">MÓDULO</p><h1 class="page-title"><?= e($title) ?></h1><p class="page-description"><?= e($description) ?></p>
<?php if (isset($catalogo)): ?>
<section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del catálogo cargado">
    <?php foreach ([['Localidades', $catalogo['localidades']], ['Zonas geográficas', $catalogo['zonas_geograficas']], ['Sectores', $catalogo['sectores_operacionales']], ['Puntos de control', $catalogo['puntos_control']]] as [$etiqueta, $cantidad]): ?>
    <article class="panel p-4"><p class="text-xs font-medium text-slate-500"><?= e($etiqueta) ?></p><p class="mt-2 text-2xl font-semibold text-brand-800 tabular-nums"><?= e((string) $cantidad) ?></p></article>
    <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="panel mt-7 max-w-3xl"><div class="flex size-14 items-center justify-center rounded-2xl bg-teal-50 text-2xl text-teal-700"><i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i></div><h2 class="mt-5 text-lg font-semibold text-slate-900">Preparado para la siguiente etapa</h2><p class="mt-2 text-sm leading-6 text-slate-500">La navegación de este módulo ya está disponible. Sus operaciones se habilitarán al implementar la base de datos y las reglas correspondientes.</p><ul class="mt-6 space-y-4"><?php foreach ($features as $feature): ?><li class="flex gap-3 text-sm"><i class="bi bi-arrow-right text-teal-700" aria-hidden="true"></i><span><?= e($feature) ?></span></li><?php endforeach; ?></ul><a class="button-secondary mt-7" href="<?= e(url()) ?>">Volver al resumen</a></section>
