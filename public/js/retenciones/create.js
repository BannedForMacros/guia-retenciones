/* Retenciones – Crear (UI minimalista).
 * - Datos de factura del datamarket: TEXTO (no inputs).
 * - Sin línea manual: las filas se agregan solo desde el buscador de facturas.
 * - Totales como tfoot row alineado a las columnas.
 * - Tasa fija 3 % (régimen general SUNAT). */

/* ── Constantes ──────────────────────────────────────────────────────── */
var TASA_PCT = 3.00;
var TASA_DEC = TASA_PCT / 100;

var TIPOS_DOC_REL = [
  { v: '01', t: 'Factura' },
  { v: '07', t: 'Nota de Crédito' },
  { v: '08', t: 'Nota de Débito' },
];

var contadorLineas = 0;

/* ── Helpers locales (los formateos/parseos vienen de RetUtils) ──────── */
function _escapeHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
  });
}
function _initial(name) {
  var s = String(name || '').trim();
  return s ? s.charAt(0).toUpperCase() : '·';
}
/* Wrappers cortos sobre RetUtils — mantienen el JS legible. */
var _fmt2     = function (n) { return RetUtils.fmtNum(n, 2); };
var _parseNum = function (raw) { return RetUtils.parseNum(raw); };

/* ── Init ────────────────────────────────────────────────────────────── */
$(document).ready(function () {

  /* Buscador de PROVEEDORES */
  $('#proveedor_select').select2({
    theme: 'bootstrap-5',
    placeholder: 'Buscar proveedor (RUC o razón social)…',
    minimumInputLength: 2,
    allowClear: false,
    dropdownAutoWidth: true,
    ajax: {
      url: route('retenciones.listarProveedores'),
      type: 'GET', dataType: 'json', delay: 250,
      data: function (params) { return { term: params.term, limit: 25 }; },
      processResults: function (data) { return { results: data.items || [] }; },
    },
  });

  $('#proveedor_select').on('select2:select', function (e) {
    var d = e.params.data || {};
    var prov = {
      ruc:       d.proveedor_ruc       || '',
      nombre:    d.proveedor_nombre    || '',
      direccion: d.proveedor_direccion || '',
    };

    // Si el proveedor NO esta marcado como afecto a retencion, advertimos
    // y ofrecemos marcarlo en un solo paso.
    if (d.afecto_retencion === false) {
      validarYSeleccionarProveedor(prov);
    } else {
      seleccionarProveedor(prov);
    }

    // Limpiamos la selección visible — la info ya está en el chip (si procedio).
    $('#proveedor_select').val(null).trigger('change');
  });

  $('#btn_limpiar_proveedor').on('click', limpiarProveedor);

  // Contador del textarea de Observacion
  $(document).on('input', '#observacion', function () {
    $('#obs_counter').text((this.value || '').length + ' / 250');
  });
});

/* ─── Selección / limpieza de proveedor ──────────────────────────────── */

/**
 * Si el proveedor elegido no esta marcado como afecto a retencion, mostramos
 * SOLO un aviso (sin accion). El usuario debe ir a "Proveedores Retenidos" en
 * el listado para gestionar el padron — no lo cambiamos desde este form.
 */
function validarYSeleccionarProveedor(prov) {
  var nombre = (prov.nombre || '').toUpperCase();
  var ruc    = prov.ruc || '';

  Swal.fire({
    title: 'Proveedor no afecto a retención',
    html:
      '<div class="text-start">' +
        '<p class="mb-2">No se puede emitir una retención a este proveedor porque no figura en el padrón:</p>' +
        '<div class="p-2 mb-3" style="background:#F5F5F5;border:1px solid #e5e7eb;border-radius:6px;">' +
          '<div style="font-weight:700;color:#1A3A5C;">' + _escapeHtml(nombre) + '</div>' +
          '<div style="font-family:ui-monospace,monospace;font-size:.78rem;color:#6b7280;">RUC: ' + _escapeHtml(ruc) + '</div>' +
        '</div>' +
        '<p class="mb-0 small text-muted">Para marcarlo, abre <b>Proveedores Retenidos</b> desde el listado de retenciones.</p>' +
      '</div>',
    icon: 'warning',
    confirmButtonText:  'Cerrar',
    confirmButtonColor: '#0D2E6E',
    width: 520,
  });
  // No seleccionamos al proveedor: el form se queda en el estado previo.
}

