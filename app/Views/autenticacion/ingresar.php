<p class="eyebrow">ACCESO AL SISTEMA</p>
<h1 class="page-title">Ingresar a GECOPRE</h1>
<p class="page-description">Continuidad y presión en las redes de distribución.</p>
<form method="post" action="<?= e(url('/ingresar')) ?>" class="mt-7 space-y-5">
    <input type="hidden" name="token" value="<?= e($tokenFormulario) ?>">
    <label class="form-label">Correo electrónico<input class="field" type="email" name="correo" autocomplete="email" required></label>
    <label class="form-label">Contraseña<input class="field" type="password" name="contrasena" autocomplete="current-password" required></label>
    <button class="button-primary w-full" type="submit"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Ingresar</button>
</form>
