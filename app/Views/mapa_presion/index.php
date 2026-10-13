<?php
$meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$periodoActual = null;
foreach ($periodos as $periodo) { if ((int) $periodo['id'] === $periodoId) { $periodoActual = $periodo; break; } }
$esContinuidad = $indicador === 'continuidad';
$nombreIndicador = $esContinuidad ? 'Continuidad' : 'Presión';
$unidadIndicador = $esContinuidad ? 'h/día' : 'm.c.a.';
?>
<p class="eyebrow">MONITOREO TERRITORIAL</p>
<div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="page-title">Mapa de <?= e(strtolower($nombreIndicador)) ?></h1><p class="page-description">Ubica las mediciones por punto de control y detecta visualmente las variaciones del servicio en Ilo.</p></div><a href="<?= e(url('/mapa-presion/ubicaciones')) ?>" class="button-secondary"><i class="bi bi-geo-alt" aria-hidden="true"></i>Administrar ubicaciones</a></div>
<section class="panel mt-7">
    <form method="get" class="flex flex-wrap items-end gap-3"><input type="hidden" name="route" value="/mapa-presion"><label class="form-label">Indicador<select class="field mt-1 min-w-44" name="indicador" onchange="this.form.submit()"><option value="presion" <?= !$esContinuidad ? 'selected' : '' ?>>Presión</option><option value="continuidad" <?= $esContinuidad ? 'selected' : '' ?>>Continuidad</option></select></label><label class="form-label">Período<select class="field mt-1 min-w-48" name="periodo" onchange="this.form.submit()"><?php foreach ($periodos as $periodo): ?><option value="<?= (int) $periodo['id'] ?>" <?= (int) $periodo['id'] === $periodoId ? 'selected' : '' ?>><?= e($meses[(int) $periodo['mes']] . ' ' . $periodo['anio']) ?></option><?php endforeach; ?></select></label><noscript><button class="button-primary" type="submit">Actualizar</button></noscript></form>
</section>
<?php if ($puntos === []): ?><section class="panel mt-6 text-center"><i class="bi bi-map text-3xl text-brand-600" aria-hidden="true"></i><h2 class="mt-3 section-title">No hay presiones para mostrar</h2><p class="mt-2 text-sm text-slate-500">Selecciona un período con mediciones de presión.</p></section><?php else: ?>
<section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
    <div class="panel p-3 sm:p-5"><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="section-title">Ilo · <?= e($meses[(int) $periodoActual['mes']] . ' ' . $periodoActual['anio']) ?></h2><p class="mt-1 text-sm text-slate-500">Marcadores referenciales por ubicación registrada.</p></div><div class="flex flex-wrap gap-3 text-xs"><?php if ($esContinuidad): ?><span><i class="bi bi-circle-fill text-red-500" aria-hidden="true"></i> &lt; 12 h/día</span><span><i class="bi bi-circle-fill text-amber-500" aria-hidden="true"></i> 12–17 h/día</span><span><i class="bi bi-circle-fill text-emerald-600" aria-hidden="true"></i> ≥ 17 h/día</span><?php else: ?><span><i class="bi bi-circle-fill text-red-500" aria-hidden="true"></i> &lt; 20 m.c.a.</span><span><i class="bi bi-circle-fill text-amber-500" aria-hidden="true"></i> 20–28 m.c.a.</span><span><i class="bi bi-circle-fill text-emerald-600" aria-hidden="true"></i> ≥ 28 m.c.a.</span><?php endif; ?></div></div>
        <div id="mapa-presion" class="mapa-presion" data-indicador="<?= e($indicador) ?>" data-unidad="<?= e($unidadIndicador) ?>" data-punto-seleccionado="<?= (int) ($puntoId ?? 0) ?>" data-puntos='<?= e(json_encode($puntos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>' aria-label="Mapa satelital de <?= e(strtolower($nombreIndicador)) ?> por punto de control"></div>
    </div>
    <aside class="panel"><p class="eyebrow">PUNTO SELECCIONADO</p><div id="mapa-detalle" class="mt-4 text-sm text-slate-500"><i class="bi bi-cursor text-xl text-brand-600" aria-hidden="true"></i><p class="mt-3 leading-6">Selecciona un marcador para revisar su presión, dirección y relación operativa.</p></div></aside>
</section>
<?php endif; ?>
