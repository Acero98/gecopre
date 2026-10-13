<?php
$meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
?>
<p class="eyebrow">RESULTADO OFICIAL</p>
<div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="page-title">Resultados mensuales</h1><p class="page-description">Los resultados se calculan por zona y se ponderan según sus conexiones activas.</p></div><span class="badge"><i class="bi bi-calculator" aria-hidden="true"></i> Regla vigente</span></div>

<section class="panel mt-7"><div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="section-title">Generar cálculo</h2><p class="mt-1 text-sm text-slate-500">Solo se muestran períodos aprobados. Cada ejecución crea una nueva versión trazable antes del cierre.</p></div><?php if ($periodosDisponibles === []): ?><span class="text-sm text-slate-500">Aún no hay períodos aprobados.</span><?php else: ?><form method="post" action="<?= e(url('/resultados/calcular')) ?>" class="flex flex-wrap items-center gap-3"><input type="hidden" name="token" value="<?= e($tokenFormulario) ?>"><label class="sr-only" for="periodo-calculo">Período</label><select id="periodo-calculo" name="periodo_id" class="field w-auto" required><?php foreach ($periodosDisponibles as $periodo): ?><option value="<?= (int) $periodo['id'] ?>"><?= e($meses[(int) $periodo['mes']] . ' ' . $periodo['anio']) ?><?= (int) $periodo['versiones'] > 0 ? ' · recalcular' : '' ?></option><?php endforeach; ?></select><button type="submit" class="button-primary"><i class="bi bi-play-circle" aria-hidden="true"></i>Calcular</button></form><?php endif; ?></div></section>

<?php
$indicadores = [
    ['codigo' => 'continuidad', 'nombre' => 'CONTINUIDAD', 'unidad' => 'h/día', 'datosLocalidad' => $continuidadLocalidad, 'datosZona' => $continuidadZonasGeograficas, 'metas' => $metasContinuidad, 'icono' => 'bi-clock-history'],
    ['codigo' => 'presion', 'nombre' => 'PRESIÓN', 'unidad' => 'm.c.a.', 'datosLocalidad' => $presionLocalidad, 'datosZona' => $presionZonasGeograficas, 'metas' => $metasPresion, 'icono' => 'bi-speedometer2'],
];
?>
<?php if ($indicadorSeleccionado === null): ?>
    <section class="panel mt-6">
        <div><h2 class="section-title">Consultar resultados por indicador</h2><p class="mt-1 text-sm leading-6 text-slate-500">Selecciona Continuidad o Presión en el menú lateral para consultar sus resultados mensuales, acumulados e información por zona geográfica.</p></div>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <a href="<?= e(url('/resultados') . '&indicador=continuidad') ?>" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-600 hover:bg-slate-50"><i class="bi bi-clock-history text-2xl text-brand-600" aria-hidden="true"></i><h3 class="mt-3 text-base font-semibold text-slate-900">Continuidad</h3><p class="mt-1 text-sm leading-6 text-slate-500">Resultados de localidad, zonas geográficas, metas e ICI.</p><span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-700">Ver continuidad <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
            <a href="<?= e(url('/resultados') . '&indicador=presion') ?>" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-600 hover:bg-slate-50"><i class="bi bi-speedometer2 text-2xl text-brand-600" aria-hidden="true"></i><h3 class="mt-3 text-base font-semibold text-slate-900">Presión</h3><p class="mt-1 text-sm leading-6 text-slate-500">Resultados de localidad y zonas geográficas con sus promedios acumulados.</p><span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-700">Ver presión <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
        </div>
    </section>
<?php elseif (($indicadorSeleccionado === 'continuidad' && $continuidadLocalidad === []) || ($indicadorSeleccionado === 'presion' && $presionLocalidad === [])): ?>
    <section class="panel mt-6 text-center"><i class="bi bi-bar-chart-line text-3xl text-brand-600" aria-hidden="true"></i><h2 class="mt-3 section-title">Sin resultados oficiales todavía</h2><p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">Aprueba un período desde Revisión y cierre. Luego podrás generar los resultados de <?= e($indicadorSeleccionado) ?>.</p></section>
