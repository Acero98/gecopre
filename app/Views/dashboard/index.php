<div class="mb-7 flex flex-wrap items-start justify-between gap-4">
    <div><p class="eyebrow">PANORAMA GENERAL</p><h1 class="page-title">Estado del servicio</h1><p class="page-description">Provincia de Ilo · Julio de 2026</p></div>
    <a href="<?= e(url('/registros')) ?>" class="button-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registro mensual</a>
</div>
<div class="mb-6 flex items-start gap-3 rounded-xl border border-teal-100 bg-teal-50/70 px-4 py-3 text-sm text-teal-900"><i class="bi bi-info-circle mt-0.5" aria-hidden="true"></i><p>Datos de referencia del Excel revisado, enero–julio de 2026. No son registros ingresados en el sistema.</p></div>
<div class="grid gap-4 md:grid-cols-3">
    <?php foreach ([['label' => 'Continuidad', 'value' => '18,92', 'unit' => 'h/día', 'icon' => 'clock', 'detail' => 'Acumulado ene–jul: 16,62 h/día'], ['label' => 'Presión', 'value' => '26,19', 'unit' => 'm.c.a.', 'icon' => 'speedometer2', 'detail' => 'Acumulado ene–jul: 24,63 m.c.a.'], ['label' => 'Conexiones activas', 'value' => '32.110', 'unit' => '', 'icon' => 'diagram-3', 'detail' => 'Ponderación de los indicadores de julio']] as $metric): ?>
    <article class="panel"><div class="flex items-center justify-between"><h2 class="text-sm font-medium text-slate-500"><?= e($metric['label']) ?></h2><i class="bi bi-<?= e($metric['icon']) ?> text-lg text-teal-700" aria-hidden="true"></i></div><p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900 tabular-nums"><?= e($metric['value']) ?> <span class="text-sm font-normal tracking-normal text-slate-500"><?= e($metric['unit']) ?></span></p><p class="mt-3 text-xs text-slate-500"><?= e($metric['detail']) ?></p></article>
    <?php endforeach; ?>
</div>
<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    <section class="panel min-w-0"><div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="section-title">Evolución mensual</h2><p id="chart-unit" class="mt-1 text-xs text-slate-500">Continuidad media · horas por día</p></div><label class="text-xs text-slate-500">Indicador<select id="chart-indicator" class="field mt-1"><option value="continuity">Continuidad</option><option value="pressure">Presión</option></select></label></div>
        <div id="history-chart" class="mt-7 flex h-48 items-end gap-2" aria-label="Continuidad mensual de enero a julio de 2026">
            <?php foreach ($months as $month): ?>
            <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2" data-bar data-continuity="<?= e((string) $month['continuity']) ?>" data-pressure="<?= e((string) $month['pressure']) ?>">
                <span data-bar-value class="text-[11px] font-medium text-slate-600 tabular-nums"><?= number_es($month['continuity']) ?></span><div data-bar-fill class="w-full max-w-10 rounded-t bg-teal-600/80" style="height:<?= number_format($month['continuity'] / 24 * 140, 2, '.', '') ?>px"></div><span class="text-xs text-slate-500"><?= e($month['short']) ?></span>
            </div>
            <?php endforeach; ?>
        </div><div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-500"><span>Fuente: matriz 2026</span><a class="font-medium text-teal-700 hover:underline" href="<?= e(url('/resultados')) ?>">Ver detalle <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
    </section>
    <section class="panel"><h2 class="section-title">Antes del siguiente registro</h2><div class="mt-5 space-y-5">
        <div class="flex gap-3"><i class="bi bi-calendar3 mt-1 text-teal-700" aria-hidden="true"></i><div><h3 class="text-sm font-semibold">Agosto sin datos en el archivo</h3><p class="mt-1 text-xs leading-5 text-slate-500">La ausencia de mediciones se mostrará como pendiente, no como cero.</p></div></div>
        <div class="flex gap-3"><i class="bi bi-bullseye mt-1 text-amber-600" aria-hidden="true"></i><div><h3 class="text-sm font-semibold">Confirmar meta de continuidad</h3><p class="mt-1 text-xs leading-5 text-slate-500">El Excel muestra 19,2 para EPS, pero utiliza 19 en la fórmula.</p></div></div>
        <div class="flex gap-3"><i class="bi bi-pin-map mt-1 text-teal-700" aria-hidden="true"></i><div><h3 class="text-sm font-semibold">Definir el plan de monitoreo</h3><p class="mt-1 text-xs leading-5 text-slate-500">36 valores mensuales por indicador. Los puntos esperados deben confirmarse.</p></div></div>
    </div></section>
</div>
<section class="mt-6 panel"><div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h2 class="section-title">Continuar en GECOPRE</h2><span class="text-xs text-slate-500">Estructura inicial de los módulos</span></div><div class="grid gap-3 sm:grid-cols-3">
    <?php foreach ([['/registros', 'pencil-square', 'Registro mensual', 'Conocer el formulario de captura'], ['/resultados', 'bar-chart-line', 'Resultados', 'Consultar el histórico de referencia'], ['/predicciones', 'graph-up-arrow', 'Predicciones', 'Revisar el alcance del módulo']] as $shortcut): ?>
    <a class="rounded-lg border border-slate-200 p-4 transition hover:border-teal-300 hover:bg-teal-50/40" href="<?= e(url($shortcut[0])) ?>"><i class="bi bi-<?= e($shortcut[1]) ?> text-lg text-teal-700" aria-hidden="true"></i><h3 class="mt-2 text-sm font-semibold"><?= e($shortcut[2]) ?></h3><p class="mt-1 text-xs leading-5 text-slate-500"><?= e($shortcut[3]) ?></p></a>
    <?php endforeach; ?>
</div></section>