function seleccionarProveedor(p) {
  $('#proveedor_ruc').val(p.ruc);
  $('#proveedor_razon').val((p.nombre || '').toUpperCase());
  $('#proveedor_direccion').val(p.direccion);

  $('#prov_avatar').text(_initial(p.nombre));
  $('#prov_name').text((p.nombre || '').toUpperCase());
  $('#prov_ruc').text(p.ruc);
  $('#prov_addr').text(p.direccion || '—');
  $('#proveedor_chip').removeClass('d-none');
  $('#proveedor_empty').addClass('d-none');

  inicializarSelectorFacturas(p.ruc);
}

function limpiarProveedor() {
  $('#proveedor_ruc').val('');
  $('#proveedor_razon').val('');
  $('#proveedor_direccion').val('');
  $('#proveedor_chip').addClass('d-none');
  $('#proveedor_empty').removeClass('d-none');

  $('#detalles_body').empty();
  $('#vacio_msg').show();
  $('#totales_foot').addClass('d-none');
  destruirSelectorFacturas();
  recalcularTodo();
}

/* ─── Selector de facturas pendientes ────────────────────────────────── */

function destruirSelectorFacturas() {
  var $sel = $('#factura_select');
  if ($sel.data('select2')) $sel.select2('destroy');
  // Restauramos el placeholder visible para que no quede el select "fantasma".
  $sel.empty()
      .append('<option value="">Primero selecciona un proveedor…</option>')
      .prop('disabled', true);
}

function inicializarSelectorFacturas(rucProveedor) {
  destruirSelectorFacturas();
  if (!rucProveedor || rucProveedor.length !== 11) return;

  // CRITICO: removemos cualquier handler previo. select2('destroy') NO limpia
  // los .on() de jQuery, asi que si no hacemos esto, cada cambio de proveedor
  // agrega un listener mas y un click selecciona N facturas (bug de duplicados).
  $('#factura_select').off('select2:select');

  $('#factura_select').prop('disabled', false).select2({
    theme: 'bootstrap-5',
    placeholder: 'Buscar factura del proveedor…',
    allowClear: false,
    minimumInputLength: 0,
    dropdownAutoWidth: true,
    ajax: {
      url: route('retenciones.facturasProveedor'),
      dataType: 'json', delay: 250,
      data: function (params) {
        return { ruc: rucProveedor, q: params.term || '', solo_disponibles: 0, limit: 50 };
      },
      processResults: function (data) {
        var yaEnForm = {};
        $('#detalles_body tr').each(function () {
          var sn = $(this).data('serie-num');
          if (sn) yaEnForm[sn] = true;
        });
        var items = (data.items || [])
          .filter(function (f) { return !yaEnForm[f.serie_numero]; })
          .map(function (f) {
            // Una factura solo se bloquea si NO tiene saldo pendiente (totalmente
            // cobrada). Si tiene retenciones previas pero queda saldo, sigue
            // disponible para retencion parcial.
            return $.extend({}, f, {
              id: f.serie_numero,
              text: f.serie_numero,
              disabled: f.disponible === false,
            });
          });
        return { results: items };
      },
    },
    templateResult: function (f) {
      if (!f.id) return f.text;
      var sym       = (f.moneda === 'USD') ? 'US$' : 'S/';
      var importe   = parseFloat(f.importe_total || 0).toFixed(2);
      var saldo     = parseFloat(f.saldo_pendiente != null ? f.saldo_pendiente : f.importe_total || 0).toFixed(2);
      var retenido  = parseFloat(f.total_retenido_acumulado || 0).toFixed(2);
      var disponible = f.disponible !== false;
      var yaParcial  = !!f.ya_retenida && disponible;
      var tipoTxt    = (TIPOS_DOC_REL.find(function (t) { return t.v === f.tipo_doc; }) || {}).t || f.tipo_doc;

      var clase = !disponible ? 'factura-result is-retenida' : 'factura-result';

      // Badge: completamente retenida vs. retencion parcial vs. sin retencion previa
      var badge = '';
      if (!disponible) {
        badge = '<span class="badge-retenida" title="Sin saldo pendiente — totalmente cobrada">Retenida total</span>';
      } else if (yaParcial) {
        badge = '<span class="badge-parcial" title="Ya tiene retencion(es) previa(s)">Parcial · pagado ' + sym + ' ' + parseFloat(f.total_pagado_acumulado || 0).toFixed(2) + '</span>';
      }

      // Bloque inferior: saldo destacado (la info mas util para retener)
      var saldoBlock = '<div class="meta">Emitida: ' + f.fecha_emision +
                      ' · Total ' + sym + ' ' + importe +
                      (yaParcial ? ' · Retenido ' + sym + ' ' + retenido : '') +
                      '</div>';

      return $(
        '<div class="' + clase + '">' +
          '<div class="left">' +
            '<div class="top">' +
              '<span class="tipo-tag">' + f.tipo_doc + ' ' + tipoTxt + '</span>' +
              '<span class="serie">' + f.serie_numero + '</span>' +
              badge +
            '</div>' +
            saldoBlock +
          '</div>' +
          '<div class="importe">Saldo<br>' + sym + ' ' + saldo + '</div>' +
        '</div>'
      );
    },
    templateSelection: function (f) {
      return f.id ? f.text : 'Buscar factura del proveedor…';
    },
    escapeMarkup: function (m) { return m; },
  });

  $('#factura_select').on('select2:select', function (e) {
    var f = e.params.data;
    if (f.disponible === false) return;
    agregarLineaConFactura(f);
    $('#factura_select').val(null).trigger('change');
  });
}

