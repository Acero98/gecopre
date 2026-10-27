<p class="eyebrow">ADMINISTRACIÓN DEL MAPA</p>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="page-title">Ubicaciones de puntos de control</h1>
        <p class="page-description">Corrige las coordenadas referenciales para mejorar la ubicación del punto en el mapa.</p>
    </div>
    <a href="<?= e(url('/mapa-presion')) ?>" class="button-secondary"><i class="bi bi-map" aria-hidden="true"></i>Ver mapa</a>
</div>

<section class="panel mt-5 p-4 sm:p-4">
    <form id="form-importar-ubicaciones" method="post" action="<?= e(url('/mapa-presion/ubicaciones/importar')) ?>" enctype="multipart/form-data" class="grid items-end gap-3 lg:grid-cols-[minmax(14rem,auto)_auto_minmax(20rem,1fr)_auto]">
        <input type="hidden" name="token" value="<?= e($tokenFormulario) ?>">
        <div>
            <h2 class="section-title">Importar ubicaciones</h2>
            <p class="mt-0.5 text-xs text-slate-500">Descarga, edita y carga la plantilla Excel.</p>
        </div>
        <a href="<?= e(url('/mapa-presion/ubicaciones/plantilla')) ?>" class="button-secondary"><i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>Plantilla</a>
        <label class="form-label text-sm"><span class="sr-only">Archivo Excel (.xlsx)</span>
            <input class="field" type="file" name="archivo_ubicaciones" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" aria-label="Archivo Excel de ubicaciones" required>
        </label>
        <button id="boton-importar-ubicaciones" class="button-primary" type="submit"><i class="bi bi-upload" aria-hidden="true"></i><span>Importar ubicaciones</span></button>
    </form>
</section>

<section class="panel mt-6">
    <div class="mb-5">
        <h2 class="section-title">Coordenadas</h2>
        <p class="mt-1 text-sm text-slate-500">Al guardar una coordenada, el punto deja de mostrarse como referencial.</p>
    </div>
    <div>
        <table class="data-table min-w-[1180px]" data-search-placeholder="Buscar código, dirección, zona o sector...">
            <thead><tr><th>Código</th><th>Dirección</th><th>Zona geográfica</th><th>Sector / zona</th><th>Origen</th><th class="text-right">Latitud</th><th class="text-right">Longitud</th><th class="text-right">Acción</th></tr></thead>
            <tbody><?php foreach ($puntos as $punto): ?><tr>
                <th scope="row" class="font-semibold text-slate-900"><?= e($punto['codigo']) ?></th>
                <td><?= e($punto['direccion']) ?></td>
                <td class="font-medium text-slate-900"><?= e($punto['zona_geografica']) ?></td>
                <td><?= e($punto['sector'] . ' · ' . $punto['zona_presion']) ?></td>
                <td><span class="status-chip <?= (int) $punto['coordenadas_referenciales'] === 1 ? 'estado-en_revision' : 'estado-aprobado' ?>"><?= (int) $punto['coordenadas_referenciales'] === 1 ? 'Referencial' : 'Validada' ?></span></td>
                <td class="text-right tabular-nums"><?= number_format((float) $punto['latitud'], 7, '.', '') ?></td>
                <td class="text-right tabular-nums"><?= number_format((float) $punto['longitud'], 7, '.', '') ?></td>
                <td class="text-right"><details class="inline-block text-left"><summary class="button-secondary cursor-pointer list-none"><i class="bi bi-pencil" aria-hidden="true"></i>Editar</summary><form method="post" action="<?= e(url('/mapa-presion/ubicaciones')) ?>" enctype="multipart/form-data" class="form-guardar-ubicacion mt-3 grid min-w-72 gap-3 rounded-lg border border-slate-200 bg-white p-3 shadow-lg"><input type="hidden" name="token" value="<?= e($tokenFormulario) ?>"><input type="hidden" name="punto_id" value="<?= (int) $punto['id'] ?>"><label class="form-label text-xs">Latitud<input class="field mt-1" type="number" name="latitud" step="0.0000001" value="<?= number_format((float) $punto['latitud'], 7, '.', '') ?>" required></label><label class="form-label text-xs">Longitud<input class="field mt-1" type="number" name="longitud" step="0.0000001" value="<?= number_format((float) $punto['longitud'], 7, '.', '') ?>" required></label><label class="form-label text-xs">Foto de referencia <span class="font-normal text-slate-400">JPG, PNG o WebP · máx. 5 MB</span><input class="field mt-1 !min-h-10 !py-2" type="file" name="imagen_referencia" accept="image/jpeg,image/png,image/webp" data-imagen-referencia><span class="mt-1 block text-xs text-slate-500" data-nombre-imagen>Sin foto nueva seleccionada</span><?php if (!empty($punto['imagen_referencia'])): ?><img class="mt-2 h-16 w-full rounded-md border border-slate-200 object-cover" src="<?= e(base_url() . '/uploads/referencias/' . rawurlencode(basename($punto['imagen_referencia']))) ?>" alt="Foto de referencia actual de <?= e($punto['codigo']) ?>"><?php endif; ?></label><button class="button-primary justify-center" type="submit"><span>Guardar ubicación</span></button></form></details></td>
            </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section>
