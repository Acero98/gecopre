'use strict';

const menuButton = document.getElementById('menu-toggle');
const navigation = document.getElementById('main-navigation');
const smallScreen = window.matchMedia('(max-width: 1023px)');
const sidebarButton = document.getElementById('sidebar-collapse');
const sidebarPreference = 'gecopre-menu-contraido';
function updateMenu() {
    navigation.hidden = smallScreen.matches;
    menuButton.setAttribute('aria-expanded', String(!navigation.hidden));
}
if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
        navigation.hidden = !navigation.hidden;
        menuButton.setAttribute('aria-expanded', String(!navigation.hidden));
    });
    smallScreen.addEventListener('change', updateMenu);
    updateMenu();
}

function setSidebarCollapsed(collapsed) {
    document.body.classList.toggle('nav-collapsed', collapsed);
    sidebarButton.setAttribute('aria-pressed', String(collapsed));
    sidebarButton.setAttribute('aria-label', collapsed ? 'Expandir menú con etiquetas' : 'Contraer menú a iconos');
    sidebarButton.dataset.tooltip = collapsed ? 'Expandir menú' : 'Contraer menú';
    sidebarButton.querySelector('i').className = `bi ${collapsed ? 'bi-layout-sidebar' : 'bi-layout-sidebar-inset'} text-lg`;
}
if (sidebarButton) {
    try {
        setSidebarCollapsed(localStorage.getItem(sidebarPreference) === 'true');
    } catch {
        setSidebarCollapsed(false);
    }
    sidebarButton.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains('nav-collapsed');
        setSidebarCollapsed(collapsed);
        try { localStorage.setItem(sidebarPreference, String(collapsed)); } catch { /* Keep this interaction available if storage is blocked. */ }
    });
}

const indicator = document.getElementById('chart-indicator');
if (indicator) {
    indicator.addEventListener('change', () => {
        const pressure = indicator.value === 'pressure';
        const unit = pressure ? 'm.c.a.' : 'horas por día';
        document.getElementById('chart-unit').textContent = `${pressure ? 'Presión' : 'Continuidad'} media · ${unit}`;
        const chart = document.getElementById('history-chart');
        chart.setAttribute('aria-label', `${pressure ? 'Presión' : 'Continuidad'} mensual de enero a julio de 2026`);
        chart.querySelectorAll('[data-bar]').forEach(bar => {
            const value = Number(bar.dataset[indicator.value]);
            bar.querySelector('[data-bar-value]').textContent = value.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            bar.querySelector('[data-bar-fill]').style.height = `${value / (pressure ? 30 : 24) * 140}px`;
        });
    });
}

const demo = document.getElementById('measurement-demo');
if (demo) {
    demo.addEventListener('submit', event => {
        event.preventDefault();
        if (!demo.reportValidity()) return;
        const [hours, minutes] = demo.elements.continuity.value.split(':').map(Number);
        const total = hours + minutes / 60;
        const pressure = Number(demo.elements.pressure.value);
        const format = value => value.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 4});
        document.getElementById('alert-title').textContent = 'Ejemplo validado';
        document.getElementById('alert-message').textContent = `Continuidad: ${format(total)} h/día. Presión: ${format(pressure)} m.c.a. Los valores son válidos para esta demostración. No se han guardado datos.`;
        document.getElementById('app-alert').showModal();
    });
}

const alertDialog = document.getElementById('app-alert');
if (alertDialog && alertDialog.dataset.autoOpen === 'true' && typeof alertDialog.showModal === 'function') {
    alertDialog.showModal();
}

const tablaMediciones = document.getElementById('tabla-mediciones');
if (tablaMediciones && window.simpleDatatables) {
    new window.simpleDatatables.DataTable(tablaMediciones, {
        perPage: 10,
        perPageSelect: [10, 20, 50],
        searchable: true,
        labels: {
            placeholder: 'Buscar medición...',
            perPage: 'Registros por página',
            noRows: 'No hay mediciones para mostrar.',
            noResults: 'No se encontraron mediciones.',
            info: 'Mostrando {start} a {end} de {rows} mediciones',
        },
    });
}

const puntoControl = document.getElementById('punto-control');
if (puntoControl && window.TomSelect) {
    const selectorPunto = new window.TomSelect(puntoControl, {
        create: false,
        allowEmptyOption: true,
        maxOptions: 200,
        placeholder: 'Busca y selecciona un punto de control',
    });
    selectorPunto.clear(true);
}

const indicadorRegistro = document.getElementById('indicador-registro');
const valorMedicion = document.getElementById('valor-medicion');
const etiquetaValor = document.getElementById('etiqueta-valor');
const ayudaValor = document.getElementById('ayuda-valor');
function actualizarCampoMedicion() {
    if (!indicadorRegistro || !valorMedicion || !etiquetaValor || !ayudaValor) return;
    const esPresion = indicadorRegistro.value === 'presion';
    valorMedicion.value = '';
    valorMedicion.type = esPresion ? 'number' : 'text';
    valorMedicion.inputMode = esPresion ? 'decimal' : 'numeric';
    valorMedicion.placeholder = esPresion ? 'Ej. 17.12' : 'Ej. 23:46';
    valorMedicion.min = esPresion ? '0' : '';
    valorMedicion.step = esPresion ? '0.01' : '';
    valorMedicion.pattern = esPresion ? '' : '(?:[01][0-9]|2[0-4]):[0-5][0-9]';
    etiquetaValor.textContent = esPresion ? 'Presión (m.c.a.)' : 'Continuidad (HH:MM)';
    ayudaValor.textContent = esPresion ? 'Ingresa un valor igual o mayor que cero.' : 'Desde 00:00 hasta 24:00.';
}
if (indicadorRegistro) {
    indicadorRegistro.addEventListener('change', actualizarCampoMedicion);
    actualizarCampoMedicion();
}

const flashMessage = document.getElementById('flash-message');
if (flashMessage && window.Swal) {
    window.Swal.fire({
        icon: flashMessage.dataset.type === 'error' ? 'error' : 'success',
        title: flashMessage.dataset.type === 'error' ? 'No se pudo completar la acción' : 'Operación realizada',
        text: flashMessage.dataset.message,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#1768bb',
    });
}
