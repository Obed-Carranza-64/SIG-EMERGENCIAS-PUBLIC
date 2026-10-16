(function () {
  'use strict';

  var CFG = window.SIG_CONFIG || {};
  var SALVADOR = [
    typeof CFG.mapCenterLat === 'number' ? CFG.mapCenterLat : 13.7942,
    typeof CFG.mapCenterLon === 'number' ? CFG.mapCenterLon : -88.8965
  ];
  var NOMINATIM = CFG.nominatimUrl || 'https://nominatim.openstreetmap.org';
  var NOM_LANG = CFG.nominatimLang || 'es';
  var NOM_LIMIT = CFG.nominatimLimit || 6;
  var NOM_EXTRA = CFG.nominatimCountry ? '&countrycodes=' + encodeURIComponent(CFG.nominatimCountry) : '';
  var ZOOM = CFG.mapZoom || 8;
  var ZOOM_HEAT = CFG.mapHeatZoom || 9;
  var MAPS = {};

  /* ---------------- Mapas / tiles OpenStreetMap ---------------- */
  function osmTileLayer() {
    return L.tileLayer(CFG.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      subdomains: CFG.tileSubdomains || 'abc',
      maxZoom: CFG.tileMaxZoom || 19,
      attribution: CFG.tileAttribution || '&copy; OpenStreetMap contributors'
    });
  }

  function heatPoints() {
    var base = [
      [13.4833, -88.1833, 1.0],
      [13.6768, -89.2903, 0.9],
      [13.7187, -89.1083, 0.6],
      [13.8754, -89.9200, 0.8],
      [13.9942, -89.5597, 0.7],
      [13.9042, -88.8783, 0.5],
      [13.4769, -88.1763, 0.7],
      [14.0333, -88.9333, 0.9],
      [13.2870, -88.5358, 0.6],
      [13.8400, -89.7700, 0.5]
    ];
    var pts = [];
    base.forEach(function (p) {
      for (var i = 0; i < 14; i++) {
        pts.push([
          p[0] + (Math.random() - 0.5) * 0.22,
          p[1] + (Math.random() - 0.5) * 0.22,
          Math.max(0.15, p[2] - Math.random() * 0.4)
        ]);
      }
    });
    return pts;
  }

  function initMap(id, opts) {
    var el = document.getElementById(id);
    if (!el || !window.L) return null;
    opts = opts || {};

    var mapArgs = { zoomControl: false };
    if (opts.interactive === false) {
      mapArgs.dragging = false;
      mapArgs.scrollWheelZoom = false;
      mapArgs.doubleClickZoom = false;
      mapArgs.boxZoom = false;
      mapArgs.keyboard = false;
      mapArgs.touchZoom = false;
      mapArgs.attributionControl = false;
    }
    var map = L.map(id, mapArgs)
      .setView(opts.center || SALVADOR, opts.zoom || 8);

    osmTileLayer().addTo(map);

    if (opts.dark !== false) {
      map.getContainer().classList.add('map-dark');
    }

    if (opts.heat) {
      map._heatLayer = L.heatLayer(heatPoints(), {
        radius: 34,
        blur: 30,
        maxZoom: 12,
        gradient: { 0.2: '#3b82f6', 0.4: '#f59e0b', 0.6: '#ff5a2d', 0.85: '#e21f2e', 1.0: '#ff1447' }
      });
      map._heatLayer.addTo(map);
    }

    if (opts.markers) {
      opts.markers.forEach(function (m) {
        L.marker([m[0], m[1]], { icon: sigPin('fa-triangle-exclamation', '#ff2d3f'), interactive: false })
          .addTo(map).bindPopup('<strong>' + m[2] + '</strong>');
      });
    }

    MAPS[id] = map;
    if (opts.interactive !== false) {
      initMapTools(map);
      if (opts.geocode !== false) {
        initReverseGeocode(map);
      }
    }
    return map;
  }

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ---------------- Controles y tema ---------------- */
  function initMapTools(map) {
    var wrap = map.getContainer().closest('.map-wrap');
    if (!wrap) return;

    wrap.querySelectorAll('[data-map-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var action = btn.getAttribute('data-map-action');
        if (action === 'zoom-in') { map.zoomIn(); }
        else if (action === 'zoom-out') { map.zoomOut(); }
        else if (action === 'locate') { locateUser(map); }
        else if (action === 'expand') { toggleFullscreen(wrap); }
        else if (action === 'theme') { map.getContainer().classList.toggle('map-dark'); }
      });
    });
  }

  function locateUser(map) {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function (pos) {
      var ll = [pos.coords.latitude, pos.coords.longitude];
      map.setView(ll, 14);
      L.marker(ll, { icon: sigUser() })
        .addTo(map).bindPopup('Tu ubicación').openPopup();
    }, function () {
      alert('No se pudo obtener tu ubicación.');
    });
  }

  function toggleFullscreen(wrap) {
    if (!document.fullscreenElement) {
      if (wrap.requestFullscreen) wrap.requestFullscreen();
    } else if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }

  /* ---------------- Geocodificación inversa (Nominatim) ---------------- */
  function initReverseGeocode(map) {
    var wrap = map.getContainer().closest('.map-wrap');
    var coords = wrap ? wrap.querySelector('.map-coords') : null;

    map.on('click', function (e) {
      var lat = e.latlng.lat.toFixed(5);
      var lon = e.latlng.lng.toFixed(5);
      if (coords) coords.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Obteniendo dirección...';

      fetch(NOMINATIM + '/reverse?format=jsonv2&accept-language=' + encodeURIComponent(NOM_LANG) + '&lat=' + lat + '&lon=' + lon)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var label = data && data.display_name ? data.display_name : (lat + ', ' + lon);
          if (coords) coords.innerHTML = '<i class="fa-solid fa-location-dot"></i> ' + label;
        })
        .catch(function () {
          if (coords) coords.innerHTML = '<i class="fa-solid fa-location-dot"></i> ' + lat + ', ' + lon;
        });
    });
  }

  /* ---------------- Búsqueda de lugares (Nominatim) ---------------- */
  function initOsmSearch() {
    document.querySelectorAll('[data-osm-search]').forEach(function (input) {
      var wrap = input.closest('.map-search');
      var box = wrap ? wrap.querySelector('.osm-results') : null;
      var map = MAPS[input.getAttribute('data-osm-target')];
      if (!box || !map) return;

      var marker = null;
      var timer = null;

      function runSearch(q) {
        box.innerHTML = '<div class="osm-item"><i class="fa-solid fa-spinner fa-spin"></i> Buscando...</div>';
        box.classList.add('is-open');

        var url = NOMINATIM + '/search?format=jsonv2&addressdetails=1&limit=' + NOM_LIMIT + '&accept-language=' + encodeURIComponent(NOM_LANG) + NOM_EXTRA + '&q=' + encodeURIComponent(q);
        fetch(url)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.length) {
              box.innerHTML = '<div class="osm-item">Sin resultados</div>';
              return;
            }
            box.innerHTML = '';
            data.forEach(function (place) {
              var item = document.createElement('div');
              item.className = 'osm-item';
              item.innerHTML = '<i class="fa-solid fa-location-dot"></i><span>' + place.display_name + '</span>';
              item.addEventListener('click', function () {
                var lat = parseFloat(place.lat);
                var lon = parseFloat(place.lon);
                map.setView([lat, lon], 14);
                if (marker) { map.removeLayer(marker); }
                marker = L.marker([lat, lon], { icon: sigPin('fa-location-dot', '#ff2d3f') }).addTo(map).bindPopup(place.display_name).openPopup();
                input.value = place.display_name;
                box.classList.remove('is-open');
              });
              box.appendChild(item);
            });
          })
          .catch(function () {
            box.innerHTML = '<div class="osm-item">Error al buscar</div>';
          });
      }

      input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 3) { box.innerHTML = ''; box.classList.remove('is-open'); return; }
        timer = setTimeout(function () { runSearch(q); }, 400);
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); runSearch(input.value.trim()); }
      });

      document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) { box.classList.remove('is-open'); }
      });
    });
  }

  /* ---------------- Capas geo reales (mapa principal) ---------------- */
  var NODES = [
    [13.7942, -88.8965, 'Nodo Central'],
    [13.6768, -89.2903, 'Nodo Santa Tecla'],
    [13.4833, -88.1833, 'Nodo San Miguel'],
    [14.0333, -88.9333, 'Nodo Chalatenango'],
    [13.3500, -88.4400, 'Nodo Usulután'],
    [13.9357, -89.0259, 'Nodo Suchitoto']
  ];

  function alertColor(nivel) {
    return nivel === 'alta' ? '#ff2d3f' : (nivel === 'media' ? '#f59e0b' : '#2ecc71');
  }

  function tipoKey(t) {
    t = (t || '').toLowerCase();
    if (t.indexOf('incendio') > -1) return 'incendios';
    if (t.indexOf('inund') > -1 || t.indexOf('lluvia') > -1) return 'inundaciones';
    return 'deslizamientos';
  }

  function sigPin(fa, color) {
    return L.divIcon({
      className: 'sig-divicon',
      html: '<div class="sig-pin" style="--c:' + color + '"><i class="fa-solid ' + esc(fa || 'fa-triangle-exclamation') + '"></i></div>',
      iconSize: [32, 32],
      iconAnchor: [16, 30],
      popupAnchor: [0, -28]
    });
  }

  function sigDot(fa, color) {
    return L.divIcon({
      className: 'sig-divicon',
      html: '<div class="sig-dot" style="--c:' + color + '"><i class="fa-solid ' + esc(fa || 'fa-circle') + '"></i></div>',
      iconSize: [26, 26],
      iconAnchor: [13, 13],
      popupAnchor: [0, -12]
    });
  }

  function sigUser() {
    return L.divIcon({
      className: 'sig-divicon',
      html: '<div class="sig-user"></div>',
      iconSize: [18, 18],
      iconAnchor: [9, 9]
    });
  }

  function popHead(fa, color, title) {
    return '<div class="pop-head"><i class="fa-solid ' + esc(fa) + '" style="color:' + color + '"></i><strong>' + esc(title) + '</strong></div>';
  }

  function geoMarker(group, lat, lon, icon, popup) {
    if (lat === null || lon === null || lat === undefined || lon === undefined) return null;
    var m = L.marker([parseFloat(lat), parseFloat(lon)], { icon: icon });
    if (popup) m.bindPopup(popup);
    m.addTo(group);
    return m;
  }

  function initGeoLayers() {
    var map = MAPS.mapFull;
    var geo = window.GEO;
    if (!map || !geo || !window.L) return;

    var groups = {
      riesgo: L.layerGroup(),
      incendios: L.layerGroup(),
      inundaciones: L.layerGroup(),
      deslizamientos: L.layerGroup(),
      red: L.layerGroup(),
      recursos: L.layerGroup(),
      limites: L.layerGroup()
    };
    if (map._heatLayer) {
      groups.riesgo.addLayer(map._heatLayer);
    }

    (geo.alertas || []).forEach(function (a) {
      var c = alertColor(a.nivel);
      geoMarker(groups[tipoKey(a.tipo)], a.lat, a.lon, sigPin(a.icon, c),
        popHead(a.icon || 'fa-triangle-exclamation', c, a.tipo) + esc(a.lugar) + '<br><small>' + esc(a.tiempo) + '</small>');
    });

    (geo.reportes || []).forEach(function (r) {
      var c = r.estado === 'Resuelto' ? '#2ecc71' : (r.estado === 'En atención' ? '#3b82f6' : '#ff2d3f');
      geoMarker(groups[tipoKey(r.titulo)], r.lat, r.lon, sigPin(r.icon, c),
        popHead(r.icon || 'fa-bullhorn', c, r.titulo) + esc(r.lugar) + '<br><small>' + esc(r.estado) + ' · ' + esc(r.tiempo) + '</small>');
    });

    NODES.forEach(function (n, i) {
      L.marker([n[0], n[1]], { icon: sigDot('fa-tower-broadcast', '#2ecc71') })
        .bindPopup(popHead('fa-tower-broadcast', '#2ecc71', n[2]) + 'Nodo activo').addTo(groups.red);
      if (i > 0) {
        L.polyline([[NODES[0][0], NODES[0][1]], [n[0], n[1]]], { color: '#2ecc71', weight: 1, dashArray: '4 4', opacity: 0.6 }).addTo(groups.red);
      }
    });

    (geo.albergues || []).forEach(function (a) {
      geoMarker(groups.recursos, a.lat, a.lon, sigDot('fa-house-chimney', '#3b82f6'),
        popHead('fa-house-chimney', '#3b82f6', a.n) + esc(a.cap) + '<br><small>' + esc(a.estado) + '</small>');
    });

    L.rectangle([[12.9, -90.2], [14.5, -87.6]], { color: '#3b82f6', weight: 2, dashArray: '6 6', fill: false }).addTo(groups.limites);

    document.querySelectorAll('.layer .switch input[data-layer]').forEach(function (sw) {
      var g = groups[sw.getAttribute('data-layer')];
      if (!g) return;
      if (sw.checked) { g.addTo(map); } else { map.removeLayer(g); }
      sw.addEventListener('change', function () {
        var row = sw.closest('.layer');
        row.style.opacity = sw.checked ? '1' : '.55';
        if (sw.checked) { g.addTo(map); } else { map.removeLayer(g); }
      });
    });

    if (window.MAP_TARGET) {
      var t = window.MAP_TARGET;
      map.setView([t.lat, t.lon], 13);
      L.marker([t.lat, t.lon], { icon: sigPin('fa-location-dot', '#ff2d3f') }).addTo(map).bindPopup('<strong>' + esc(t.label) + '</strong>').openPopup();
    }
  }

  function initReportesMap() {
    var map = MAPS.mapReportes;
    var data = window.GEO_REPORTES;
    if (!map || !data || !window.L) return;
    data.forEach(function (r) {
      var c = r.estado === 'Resuelto' ? '#2ecc71' : (r.estado === 'En atención' ? '#3b82f6' : '#ff2d3f');
      geoMarker(map, r.lat, r.lon, sigPin(r.icon, c),
        popHead(r.icon || 'fa-bullhorn', c, r.titulo) + esc(r.lugar) + '<br><small>' + esc(r.estado) + '</small>');
    });
  }

  /* ---------------- Capas / tabs / filtros ---------------- */
  function initLayers() {
    document.querySelectorAll('.layer .switch input').forEach(function (sw) {
      if (sw.hasAttribute('data-layer')) return;
      sw.addEventListener('change', function () {
        var row = sw.closest('.layer');
        row.style.opacity = sw.checked ? '1' : '.55';
      });
    });
  }

  function initTabs() {
    document.querySelectorAll('.tabs').forEach(function (tabs) {
      tabs.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-filter]');
        if (!btn) return;
        tabs.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        var filter = btn.getAttribute('data-filter');
        document.querySelectorAll('.js-report').forEach(function (row) {
          var match = filter === 'todos' || row.getAttribute('data-estado') === filter;
          row.style.display = match ? '' : 'none';
        });
      });
    });
  }

  function initSearch() {
    document.querySelectorAll('[data-search]').forEach(function (input) {
      var target = input.getAttribute('data-search');
      input.addEventListener('input', function () {
        var q = input.value.toLowerCase().trim();
        document.querySelectorAll(target).forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
      });
    });
  }

  function initAlertTabs() {
    var tabs = document.getElementById('alertTabs');
    if (!tabs) return;
    tabs.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-anivel]');
      if (!btn) return;
      tabs.querySelectorAll('[data-anivel]').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      var f = btn.getAttribute('data-anivel');
      document.querySelectorAll('.js-alerta').forEach(function (row) {
        var n = row.getAttribute('data-nivel');
        var show = f === 'all' || (f === 'alta' ? n === 'alta' : (n === 'media' || n === 'baja'));
        row.style.display = show ? '' : 'none';
      });
    });
  }

  function initKitTabs() {
    var tabs = document.getElementById('kitTabs');
    if (!tabs) return;
    tabs.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-kcat]');
      if (!btn) return;
      tabs.querySelectorAll('[data-kcat]').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      var f = btn.getAttribute('data-kcat');
      document.querySelectorAll('.js-kit').forEach(function (card) {
        card.style.display = (f === 'todas' || card.getAttribute('data-cat') === f) ? '' : 'none';
      });
    });
  }

  function initReportFilters() {
    var boxes = document.querySelectorAll('.js-estado');
    var tabs = document.getElementById('reportTabs');
    if (!boxes.length || !tabs) return;

    function checkedVals() {
      return Array.prototype.filter.call(boxes, function (b) { return b.checked; })
        .map(function (b) { return b.value; });
    }
    function applyBoxes() {
      var on = checkedVals();
      document.querySelectorAll('.js-report').forEach(function (row) {
        row.style.display = on.indexOf(row.getAttribute('data-estado')) > -1 ? '' : 'none';
      });
    }
    boxes.forEach(function (b) {
      b.addEventListener('change', function () {
        var vals = checkedVals();
        tabs.querySelectorAll('[data-filter]').forEach(function (t) { t.classList.remove('is-active'); });
        if (vals.length === 4) {
          tabs.querySelector('[data-filter="todos"]').classList.add('is-active');
        } else if (vals.length === 1) {
          var t = tabs.querySelector('[data-filter="' + vals[0] + '"]');
          if (t) t.classList.add('is-active');
        }
        applyBoxes();
      });
    });
    tabs.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-filter]');
      if (!btn) return;
      var f = btn.getAttribute('data-filter');
      boxes.forEach(function (b) { b.checked = (f === 'todos' || b.value === f); });
    });
  }

  function initKitModal() {
    var modal = document.getElementById('kitModal');
    if (!modal) return;
    var body = document.getElementById('kitModalBody');

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    }
    document.querySelectorAll('.js-kit-detail').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var k;
        try { k = JSON.parse(btn.getAttribute('data-kit')); } catch (err) { return; }
        var badgeCls = k.disp === 'Disponible' ? 'disp' : (k.disp === 'En uso' ? 'uso' : 'revision');
        body.innerHTML =
          '<div class="row" style="margin-bottom:12px">' +
            '<div class="ic ' + esc(k.color) + '"><i class="fa-solid ' + esc(k.icon) + '"></i></div>' +
            '<div class="txt"><strong>' + esc(k.n) + '</strong><small>' + esc(k.cat) + ' · ' + esc(k.tipo) + '</small></div>' +
            '<span class="badge ' + badgeCls + '">' + esc(k.disp) + '</span>' +
          '</div>' +
          '<div class="list">' +
            '<div class="row"><div class="ic blue"><i class="fa-solid fa-location-dot"></i></div><div class="txt"><strong>Ubicación actual</strong><small>' + esc(k.loc) + '</small></div></div>' +
            '<div class="row"><div class="ic purple"><i class="fa-solid fa-cubes"></i></div><div class="txt"><strong>Existencias</strong><small>' + esc(k.cant) + '</small></div></div>' +
            '<div class="row"><div class="ic amber"><i class="fa-solid fa-calendar-day"></i></div><div class="txt"><strong>Vencimiento</strong><small>' + esc(k.fecha) + '</small></div></div>' +
          '</div>' +
          '<p style="color:var(--muted);font-size:12px;margin:12px 0 0">Usa “Asignar” en la tarjeta para cambiar su estado a En uso.</p>';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
      });
    });
    modal.querySelectorAll('.js-modal-close').forEach(function (b) {
      b.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  }

  function showToast(msg, ok) {
    var t = document.getElementById('jsToast');
    if (!t) return;
    t.className = 'toast ' + (ok === false ? 'is-err' : 'is-ok');
    t.innerHTML = '<i class="fa-solid ' + (ok === false ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i> ' + esc(msg);
    void t.offsetWidth;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(function () { t.classList.remove('show'); }, 4000);
  }

  function initToasts() {
    document.querySelectorAll('.toast[data-show]').forEach(function (t) {
      setTimeout(function () { t.classList.add('show'); }, 150);
      setTimeout(function () { t.classList.remove('show'); }, 4500);
    });
    document.querySelectorAll('[data-toast]').forEach(function (el) {
      el.addEventListener('click', function () {
        showToast(el.getAttribute('data-toast'), true);
      });
    });
  }

  function initPicker() {
    var el = document.getElementById('pickerMap');
    if (!el || !window.L) return;
    var map = initMap('pickerMap', { zoom: 9, geocode: false });
    if (!map) return;
    var latI = document.getElementById('fLat');
    var lonI = document.getElementById('fLon');
    var label = document.getElementById('pickerLabel');
    var startLat = parseFloat(latI.value) || SALVADOR[0];
    var startLon = parseFloat(lonI.value) || SALVADOR[1];
    var marker = L.marker([startLat, startLon], { icon: sigPin('fa-location-dot', '#ff2d3f') }).addTo(map);
    map.setView([startLat, startLon], ZOOM_HEAT);
    map.on('click', function (e) {
      var lat = e.latlng.lat.toFixed(5);
      var lon = e.latlng.lng.toFixed(5);
      latI.value = lat;
      lonI.value = lon;
      marker.setLatLng(e.latlng);
      if (label) label.textContent = lat + ', ' + lon;
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMaps();
    initOsmSearch();
    initLayers();
    initTabs();
    initSearch();
    initGeoLayers();
    initReportesMap();
    initAlertTabs();
    initKitTabs();
    initReportFilters();
    initKitModal();
    initToasts();
    initPicker();
  });

  function initMaps() {
    initMap('mapInicio', { zoom: ZOOM, heat: true, interactive: false, markers: [[13.4833, -88.1833, 'Volcán San Miguel']] });
    initMap('mapFull', { zoom: ZOOM_HEAT, heat: true });
    initMap('mapReportes', { zoom: ZOOM_HEAT, heat: true });
  }
})();
