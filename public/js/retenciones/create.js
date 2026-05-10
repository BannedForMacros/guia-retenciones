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
    seleccionarProveedor({
      ruc:       d.proveedor_ruc       || '',
      nombre:    d.proveedor_nombre    || '',
      direccion: d.proveedor_direccion || '',
    });
    // Limpiamos la selección visible — la info ya está en el chip.
    $('#proveedor_select').val(null).trigger('change');
  });

  $('#btn_limpiar_proveedor').on('click', limpiarProveedor);
});

/* ─── Selección / limpieza de proveedor ──────────────────────────────── */

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
            return $.extend({}, f, {
              id: f.serie_numero,
              text: f.serie_numero,
              disabled: !!f.ya_retenida,
            });
          });
        return { results: items };
      },
    },
    templateResult: function (f) {
      if (!f.id) return f.text;
      var importe = parseFloat(f.importe_total || 0).toFixed(2);
      var sym = (f.moneda === 'USD') ? 'US$' : 'S/';
      var tipoTxt = (TIPOS_DOC_REL.find(function (t) { return t.v === f.tipo_doc; }) || {}).t || f.tipo_doc;
      var clase = f.ya_retenida ? 'factura-result is-retenida' : 'factura-result';
      var badge = f.ya_retenida && f.retencion_serienumero
        ? '<span class="badge-retenida" title="Retenida el ' + (f.retencion_fecha || '') + '">Ya retenida · ' + f.retencion_serienumero + '</span>'
        : '';
      return $(
        '<div class="' + clase + '">' +
          '<div class="left">' +
            '<div class="top">' +
              '<span class="tipo-tag">' + f.tipo_doc + ' ' + tipoTxt + '</span>' +
              '<span class="serie">' + f.serie_numero + '</span>' +
              badge +
            '</div>' +
            '<div class="meta">Emitida: ' + f.fecha_emision + '</div>' +
          '</div>' +
          '<div class="importe">' + sym + ' ' + importe + '</div>' +
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
    if (f.ya_retenida) return;
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
  var moneda     = f.moneda || 'PEN';
  var esUsd      = moneda === 'USD';

  // Toda la data del datamarket se guarda en data-* del <tr>; el form lo
  // usa al hacer submit. Las celdas son TEXTO (no editable).
  // Para USD: TC referencial (RetUtils.TC_DEFAULT) con 2 decimales — editable.
  var tcDefault = RetUtils.TC_DEFAULT.toFixed(RetUtils.TC_DECIMALS); // "3.75"
  var tCambioCell = esUsd
    ? '<input type="number" class="form-control text-end in-factor-cambio" step="0.01" min="0.01" value="' + tcDefault + '" title="Tipo de cambio PEN por USD (editable, máx. 2 decimales)">'
    : '<span class="text-muted">1.00</span>';

  var trClass = esUsd ? 'is-usd' : '';
  var monedaClass = esUsd ? 'is-usd' : 'is-pen';

  var tr =
    '<tr class="' + trClass + '" data-idx="' + idx + '"' +
        ' data-tipo-doc="'  + _escapeHtml(f.tipo_doc) + '"' +
        ' data-serie-num="' + _escapeHtml(f.serie_numero) + '"' +
        ' data-fecha-doc="' + _escapeHtml(f.fecha_emision) + '"' +
        ' data-importe-doc="' + importeDoc.toFixed(2) + '"' +
        ' data-moneda="'    + moneda + '">' +
    '  <td class="text-center"><span class="num-linea">' + idx + '</span></td>' +
    '  <td class="mono-cell">' + _escapeHtml(f.serie_numero) + '</td>' +
    '  <td class="text-cell">' + _escapeHtml(f.fecha_emision) + '</td>' +
    '  <td class="num-cell text-cell">' + _fmt2(importeDoc) + '</td>' +
    '  <td class="text-center"><span class="moneda-tag ' + monedaClass + '">' + moneda + '</span></td>' +
    '  <td class="text-center">' + tCambioCell + '</td>' +
    '  <td><input type="date" class="form-control in-fecha-pago" value="' + hoy + '"></td>' +
    '  <td><input type="number" class="form-control text-center in-numero-pago" min="1" max="999" step="1" value="1" title="Número / cuota de pago"></td>' +
    '  <td><input type="number" class="form-control text-end in-importe-pago" step="0.01" min="0" value="' + importeDoc.toFixed(2) + '"></td>' +
    '  <td class="num-cell out-retenido fw-bold">S/ 0.00</td>' +
    '  <td class="num-cell out-neto">S/ 0.00</td>' +
    '  <td class="text-center">' +
    '    <button type="button" class="btn btn-sm btn-link text-danger btn-eliminar-linea" title="Quitar"><i class="fa fa-xmark"></i></button>' +
    '  </td>' +
    '</tr>';

  $('#detalles_body').append(tr);
  $('#vacio_msg').hide();
  $('#totales_foot').removeClass('d-none');
  recalcularTodo();
}

/* Si el usuario quita una factura, renumera y oculta tfoot si quedó vacío */
$(document).on('click', '.btn-eliminar-linea', function () {
  $(this).closest('tr').remove();
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

    totPagPEN += pagoPEN; totRetPEN += retPEN; totNetoPEN += netoPEN;
  });

  $('#tot_pagado').text('S/ ' + _fmt2(totPagPEN));
  $('#tot_retenido').text('S/ ' + _fmt2(totRetPEN));
  $('#tot_neto').text('S/ ' + _fmt2(totNetoPEN));
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
    if (pag <= 0) {
      detallesValidos = false;
      razonInvalida = 'Cada línea necesita Importe Pago > 0.';
    }
    if (moneda === 'USD' && (!factor || factor <= 0)) {
      detallesValidos = false;
      razonInvalida = 'Las líneas en USD necesitan un factor de tipo de cambio > 0.';
    }
  });
  if (!detallesValidos) { Swal.fire({ html: razonInvalida, icon: 'warning' }); return; }

  Swal.fire({
    html: '¿Registrar la retención <b>' + $('#serie').val() + '-' + $('#numero').val() + '</b>?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, registrar',
    cancelButtonText: 'Cancelar',
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
    fd.append('observacion',          $('input[name="observacion"]').val() || '');

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