/* ─── Agregar línea (texto, no inputs) ──────────────────────────────── */

function agregarLineaConFactura(f) {
  contadorLineas++;
  var idx = contadorLineas;
  var hoy = new Date().toISOString().slice(0, 10);
  var importeDoc = parseFloat(f.importe_total || 0);
  var saldoPend  = parseFloat(f.saldo_pendiente != null ? f.saldo_pendiente : importeDoc);
  var totalPagado   = parseFloat(f.total_pagado_acumulado   || 0);
  var totalRetenido = parseFloat(f.total_retenido_acumulado || 0);
  var ultimoNumPago = parseInt(f.ultimo_numero_pago, 10) || 0;
  // numero_pago = ultimo_numero_pago + 1, zero-padded a 3 digitos.
  var siguienteNumPago = ultimoNumPago + 1;
  var siguienteNumPagoStr = String(siguienteNumPago).padStart(3, '0');
  var moneda     = f.moneda || 'PEN';
  var esUsd      = moneda === 'USD';
  var simMoneda  = esUsd ? 'US$' : 'S/';

  // Toda la data del datamarket se guarda en data-* del <tr>; el form lo
  // usa al hacer submit. Las celdas son TEXTO (no editable).
  // Para USD: TC referencial (RetUtils.TC_DEFAULT) con 2 decimales — editable.
  var tcDefault = RetUtils.TC_DEFAULT.toFixed(RetUtils.TC_DECIMALS); // "3.75"
  var tCambioCell = esUsd
    ? '<input type="number" class="form-control text-end in-factor-cambio" step="0.01" min="0.01" value="' + tcDefault + '" title="Tipo de cambio PEN por USD (editable, máx. 2 decimales)">'
    : '<span class="text-muted">1.00</span>';

  var trClass = esUsd ? 'is-usd' : '';
  var monedaClass = esUsd ? 'is-usd' : 'is-pen';

  // El importe pago arranca con el saldo pendiente (lo que falta retener),
  // no con el importe total. Y el max del input tambien se limita al saldo
  // para evitar pagar mas de lo que queda.
  var importePagoDefault = saldoPend.toFixed(2);
  var importePagoMax     = saldoPend.toFixed(2);

  // Contenido del tooltip del icono "info" en la celda Importe Pago.
  // Muestra el historial real de la factura (de retenciones previas).
  var tieneHistoria = totalPagado > 0 || totalRetenido > 0 || ultimoNumPago > 0;
  var tooltipHtml =
    '<div class="info-pago-tip">' +
      '<div class="tip-head">' + _escapeHtml(f.serie_numero) + '</div>' +
      '<div class="tip-row"><span>Importe total</span><b>' + simMoneda + ' ' + _fmt2(importeDoc) + '</b></div>' +
      (tieneHistoria
        ? '<div class="tip-row"><span>Ya pagado</span><b>' + simMoneda + ' ' + _fmt2(totalPagado) + '</b></div>' +
          '<div class="tip-row"><span>Ya retenido</span><b>' + simMoneda + ' ' + _fmt2(totalRetenido) + '</b></div>' +
          '<div class="tip-row"><span>Ult. n° pago</span><b>' + (ultimoNumPago || '—') + '</b></div>'
        : '<div class="tip-row tip-muted"><i class="fa fa-circle-info"></i> Sin retenciones previas</div>') +
      '<div class="tip-row tip-saldo"><span>Saldo pendiente</span><b>' + simMoneda + ' ' + _fmt2(saldoPend) + '</b></div>' +
      (tieneHistoria
        ? '<div class="tip-row tip-prox"><span>Este pago será el</span><b>n° ' + siguienteNumPagoStr + '</b></div>'
        : '') +
    '</div>';

  // Solo el botón — el contenido del tooltip se inyecta por JS al construir
  // la Tooltip (mas confiable que pasar HTML escapado por atributo).
  var infoIcon =
    '<button type="button" class="btn-info-pago" aria-label="Ver historial">' +
      '<i class="fa fa-circle-info"></i>' +
    '</button>';

  var tr =
    '<tr class="' + trClass + '" data-idx="' + idx + '"' +
        ' data-tipo-doc="'  + _escapeHtml(f.tipo_doc) + '"' +
        ' data-serie-num="' + _escapeHtml(f.serie_numero) + '"' +
        ' data-fecha-doc="' + _escapeHtml(f.fecha_emision) + '"' +
        ' data-importe-doc="' + importeDoc.toFixed(2) + '"' +
        ' data-saldo-pendiente="' + saldoPend.toFixed(2) + '"' +
        ' data-moneda="'    + moneda + '">' +
    '  <td class="text-center"><span class="num-linea">' + idx + '</span></td>' +
    '  <td class="mono-cell">' + _escapeHtml(f.serie_numero) + '</td>' +
    '  <td class="text-cell">' + _escapeHtml(f.fecha_emision) + '</td>' +
    '  <td class="num-cell text-cell">' + _fmt2(importeDoc) + '</td>' +
    '  <td class="text-center"><span class="moneda-tag ' + monedaClass + '">' + moneda + '</span></td>' +
    '  <td class="text-center">' + tCambioCell + '</td>' +
    '  <td><input type="date" class="form-control in-fecha-pago" value="' + hoy + '"></td>' +
    '  <td><input type="number" class="form-control text-center in-numero-pago" min="1" max="999" step="1" value="' + siguienteNumPago + '" title="Número / cuota de pago"></td>' +
    '  <td>' +
    '    <div class="pago-cell">' +
    '      <input type="number" class="form-control text-end in-importe-pago" step="0.01" min="0.01" max="' + importePagoMax + '" value="' + importePagoDefault + '">' +
    '      ' + infoIcon +
    '    </div>' +
    '  </td>' +
    '  <td class="num-cell out-retenido fw-bold">S/ 0.00</td>' +
    '  <td class="num-cell out-neto">S/ 0.00</td>' +
    '  <td class="text-center">' +
    '    <button type="button" class="btn btn-sm btn-link text-danger btn-eliminar-linea" title="Quitar"><i class="fa fa-xmark"></i></button>' +
    '  </td>' +
    '</tr>';

  var $tr = $(tr);
  // El popover toma el HTML desde .data() — asi NUNCA pisamos atributos
  // ni dependemos de Bootstrap Tooltip (no expuesto en window).
  $tr.find('.btn-info-pago').data('info-html', tooltipHtml);
  $('#detalles_body').append($tr);
  $('#vacio_msg').hide();
  $('#totales_foot').removeClass('d-none');

  recalcularTodo();
}

