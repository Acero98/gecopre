<?php
$meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
$serieMensual = [];
foreach ($serie as $fila) { $clave = $fila['anio'] . '-' . $fila['mes']; $serieMensual[$clave]['anio'] = $fila['anio']; $serieMensual[$clave]['mes'] = $fila['mes']; $serieMensual[$clave][$fila['indicador']] = (float) $fila['valor']; }
$serieMensual = array_values($serieMensual);
$ultimo = $serieMensual === [] ? null : $serieMensual[count($serieMensual) - 1];
$dibujarLineaSeguimiento = static function (array $serie, string $indicador, string $unidad, string $color) use ($meses): string {
    $puntos = [];
    foreach ($serie as $fila) {
        if (array_key_exists($indicador, $fila)) {
            $puntos[] = ['mes' => (int) $fila['mes'], 'valor' => (float) $fila[$indicador]];
        }
    }
    if ($puntos === []) {
        return '';
    }

    $valores = array_column($puntos, 'valor');
    $minimo = min($valores);
    $maximo = max($valores);
    $rango = max(1.0, $maximo - $minimo);
    $minimoEje = max(0.0, $minimo - ($rango * 0.25));
    $maximoEje = $maximo + ($rango * 0.25);
    $altoUtil = 104.0;
    $izquierda = 42.0;
    $derecha = 620.0;
    $base = 140.0;
    $cantidad = count($puntos);
    $coordenadas = [];
    foreach ($puntos as $indice => $punto) {
        $x = $cantidad === 1 ? ($izquierda + $derecha) / 2 : $izquierda + (($derecha - $izquierda) * $indice / ($cantidad - 1));
        $y = $base - (($punto['valor'] - $minimoEje) / ($maximoEje - $minimoEje) * $altoUtil);
        $coordenadas[] = ['x' => $x, 'y' => $y, 'mes' => $punto['mes'], 'valor' => $punto['valor']];
    }
    ob_start();
    ?>
    <svg class="h-52 w-full overflow-visible" viewBox="0 0 660 190" role="img" aria-label="Seguimiento mensual de <?= e(strtolower($indicador)) ?> en <?= e($unidad) ?>">
        <line x1="42" y1="36" x2="620" y2="36" stroke="#e2e8f0" stroke-width="1" />
        <line x1="42" y1="88" x2="620" y2="88" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3 4" />
        <line x1="42" y1="140" x2="620" y2="140" stroke="#cbd5e1" stroke-width="1" />
        <text x="0" y="40" fill="#64748b" font-size="10"><?= number_es($maximoEje) ?></text>
        <text x="0" y="144" fill="#64748b" font-size="10"><?= number_es($minimoEje) ?></text>
        <polyline points="<?= e(implode(' ', array_map(static fn (array $punto): string => number_format($punto['x'], 2, '.', '') . ',' . number_format($punto['y'], 2, '.', ''), $coordenadas))) ?>" fill="none" stroke="<?= e($color) ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <?php foreach ($coordenadas as $punto): ?>
            <circle cx="<?= number_format($punto['x'], 2, '.', '') ?>" cy="<?= number_format($punto['y'], 2, '.', '') ?>" r="4.5" fill="#fff" stroke="<?= e($color) ?>" stroke-width="3" />
            <text x="<?= number_format($punto['x'], 2, '.', '') ?>" y="<?= number_format(max(20, $punto['y'] - 12), 2, '.', '') ?>" text-anchor="middle" fill="#334155" font-size="11" font-weight="600"><?= number_es($punto['valor']) ?></text>
            <text x="<?= number_format($punto['x'], 2, '.', '') ?>" y="170" text-anchor="middle" fill="#64748b" font-size="11"><?= e($meses[$punto['mes']]) ?></text>
        <?php endforeach; ?>
    </svg>
    <?php
    return (string) ob_get_clean();
};
?>
<div class="mb-7 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">PANORAMA GENERAL</p><h1 class="page-title">Estado del servicio</h1><p class="page-description">Seguimiento operativo de continuidad, presión y conexiones activas.</p></div><a href="<?= e(url('/registros')) ?>" class="button-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registro mensual</a></div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article class="metric-card"><div class="metric-heading"><p class="metric-label">En revisión</p><span class="metric-icon"><i class="bi bi-search" aria-hidden="true"></i></span></div><p class="metric-value"><?= number_format((int) $operacion['en_revision']) ?></p><p class="metric-help">Períodos pendientes de decisión</p></article><article class="metric-card"><div class="metric-heading"><p class="metric-label">Aprobados</p><span class="metric-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><p class="metric-value"><?= number_format((int) $operacion['aprobados']) ?></p><p class="metric-help">Pendientes de cálculo o cierre</p></article><article class="metric-card"><div class="metric-heading"><p class="metric-label">Cerrados</p><span class="metric-icon"><i class="bi bi-lock" aria-hidden="true"></i></span></div><p class="metric-value"><?= number_format((int) $operacion['cerrados']) ?></p><p class="metric-help">Períodos definitivos</p></article><article class="metric-card"><div class="metric-heading"><p class="metric-label">Mediciones cargadas</p><span class="metric-icon"><i class="bi bi-clipboard-data" aria-hidden="true"></i></span></div><p class="metric-value"><?= number_format((int) $mediciones['total']) ?></p><p class="metric-help">Registros por punto e indicador</p></article></div>

