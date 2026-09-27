<p class="eyebrow">CONFIGURACIÓN INICIAL</p>
<h1 class="page-title">Crear administrador</h1>
<p class="page-description">Este primer usuario administrará GECOPRE, sus usuarios y sus catálogos.</p>
<form method="post" action="<?= e(url('/instalacion')) ?>" class="mt-7 grid gap-5 sm:grid-cols-2">
    <input type="hidden" name="token" value="<?= e($tokenFormulario) ?>">
    <label class="form-label">Nombres<input class="field" name="nombres" autocomplete="given-name" required></label>
    <label class="form-label">Apellidos<input class="field" name="apellidos" autocomplete="family-name" required></label>
    <label class="form-label sm:col-span-2">Correo electrónico<input class="field" type="email" name="correo" autocomplete="email" required></label>
    <label class="form-label sm:col-span-2">Contraseña<input class="field" type="password" name="contrasena" autocomplete="new-password" minlength="10" required><span class="text-xs font-normal text-slate-500">Mínimo 10 caracteres.</span></label>
    <label class="form-label sm:col-span-2">Confirmar contraseña<input class="field" type="password" name="confirmacion_contrasena" autocomplete="new-password" minlength="10" required></label>
    <button class="button-primary sm:col-span-2" type="submit"><i class="bi bi-person-check" aria-hidden="true"></i> Crear administrador</button>
</form>