/* ─── Popover de historial (independiente de Bootstrap Tooltip) ─────── */

var _infoPopover = null;   // { el, btn } | null

function _closeInfoPopover() {
  if (_infoPopover) {
    _infoPopover.el.remove();
    _infoPopover = null;
  }
}

function _showInfoPopover(btn, html) {
  _closeInfoPopover();

  var pop = document.createElement('div');
  pop.className = 'info-pago-popover';
  pop.innerHTML = '<div class="info-pago-popover-arrow"></div>' + html;
  document.body.appendChild(pop);

  // Posicionar al costado IZQUIERDO de la celda. Si no entra, volteamos a la derecha.
  var r  = btn.getBoundingClientRect();
  var pr = pop.getBoundingClientRect();
  var GAP = 10;
  var top = window.scrollY + r.top + (r.height / 2) - (pr.height / 2);
  var leftFlip = window.scrollX + r.right + GAP;
  var leftDef  = window.scrollX + r.left  - pr.width - GAP;

  var placement = 'left';
  var left = leftDef;
  if (leftDef < window.scrollX + 8) { left = leftFlip; placement = 'right'; }

  // Clamp vertical para no salir del viewport
  var minTop = window.scrollY + 8;
  var maxTop = window.scrollY + window.innerHeight - pr.height - 8;
  if (top < minTop) top = minTop;
  if (top > maxTop) top = maxTop;

  pop.classList.add('pop-' + placement);
  pop.style.top  = top  + 'px';
  pop.style.left = left + 'px';

  // Animacion de entrada en el siguiente frame
  requestAnimationFrame(function () { pop.classList.add('is-open'); });

  _infoPopover = { el: pop, btn: btn };
}

