<?php
$meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$totales = array_fill_keys(['borrador', 'en_revision', 'aprobado', 'cerrado'], 0);
foreach ($periodos as $periodo) { $totales[$periodo['estado']]++; }
$acciones = ['borrador' => [['en_revision', 'Enviar a revisión', 'bi-send-check']], 'en_revision' => [['aprobado', 'Aprobar', 'bi-check2-circle'], ['borrador', 'Devolver', 'bi-arrow-counterclockwise']], 'aprobado' => [['cerrado', 'Cerrar período', 'bi-lock'], ['borrador', 'Reabrir', 'bi-unlock']], 'cerrado' => []];
$nombresEstado = ['borrador' => 'Borrador', 'en_revision' => 'En revisión', 'aprobado' => 'Aprobado', 'cerrado' => 'Cerrado'];
?>
<p class="eyebrow">CONTROL OPERATIVO</p>
<div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="page-title">Revisión y cierre</h1><p class="page-description">Valida los datos, genera los resultados y cierra el período cuando estos hayan sido revisados.</p></div><span class="badge"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Flujo de períodos</span></div>
<section class="flow-steps mt-7" aria-label="Flujo de revisión"><div><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Borrador</span></div><i class="bi bi-arrow-right" aria-hidden="true"></i><div><i class="bi bi-search" aria-hidden="true"></i><span>En revisión</span></div><i class="bi bi-arrow-right" aria-hidden="true"></i><div><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Aprobado</span></div><i class="bi bi-arrow-right" aria-hidden="true"></i><div><i class="bi bi-calculator" aria-hidden="true"></i><span>Calcular y revisar</span></div><i class="bi bi-arrow-right" aria-hidden="true"></i><div><i class="bi bi-lock" aria-hidden="true"></i><span>Cerrado</span></div></section>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article class="metric-card"><div class="metric-heading"><p class="metric-label">Borradores</p><span class="metric-icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span></div><p class="metric-value"><?= $totales['borrador'] ?></p><p class="metric-help">Pendientes de envío</p></article><article class="metric-card"><div class="metric-heading"><p class="metric-label">En revisión</p><span class="metric-icon"><i class="bi bi-search" aria-hidden="true"></i></span></div><p class="metric-value"><?= $totales['en_revision'] ?></p><p class="metric-help">Pendientes de decisión</p></article><article class="metric-card"><div class="metric-heading"><p class="metric-label">Aprobados</p><span class="metric-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span></div><p class="metric-value"><?= $totales['aprobado'] ?></p><p class="metric-help">Pendientes de cálculo o cierre</p></article><article class="metric-card"><div class="metric-heading"><p class="metric-label">Cerrados</p><span class="metric-icon"><i class="bi bi-lock" aria-hidden="true"></i></span></div><p class="metric-value"><?= $totales['cerrado'] ?></p><p class="metric-help">Resultados definitivos</p></article></div>

<section class="panel mt-6">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="section-title">Períodos para revisión</h2>
            <p class="mt-1 text-sm text-slate-500">Revisa la cobertura, los resultados calculados y ejecuta la acción que corresponde a cada período.</p>
        </div>
        <span class="badge"><i class="bi bi-calendar3" aria-hidden="true"></i><?= number_format(count($periodos)) ?> períodos</span>
    </div>
    <div>
        <table class="data-table min-w-[1280px]" data-search-placeholder="Buscar período o estado...">
            <caption class="sr-only">Períodos disponibles para revisión y cierre</caption>
            <thead>
                <tr>
                    <th scope="col">Período</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-right">Registros</th>
                    <th scope="col" class="text-right">Zonas con conexiones</th>
                    <th scope="col" class="text-right">Conexiones activas</th>
                    <th scope="col" class="text-right">Continuidad</th>
                    <th scope="col" class="text-right">Presión</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periodos as $periodo):
                    $tieneResultados = $periodo['resultado_continuidad'] !== null && $periodo['resultado_presion'] !== null;
                    $estado = $periodo['estado'];
                ?>
                    <tr>
                        <th scope="row" class="font-semibold text-slate-900"><?= e($meses[(int) $periodo['mes']] . ' ' . $periodo['anio']) ?></th>
                        <td><span class="status-chip estado-<?= e($estado) ?>"><?= e($nombresEstado[$estado]) ?></span></td>
                        <td class="text-right tabular-nums"><span class="font-semibold text-slate-900"><?= number_format((int) $periodo['continuidad']) ?></span><span class="text-slate-400"> / </span><span class="font-semibold text-slate-900"><?= number_format((int) $periodo['presion']) ?></span><span class="ml-1 text-xs text-slate-500">C / P</span></td>
                        <td class="text-right font-semibold tabular-nums text-slate-900"><?= number_format((int) $periodo['zonas_con_conexiones']) ?></td>
                        <td class="text-right font-semibold tabular-nums text-slate-900"><?= number_format((int) $periodo['conexiones']) ?></td>
                        <td class="text-right font-semibold tabular-nums text-slate-900"><?= $periodo['resultado_continuidad'] === null ? '—' : number_es((float) $periodo['resultado_continuidad']) . ' h/día' ?></td>
                        <td class="text-right font-semibold tabular-nums text-slate-900"><?= $periodo['resultado_presion'] === null ? '—' : number_es((float) $periodo['resultado_presion']) . ' m.c.a.' ?></td>
                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="<?= e(url('/revision/detalle') . '&periodo=' . (int) $periodo['id']) ?>" class="button-secondary"><i class="bi bi-eye" aria-hidden="true"></i>Revisar</a>
                                <?php if ($estado === 'aprobado' && !$tieneResultados): ?>
                                    <a href="<?= e(url('/resultados')) ?>" class="button-primary"><i class="bi bi-calculator" aria-hidden="true"></i>Calcular</a>
                                <?php endif; ?>
                                <?php foreach ($acciones[$estado] as [$estadoDestino, $etiqueta, $icono]): ?>
                                    <?php if ($estadoDestino === 'cerrado' && !$tieneResultados) { continue; } ?>
                                    <form method="post" action="<?= e(url('/revision')) ?>" class="form-cambio-estado">
                                        <input type="hidden" name="token" value="<?= e($tokenFormulario) ?>">
                                        <input type="hidden" name="periodo_id" value="<?= (int) $periodo['id'] ?>">
                                        <input type="hidden" name="estado" value="<?= e($estadoDestino) ?>">
                                        <input type="hidden" name="motivo" value="">
                                        <button class="<?= $estadoDestino === 'borrador' ? 'button-secondary' : 'button-primary' ?>" type="submit" data-requiere-motivo="<?= $estadoDestino === 'borrador' ? 'true' : 'false' ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i><?= e($etiqueta) ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