<?php else: ?>
    <?php foreach ($indicadores as $indicador): ?>
        <?php if (($indicadorSeleccionado !== null && $indicadorSeleccionado !== $indicador['codigo']) || ($indicador['datosLocalidad'] === [] && $indicador['datosZona'] === [])) { continue; } ?>
        <section class="mt-9" id="<?= e(strtolower($indicador['nombre'])) ?>">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-4"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><i class="bi <?= e($indicador['icono']) ?>" aria-hidden="true"></i></span><div><p class="eyebrow"><?= e($indicador['nombre']) ?></p><h2 class="section-title">Resultados de <?= e(strtolower($indicador['nombre'])) ?></h2></div></div>
            <?php if ($indicador['datosLocalidad'] !== []): ?>
            <section class="panel mt-5">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div><h3 class="section-title">Evolución mensual a nivel de localidad</h3><p class="mt-1 text-sm text-slate-500">Compara el resultado de cada mes con su acumulado anual y los ICI correspondientes.</p></div>
                <div class="flex flex-wrap gap-2"><span class="badge"><i class="bi bi-bullseye" aria-hidden="true"></i>Meta SUNASS: <?= number_es($indicador['metas']['sunass']) ?> <?= e($indicador['unidad']) ?></span><span class="badge"><i class="bi bi-building" aria-hidden="true"></i>Meta EPS Ilo: <?= number_es($indicador['metas']['eps']) ?> <?= e($indicador['unidad']) ?></span></div>
            </div>
            <div><table class="data-table min-w-[1120px]" data-search-placeholder="Buscar período..."><caption class="sr-only"><?= e($indicador['nombre']) ?> mensual, acumulada e ICI a nivel de localidad</caption><thead><tr><th scope="col">Período</th><th scope="col" class="text-right"><?= e(ucfirst(strtolower($indicador['nombre']))) ?> del mes</th><th scope="col" class="text-right">Conexiones al cierre</th><th scope="col" class="text-right">Promedio acumulado del año</th><th scope="col" class="text-right">ICI localidad</th><th scope="col" class="text-right">ICI EPS</th><th scope="col" class="text-right">Cobertura</th><th scope="col">Estado</th><th scope="col" class="text-right">Acción</th></tr></thead><tbody>
                <?php foreach ($indicador['datosLocalidad'] as $fila): $iciLocalidad = min(1, (float) $fila['promedio_acumulado'] / $indicador['metas']['sunass']); $iciEps = min(1, (float) $fila['promedio_acumulado'] / $indicador['metas']['eps']); ?>
                    <tr><th scope="row" class="font-semibold text-slate-900"><?= e($meses[(int) $fila['mes']] . ' ' . $fila['anio']) ?></th><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['valor']) ?> <?= e($indicador['unidad']) ?></td><td class="text-right tabular-nums"><?= number_format((int) $fila['conexiones_utilizadas']) ?></td><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['promedio_acumulado']) ?> <?= e($indicador['unidad']) ?> <span class="block text-xs font-normal text-slate-500">ene–<?= e(strtolower($meses[(int) $fila['mes']])) ?></span></td><td class="text-right font-semibold tabular-nums"><?= number_es($iciLocalidad * 100) ?>%</td><td class="text-right font-semibold tabular-nums"><?= number_es($iciEps * 100) ?>%</td><td class="text-right tabular-nums"><?= (int) $fila['cobertura_obtenida'] ?> / <?= (int) $fila['cobertura_esperada'] ?> zonas</td><td><span class="status-chip estado-<?= e($fila['estado']) ?>"><?= e($fila['estado'] === 'cerrado' ? 'Cerrado' : 'Aprobado') ?></span></td><td class="text-right"><a href="<?= e(url('/resultados/detalle') . '&periodo=' . (int) $fila['periodo_id']) ?>" class="button-secondary"><i class="bi bi-eye" aria-hidden="true"></i>Ver detalle</a></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
            </section>
            <?php endif; ?>
            <?php if ($indicador['datosZona'] !== []): ?>
            <section class="panel mt-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div><h3 class="section-title">Evolución mensual por zona</h3><p class="mt-1 text-sm text-slate-500">Muestra el promedio mensual y acumulado de Zona Puerto–Pacocha y Zona Pampa Inalámbrica, ponderados por sus conexiones activas.</p></div>
            <span class="badge"><i class="bi bi-geo-alt" aria-hidden="true"></i><?= number_format(count(array_unique(array_column($indicador['datosZona'], 'zona_geografica_id')))) ?> zonas</span>
        </div>
        <div><table class="data-table min-w-[1080px]" data-search-placeholder="Buscar período o zona..."><caption class="sr-only">Evolución mensual de <?= e(strtolower($indicador['nombre'])) ?> por zona geográfica</caption><thead><tr><th scope="col">Período</th><th scope="col">Zona</th><th scope="col" class="text-right"><?= e(ucfirst(strtolower($indicador['nombre']))) ?> del mes</th><th scope="col" class="text-right">Conexiones al cierre</th><th scope="col" class="text-right">Promedio acumulado del año</th><th scope="col" class="text-right">Cobertura</th><th scope="col">Estado</th><th scope="col" class="text-right">Acción</th></tr></thead><tbody>
            <?php foreach ($indicador['datosZona'] as $fila): ?>
                <tr><th scope="row" class="font-semibold text-slate-900"><?= e($meses[(int) $fila['mes']] . ' ' . $fila['anio']) ?></th><td class="font-medium text-slate-900"><?= e($fila['zona_geografica']) ?></td><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['valor']) ?> <?= e($indicador['unidad']) ?></td><td class="text-right tabular-nums"><?= number_format((int) $fila['conexiones_utilizadas']) ?></td><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['promedio_acumulado']) ?> <?= e($indicador['unidad']) ?> <span class="block text-xs font-normal text-slate-500">ene–<?= e(strtolower($meses[(int) $fila['mes']])) ?></span></td><td class="text-right tabular-nums"><?= (int) $fila['cobertura_obtenida'] ?> / <?= (int) $fila['cobertura_esperada'] ?></td><td><span class="status-chip estado-<?= e($fila['estado']) ?>"><?= e($fila['estado'] === 'cerrado' ? 'Cerrado' : 'Aprobado') ?></span></td><td class="text-right"><a href="<?= e(url('/resultados/detalle') . '&periodo=' . (int) $fila['periodo_id']) ?>" class="button-secondary"><i class="bi bi-eye" aria-hidden="true"></i>Ver detalle</a></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </section>
            <?php endif; ?>
            <?php if ($indicador['codigo'] === 'presion' && $presionSectoresFuentes !== []): ?>
            <section class="panel mt-6">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                    <div><h3 class="section-title">Evolución mensual por sector y fuente de abastecimiento</h3><p class="mt-1 text-sm text-slate-500">Relaciona cada sector operacional con su reservorio y muestra los promedios acumulados de ambos niveles.</p></div>
                    <span class="badge"><i class="bi bi-moisture" aria-hidden="true"></i><?= number_format(count(array_unique(array_column($presionSectoresFuentes, 'sector_id')))) ?> sectores</span>
                </div>
                <div><table class="data-table min-w-[1280px]" data-search-placeholder="Buscar período, fuente o sector..."><caption class="sr-only">Evolución mensual de presión por sector operacional y fuente de abastecimiento</caption><thead><tr><th scope="col">Período</th><th scope="col">Fuente de abastecimiento</th><th scope="col">Sector operacional</th><th scope="col" class="text-right">Presión promedio del mes</th><th scope="col" class="text-right">Conexiones al cierre</th><th scope="col" class="text-right">Promedio acumulado del sector</th><th scope="col" class="text-right">Promedio acumulado de la fuente</th><th scope="col" class="text-right">Cobertura</th><th scope="col">Estado</th><th scope="col" class="text-right">Acción</th></tr></thead><tbody>
                    <?php foreach ($presionSectoresFuentes as $fila): ?>
                        <tr><th scope="row" class="font-semibold text-slate-900"><?= e($meses[(int) $fila['mes']] . ' ' . $fila['anio']) ?></th><td class="font-medium text-slate-900"><?= e($fila['fuente_abastecimiento']) ?></td><td><?= e($fila['sector_operacional']) ?></td><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['valor']) ?> m.c.a.</td><td class="text-right tabular-nums"><?= number_format((int) $fila['conexiones_utilizadas']) ?></td><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['promedio_sector_acumulado']) ?> m.c.a.</td><td class="text-right font-semibold tabular-nums text-slate-900"><?= number_es((float) $fila['promedio_fuente_acumulado']) ?> m.c.a.</td><td class="text-right tabular-nums"><?= (int) $fila['cobertura_obtenida'] ?> / <?= (int) $fila['cobertura_esperada'] ?></td><td><span class="status-chip estado-<?= e($fila['estado']) ?>"><?= e($fila['estado'] === 'cerrado' ? 'Cerrado' : 'Aprobado') ?></span></td><td class="text-right"><a href="<?= e(url('/resultados/detalle') . '&periodo=' . (int) $fila['periodo_id']) ?>" class="button-secondary"><i class="bi bi-eye" aria-hidden="true"></i>Ver detalle</a></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            </section>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