// Click en el icono: abre / cierra
$(document).on('click', '.btn-info-pago', function (e) {
  e.stopPropagation();
  var btn  = this;
  var html = $(btn).data('info-html');
  if (!html) return;

  if (_infoPopover && _infoPopover.btn === btn) {
    _closeInfoPopover();
    return;
  }
  _showInfoPopover(btn, html);
});

// Click fuera -> cerrar (excepto dentro del popover mismo)
$(document).on('click', function (e) {
  if (!_infoPopover) return;
  if (e.target.closest && e.target.closest('.info-pago-popover')) return;
  _closeInfoPopover();
});

// Scroll / resize / Escape -> cerrar
$(window).on('scroll resize', _closeInfoPopover);
$(document).on('keydown', function (e) { if (e.key === 'Escape') _closeInfoPopover(); });

/* Si el usuario quita una factura, renumera y oculta tfoot si quedó vacío */
$(document).on('click', '.btn-eliminar-linea', function () {
  var $tr = $(this).closest('tr');
  // Si el popover abierto pertenece a esta fila, ciérralo antes de borrar.
  if (_infoPopover && $.contains($tr.get(0), _infoPopover.btn)) {
    _closeInfoPopover();
  }
  $tr.remove();
  $('#detalles_body tr').each(function (i) { $(this).find('.num-linea').text(i + 1); });
  if ($('#detalles_body tr').length === 0) {
    $('#vacio_msg').show();
    $('#totales_foot').addClass('d-none');
  }
  recalcularTodo();
});

/* Recálculo automático cuando cambia importe pago o factor de cambio */
$(document).on('input change', '.in-importe-pago, .in-factor-cambio', recalcularTodo);

/* Limita el TC a 2 decimales mientras el usuario tipea (sin esperar al blur) */
$(document).on('input', '.in-factor-cambio', function () {
  RetUtils.clampDecimalsOnInput(this, RetUtils.TC_DECIMALS);
});

/* Limita el importe pago a 2 decimales (consistencia con TC) */
$(document).on('input', '.in-importe-pago', function () {
  RetUtils.clampDecimalsOnInput(this, RetUtils.DECIMALS);
});

/* Si cambia el importe del documento (solo aplica a futuro/manual; las API
   son read-only) — dejamos el handler por si se reusa más adelante. */

/* ─── Recálculo ──────────────────────────────────────────────────────── */
/* (parseo y formateo vienen de RetUtils — utils.js) */