<?php if ($ultimo === null): ?><section class="panel mt-6"><div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="section-title">Aún no hay resultados oficiales</h2><p class="mt-1 text-sm leading-6 text-slate-500">Los datos están cargados, pero deben revisarse, aprobarse y calcularse antes de aparecer como indicadores oficiales.</p></div><a href="<?= e(url('/revision')) ?>" class="button-primary"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Revisar períodos</a></div></section><?php else: ?><section class="mt-6 grid gap-4 md:grid-cols-3"><article class="panel"><div class="flex items-center justify-between"><h2 class="text-sm font-medium text-slate-500">Continuidad oficial</h2><i class="bi bi-clock-history text-lg text-brand-600" aria-hidden="true"></i></div><p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900 tabular-nums"><?= number_es((float) ($ultimo['continuidad'] ?? 0)) ?> <span class="text-sm font-normal text-slate-500">h/día</span></p><p class="mt-3 text-xs text-slate-500">Último resultado calculado</p></article><article class="panel"><div class="flex items-center justify-between"><h2 class="text-sm font-medium text-slate-500">Presión oficial</h2><i class="bi bi-speedometer2 text-lg text-brand-600" aria-hidden="true"></i></div><p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900 tabular-nums"><?= number_es((float) ($ultimo['presion'] ?? 0)) ?> <span class="text-sm font-normal text-slate-500">m.c.a.</span></p><p class="mt-3 text-xs text-slate-500">Último resultado calculado</p></article><article class="panel"><div class="flex items-center justify-between"><h2 class="text-sm font-medium text-slate-500">Último período oficial</h2><i class="bi bi-calendar-check text-lg text-brand-600" aria-hidden="true"></i></div><p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900"><?= e($meses[(int) $ultimo['mes']] . ' ' . $ultimo['anio']) ?></p><p class="mt-3 text-xs text-slate-500">Disponible para consulta y predicción</p></article></section>
<section class="mt-6 grid gap-6 xl:grid-cols-2">
    <article class="panel">
        <div class="flex items-start justify-between gap-4"><div><h2 class="section-title">Seguimiento de continuidad</h2><p class="mt-1 text-xs text-slate-500">Resultado oficial mensual · horas por día</p></div><i class="bi bi-activity text-xl text-brand-600" aria-hidden="true"></i></div>
        <div class="mt-5"><?= $dibujarLineaSeguimiento($serieMensual, 'continuidad', 'h/día', '#1768bb') ?></div>
    </article>
    <article class="panel">
        <div class="flex items-start justify-between gap-4"><div><h2 class="section-title">Seguimiento de presión</h2><p class="mt-1 text-xs text-slate-500">Resultado oficial mensual · m.c.a.</p></div><i class="bi bi-activity text-xl text-slate-600" aria-hidden="true"></i></div>
        <div class="mt-5"><?= $dibujarLineaSeguimiento($serieMensual, 'presion', 'm.c.a.', '#475569') ?></div>
    </article>
</section>
<div class="mt-4 flex items-center justify-end text-xs text-slate-500"><a class="font-medium text-brand-700 hover:underline" href="<?= e(url('/resultados')) ?>">Ver resultados <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
<?php endif; ?>

<section class="mt-6 panel"><div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h2 class="section-title">Continuar en GECOPRE</h2><p class="mt-1 text-sm text-slate-500">Sigue el flujo según el estado de los períodos.</p></div></div><div class="grid gap-3 sm:grid-cols-3"><a class="rounded-lg border border-slate-200 bg-white p-4 transition hover:border-brand-600 hover:bg-slate-50" href="<?= e(url('/revision')) ?>"><i class="bi bi-clipboard-check text-lg text-brand-600" aria-hidden="true"></i><h3 class="mt-2 text-sm font-semibold">Revisión y cierre</h3><p class="mt-1 text-xs leading-5 text-slate-500">Valida datos y aprueba períodos.</p></a><a class="rounded-lg border border-slate-200 bg-white p-4 transition hover:border-brand-600 hover:bg-slate-50" href="<?= e(url('/resultados')) ?>"><i class="bi bi-bar-chart-line text-lg text-brand-600" aria-hidden="true"></i><h3 class="mt-2 text-sm font-semibold">Resultados</h3><p class="mt-1 text-xs leading-5 text-slate-500">Calcula y revisa indicadores oficiales.</p></a><a class="rounded-lg border border-slate-200 bg-white p-4 transition hover:border-brand-600 hover:bg-slate-50" href="<?= e(url('/predicciones')) ?>"><i class="bi bi-graph-up-arrow text-lg text-brand-600" aria-hidden="true"></i><h3 class="mt-2 text-sm font-semibold">Predicciones</h3><p class="mt-1 text-xs leading-5 text-slate-500">Consulta la estimación a uno y dos meses.</p></a></div></section>
