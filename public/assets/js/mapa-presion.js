'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('mapa-presion');
    if (!container || typeof window.L === 'undefined') return;

    const puntos = JSON.parse(container.dataset.puntos || '[]');
    if (puntos.length === 0) return;

    const indicador = container.dataset.indicador || 'presion';
    const unidad = container.dataset.unidad || 'm.c.a.';
    const puntoSeleccionado = Number(container.dataset.puntoSeleccionado || 0);

    const detalle = document.getElementById('mapa-detalle');
    const mapa = window.L.map(container, { scrollWheelZoom: true, zoomControl: true });
    const claveMapTiler = container.dataset.maptilerKey || '';
    const capasBase = {};
    if (claveMapTiler) {
        const claveCodificada = encodeURIComponent(claveMapTiler);
        capasBase['Satélite'] = window.L.tileLayer(`https://api.maptiler.com/maps/satellite-v4/{z}/{x}/{y}.png?key=${claveCodificada}`, {
            maxZoom: 20,
            attribution: '© MapTiler © OpenStreetMap contributors'
        }).addTo(mapa);
        capasBase['Plano'] = window.L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${claveCodificada}`, {
            maxZoom: 20,
            attribution: '© MapTiler © OpenStreetMap contributors'
        });
    } else {
        capasBase['Satélite (temporal)'] = window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles © Esri'
        }).addTo(mapa);
    }
    window.L.control.layers(capasBase, null, { position: 'topright' }).addTo(mapa);

    const limites = [];
    const formato = valor => Number(valor).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const clase = valor => indicador === 'continuidad'
        ? (valor < 12 ? 'map-marker-bajo' : valor < 17 ? 'map-marker-medio' : 'map-marker-alto')
        : (valor < 20 ? 'map-marker-bajo' : valor < 28 ? 'map-marker-medio' : 'map-marker-alto');
    const mostrarDetalle = punto => {
        const otro = indicador === 'continuidad' ? punto.presion : punto.continuidad;
        const otraUnidad = indicador === 'continuidad' ? 'm.c.a.' : 'h/día';
        const imagen = punto.imagen_url
            ? `<img class="map-reference-image" src="${punto.imagen_url}" alt="Referencia visual del punto ${punto.codigo}">`
            : '<div class="map-reference-empty"><i class="bi bi-image" aria-hidden="true"></i><span>Sin foto de referencia</span></div>';
        detalle.innerHTML = `<p class="text-xs font-semibold tracking-widest text-brand-700">${punto.codigo}</p>${imagen}<p class="mt-3 text-3xl font-semibold tabular-nums text-slate-900">${formato(punto.valor)} <span class="text-sm font-medium text-slate-500">${unidad}</span></p><dl class="mt-5 space-y-3"><div><dt class="text-xs text-slate-500">Dirección</dt><dd class="font-medium text-slate-900">${punto.direccion}</dd></div><div><dt class="text-xs text-slate-500">${indicador === 'continuidad' ? 'Presión' : 'Continuidad'} del punto</dt><dd class="font-medium text-slate-900">${otro === null ? 'Sin registro' : `${formato(otro)} ${otraUnidad}`}</dd></div><div><dt class="text-xs text-slate-500">Relación operativa</dt><dd class="font-medium text-slate-900">${punto.fuente} · Sector ${punto.sector} · ${punto.zona_presion}</dd></div><div><dt class="text-xs text-slate-500">Ubicación</dt><dd>${Number(punto.coordenadas_referenciales) === 1 ? 'Referencial' : 'Validada manualmente'}</dd></div></dl>`;
    };

    let marcadorSeleccionado = null;
    puntos.forEach(punto => {
        const valor = Number(punto.valor);
        const ubicacion = [Number(punto.latitud), Number(punto.longitud)];
        const icono = window.L.divIcon({ className: 'map-marker-wrapper', html: `<span class="map-marker ${clase(valor)}"></span>`, iconSize: [18, 18], iconAnchor: [9, 9] });
        const marcador = window.L.marker(ubicacion, { icon: icono, title: `${punto.codigo}: ${formato(valor)} ${unidad}` }).addTo(mapa);
        const etiqueta = `<strong>${punto.codigo}</strong><br>${formato(valor)} ${unidad}`;
        marcador.bindTooltip(etiqueta, { permanent: true, direction: 'top', offset: [0, -10], className: 'map-pressure-label' });
        const continuidad = punto.continuidad === null ? 'Sin registro' : `${formato(punto.continuidad)} h/día`;
        const presion = punto.presion === null ? 'Sin registro' : `${formato(punto.presion)} m.c.a.`;
        marcador.bindPopup(`<strong>${punto.codigo}</strong><br>${punto.direccion}<hr class="map-popup-divider"><span>Continuidad</span><b>${continuidad}</b><br><span>Presión</span><b>${presion}</b><br><small>${punto.fuente} · Sector ${punto.sector} · ${punto.zona_presion}</small>`);
        marcador.on('click', () => mostrarDetalle(punto));
        if (Number(punto.id) === puntoSeleccionado) {
            marcadorSeleccionado = { marcador, punto, ubicacion };
        }
        limites.push(ubicacion);
    });

    mapa.fitBounds(limites, { padding: [36, 36], maxZoom: 15 });
    if (marcadorSeleccionado) {
        mapa.setView(marcadorSeleccionado.ubicacion, 17, { animate: false });
        marcadorSeleccionado.marcador.openPopup();
        mostrarDetalle(marcadorSeleccionado.punto);
    }
});