function recalcularTodo() {
  var totPagPEN = 0, totRetPEN = 0, totNetoPEN = 0;
  var saldosHtml = '';

  $('#detalles_body tr').each(function () {
    var $tr = $(this);
    // attr() es más confiable que data() — data() cachea y a veces queda stale
    var moneda = ($tr.attr('data-moneda') || 'PEN').toUpperCase();
    var factor = (moneda === 'USD')
      ? _parseNum($tr.find('.in-factor-cambio').val())
      : 1.0;
    if (!factor) factor = (moneda === 'USD') ? 0 : 1.0;

    var pagoOri = _parseNum($tr.find('.in-importe-pago').val());
    // Fallback: si el input quedó vacío pero el data-importe-doc tiene valor,
    // usamos el importe del documento (caso de carga inicial)
    if (!pagoOri) pagoOri = _parseNum($tr.attr('data-importe-doc'));

    var pagoPEN = +(pagoOri * factor).toFixed(2);
    var retPEN  = +(pagoPEN * TASA_DEC).toFixed(2);
    var netoPEN = +(pagoPEN - retPEN).toFixed(2);

    $tr.find('.out-retenido').text('S/ ' + _fmt2(retPEN));
    $tr.find('.out-neto').text('S/ ' + _fmt2(netoPEN));

    // Card del panel de saldos (lugar estrategico fuera de la tabla)
    var saldoPend = _parseNum($tr.attr('data-saldo-pendiente'));
    var sym       = (moneda === 'USD') ? 'US$' : 'S/';
    var sn        = $tr.attr('data-serie-num') || '—';
    var stateCls, stateTxt, stateIco = '';
    var excede = false;
    if (saldoPend > 0 && pagoOri > 0) {
      var queda = +(saldoPend - pagoOri).toFixed(2);
      if (queda <= 0.005 && queda >= -0.005) {
        stateCls = 'full';
        stateIco = '<i class="fa fa-circle-check"></i>';
        stateTxt = 'Cubre todo el saldo (' + sym + ' ' + _fmt2(saldoPend) + ')';
      } else if (pagoOri > saldoPend + 0.005) {
        stateCls = 'exceed';
        stateIco = '<i class="fa fa-triangle-exclamation"></i>';
        stateTxt = 'Excede el saldo (' + sym + ' ' + _fmt2(saldoPend) + ')';
        excede = true;
      } else {
        stateCls = 'partial';
        stateTxt = 'Pagando ' + sym + ' ' + _fmt2(pagoOri) +
                   ' · queda ' + sym + ' ' + _fmt2(queda);
      }
    } else {
      stateCls = 'partial';
      stateTxt = 'Sin importe';
    }

    // Marca visual en el input cuando excede el saldo
    $tr.find('.in-importe-pago').toggleClass('is-invalid', excede);

    saldosHtml +=
      '<div class="saldo-card saldo-' + stateCls + '">' +
        '<span class="serie">' + _escapeHtml(sn) + '</span>' +
        '<span class="status">' + stateIco + ' ' + stateTxt + '</span>' +
      '</div>';

    totPagPEN += pagoPEN; totRetPEN += retPEN; totNetoPEN += netoPEN;
  });

  $('#tot_pagado').text('S/ ' + _fmt2(totPagPEN));
  $('#tot_retenido').text('S/ ' + _fmt2(totRetPEN));
  $('#tot_neto').text('S/ ' + _fmt2(totNetoPEN));

  // Pintar/ocultar el panel de saldos
  var $panel = $('#saldos_panel');
  if (saldosHtml) {
    $panel.html(saldosHtml).removeClass('d-none');
  } else {
    $panel.empty().addClass('d-none');
  }
}

/* ─── Registrar ──────────────────────────────────────────────────────── */

$(document).on('click', '#btn_registrar', function () {
  if (!$('#proveedor_ruc').val() || $('#proveedor_ruc').val().length !== 11) {
    Swal.fire({ html: 'Selecciona un proveedor válido.', icon: 'warning' }); return;
  }
  if (!$('#serie').val() || !$('#numero').val()) {
    Swal.fire({ html: 'No hay serie configurada. Configúrala desde el listado de retenciones.', icon: 'warning' }); return;
  }
  if ($('#detalles_body tr').length === 0) {
    Swal.fire({ html: 'Agrega al menos un documento desde el buscador de facturas.', icon: 'warning' }); return;
  }

  var detallesValidos = true, razonInvalida = '';
  $('#detalles_body tr').each(function () {
    var $tr = $(this);
    var pag = RetUtils.parseNum($tr.find('.in-importe-pago').val());
    var moneda = ($tr.attr('data-moneda') || 'PEN').toUpperCase();
    var factor = (moneda === 'USD')
      ? RetUtils.parseNum($tr.find('.in-factor-cambio').val())
      : 1.0;
    var saldo = RetUtils.parseNum($tr.attr('data-saldo-pendiente'));
    var sn    = $tr.attr('data-serie-num') || '';
    var sym   = (moneda === 'USD') ? 'US$' : 'S/';
    if (pag <= 0) {
      detallesValidos = false;
      razonInvalida = 'Cada línea necesita Importe Pago > 0.';
    }
    if (saldo > 0 && pag > saldo + 0.005) {
      detallesValidos = false;
      razonInvalida = 'El importe pago de <b>' + sn + '</b> excede el saldo pendiente (' + sym + ' ' + RetUtils.fmtNum(saldo, 2) + ').';
    }
    if (moneda === 'USD' && (!factor || factor <= 0)) {
      detallesValidos = false;
      razonInvalida = 'Las líneas en USD necesitan un factor de tipo de cambio > 0.';
    }
  });
  if (!detallesValidos) { Swal.fire({ html: razonInvalida, icon: 'warning' }); return; }

  // ── Resumen detallado para el swal de confirmacion ──
  var lineasHtml = '';
  var totRetPEN = 0, totNetoPEN = 0, totPagPEN = 0;
  var hayParciales = 0;

  $('#detalles_body tr').each(function () {
    var $tr   = $(this);
    var moneda = ($tr.attr('data-moneda') || 'PEN').toUpperCase();
    var factor = (moneda === 'USD')
      ? RetUtils.parseNum($tr.find('.in-factor-cambio').val())
      : 1.0;
    if (!factor) factor = 1.0;

    var pagoOri  = RetUtils.parseNum($tr.find('.in-importe-pago').val());
    var pagoPEN  = +(pagoOri * factor).toFixed(2);
    var retPEN   = +(pagoPEN * TASA_DEC).toFixed(2);
    var netoPEN  = +(pagoPEN - retPEN).toFixed(2);
    var saldoPnd = RetUtils.parseNum($tr.attr('data-saldo-pendiente'));
    var queda    = +(saldoPnd - pagoOri).toFixed(2);
    var sym      = (moneda === 'USD') ? 'US$' : 'S/';
    var sn       = $tr.attr('data-serie-num') || '—';
    var nro      = $tr.find('.in-numero-pago').val() || '1';

    var estadoBadge = '';
    if (queda > 0.005) {
      hayParciales++;
      estadoBadge = '<span class="sw-pill sw-pill-warn">Queda ' + sym + ' ' + RetUtils.fmtNum(queda, 2) + '</span>';
    } else {
      estadoBadge = '<span class="sw-pill sw-pill-ok">✓ Saldado</span>';
    }

    lineasHtml +=
      '<tr>' +
        '<td class="sw-mono">' + sn + '</td>' +
        '<td class="text-center">' + parseInt(nro, 10) + '</td>' +
        '<td class="text-end">' + sym + ' ' + RetUtils.fmtNum(pagoOri, 2) + '</td>' +
        '<td class="text-end sw-ret">S/ ' + RetUtils.fmtNum(retPEN, 2) + '</td>' +
        '<td>' + estadoBadge + '</td>' +
      '</tr>';

    totPagPEN  += pagoPEN;
    totRetPEN  += retPEN;
    totNetoPEN += netoPEN;
  });

  var seriePill = $('#serie').val() + '-' + $('#numero').val();
  var prov      = $('#proveedor_razon').val() || '—';
  var rucProv   = $('#proveedor_ruc').val() || '';
  var fechaEm   = $('#fecha_emision').val() || '';

  var avisoParcial = hayParciales > 0
    ? '<div class="sw-warn-block">'
      + '<i class="fa fa-circle-info"></i> '
      + '<b>' + hayParciales + '</b> documento(s) quedan con <b>saldo pendiente</b>. '
      + 'Podrás retener el resto en una próxima retención.'
      + '</div>'
    : '';

  var resumenHtml =
    '<div class="sw-resumen">' +
      '<div class="sw-head">' +
        '<div class="sw-serie">' + seriePill + '</div>' +
        '<div class="sw-meta">' +
          '<span><b>Proveedor:</b> ' + _escapeHtml(prov) + (rucProv ? ' <span class="sw-mono">(' + rucProv + ')</span>' : '') + '</span><br>' +
          '<span><b>Fecha emisión:</b> ' + fechaEm + ' · <b>Tasa:</b> ' + TASA_PCT.toFixed(2).replace(/\.?0+$/, '') + '%</span>' +
        '</div>' +
      '</div>' +
      '<table class="sw-table">' +
        '<thead><tr>' +
          '<th>Documento</th><th class="text-center">N° Pago</th>' +
          '<th class="text-end">Importe</th><th class="text-end">Retiene</th>' +
          '<th>Estado</th>' +
        '</tr></thead>' +
        '<tbody>' + lineasHtml + '</tbody>' +
      '</table>' +
      '<div class="sw-totales">' +
        '<div><span>Total pagado</span><b>S/ ' + RetUtils.fmtNum(totPagPEN, 2) + '</b></div>' +
        '<div class="sw-tot-ret"><span>Total a retener</span><b>S/ ' + RetUtils.fmtNum(totRetPEN, 2) + '</b></div>' +
        '<div><span>Neto al proveedor</span><b>S/ ' + RetUtils.fmtNum(totNetoPEN, 2) + '</b></div>' +
      '</div>' +
      avisoParcial +
    '</div>';

  Swal.fire({
    title: 'Confirmar Retención',
    html: resumenHtml,
    icon: null,
    showCancelButton: true,
    confirmButtonText: '<i class="fa fa-check"></i> Sí, registrar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#0D2E6E',
    width: '720px',
    customClass: { popup: 'sw-popup-retencion' },
  }).then(function (result) {
    if (!result.isConfirmed) return;

    var fd = new FormData();
    fd.append('_token', _token);
    fd.append('serie',                $('#serie').val());
    fd.append('numero',               $('#numero').val());
    fd.append('fecha_emision',        $('#fecha_emision').val());
    fd.append('numdocproveedor',      $('#proveedor_ruc').val());
    fd.append('tipodocproveedor',     '06');
    fd.append('direccionproveedor',   $('#proveedor_direccion').val() || '');
    fd.append('razonsocialproveedor', $('#proveedor_razon').val());
    fd.append('observacion',          $('[name="observacion"]').val() || '');

    $('#detalles_body tr').each(function (i) {
      var $tr = $(this);
      var moneda = $tr.data('moneda') || 'PEN';
      var factor = (moneda === 'USD')
        ? ($tr.find('.in-factor-cambio').val() || '0')
        : '1.0';
      var p = 'detalles[' + i + ']';
      fd.append(p + '[tipo_doc_rel]',  $tr.data('tipo-doc'));
      fd.append(p + '[serie_num_rel]', $tr.data('serie-num'));
      fd.append(p + '[fecha_doc_rel]', $tr.data('fecha-doc'));
      fd.append(p + '[importe_doc]',   $tr.data('importe-doc') || 0);
      fd.append(p + '[moneda_doc]',    moneda);
      fd.append(p + '[fecha_pago]',    $tr.find('.in-fecha-pago').val());
      fd.append(p + '[numero_pago]',   $tr.find('.in-numero-pago').val() || (i + 1));
      fd.append(p + '[importe_pago]',  $tr.find('.in-importe-pago').val());
      fd.append(p + '[moneda_pago]',   moneda);
      fd.append(p + '[factor_cambio]', factor);
    });

    var BTN_RESTORE = '<i class="fa fa-check"></i> Registrar Retención';
    $('#btn_registrar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registrando…');

    $.ajax({
      type: 'POST', url: route('retenciones.store'), data: fd,
      processData: false, contentType: false, dataType: 'json',
      success: function (resp) {
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo }).then(function () {
          if (resp.procede) window.location.href = route('retenciones.index');
          else $('#btn_registrar').prop('disabled', false).html(BTN_RESTORE);
        });
      },
      error: function (xhr) {
        var resp = xhr.responseJSON || { msj: 'Error al registrar', msj_tipo: 'error' };
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo || 'error' });
        $('#btn_registrar').prop('disabled', false).html(BTN_RESTORE);
      },
    });
  });
});
