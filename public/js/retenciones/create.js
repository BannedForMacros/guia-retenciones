/* Retenciones – Crear (refactor: serie/numero del backend, sin tasa/régimen
 * editables, proveedor como chip, buscador de facturas inline). */

/* ── Constantes ──────────────────────────────────────────────────────── */
var TASA_PCT = 3.00; // hardcoded: régimen general SUNAT
var TASA_DEC = TASA_PCT / 100;

var TIPOS_DOC_REL = [
  { v: '01', t: 'Factura' },
  { v: '07', t: 'Nota de Crédito' },
  { v: '08', t: 'Nota de Débito' },
];

var contadorLineas = 0;

/* ── Helpers ────────────────────────────────────────────────────────── */
function _escapeHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
  });
}
function _initial(name) {
  var s = String(name || '').trim();
  return s ? s.charAt(0).toUpperCase() : '·';
}

$(document).ready(function () {

  /* ────────── Buscador de PROVEEDORES (Select2 inline) ──────────── */
  $('#proveedor_select').select2({
    theme: 'bootstrap-5',
    placeholder: 'Buscar proveedor (RUC o razón social)…',
    minimumInputLength: 2,
    allowClear: false,
    dropdownAutoWidth: true,
    ajax: {
      url: route('retenciones.listarProveedores'),
      type: 'GET',
      dataType: 'json',
      delay: 250,
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
    // Limpiamos la selección visible del Select2 — la información ya está en el chip.
    $('#proveedor_select').val(null).trigger('change');
  });

  $('#btn_limpiar_proveedor').on('click', limpiarProveedor);

  /* ────────── Línea manual ──────────────────────────────────────── */
  $('#btn_agregar_linea').on('click', function () {
    if (!$('#proveedor_ruc').val()) {
      Swal.fire({ html: 'Primero selecciona un proveedor.', icon: 'warning' });
      return;
    }
    agregarLinea();
  });
});

/* ─── Selección / limpieza de proveedor ──────────────────────────── */

function seleccionarProveedor(p) {
  // Persistimos en hidden inputs para que el form los envíe en submit.
  $('#proveedor_ruc').val(p.ruc);
  $('#proveedor_razon').val((p.nombre || '').toUpperCase());
  $('#proveedor_direccion').val(p.direccion);

  // Pintar chip
  $('#prov_avatar').text(_initial(p.nombre));
  $('#prov_name').text((p.nombre || '').toUpperCase());
  $('#prov_ruc').text(p.ruc);
  $('#prov_addr').text(p.direccion || '—');
  $('#proveedor_chip').removeClass('d-none');
  $('#proveedor_empty').addClass('d-none');

  // Habilitar buscador de facturas
  inicializarSelectorFacturas(p.ruc);
}

function limpiarProveedor() {
  $('#proveedor_ruc').val('');
  $('#proveedor_razon').val('');
  $('#proveedor_direccion').val('');
  $('#proveedor_chip').addClass('d-none');
  $('#proveedor_empty').removeClass('d-none');

  // Vaciar líneas (eran del proveedor anterior)
  $('#detalles_body').empty();
  $('#vacio_msg').show();
  destruirSelectorFacturas();
  recalcularTodo();
}

/* ─── Selector de facturas pendientes (Select2 inline) ───────────── */

function destruirSelectorFacturas() {
  var $sel = $('#factura_select');
  if ($sel.data('select2')) $sel.select2('destroy');
  $sel.empty().prop('disabled', true);
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
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { ruc: rucProveedor, q: params.term || '', solo_disponibles: 0, limit: 50 };
      },
      processResults: function (data) {
        // Excluimos las series ya agregadas en la tabla del form.
        var yaEnForm = {};
        $('#detalles_body tr').each(function () {
          var sn = ($(this).find('.in-serie-num').val() || '').trim();
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
      var html =
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
        '</div>';
      return $(html);
    },
    /* IMPORTANTE: el buscador es solo para buscar; nunca muestra una selección
       persistente. Una vez elegida la factura, se agrega como línea y se
       limpia el Select2 para el siguiente pick. */
    templateSelection: function (f) {
      return f.id ? f.text : 'Buscar factura del proveedor…';
    },
    escapeMarkup: function (m) { return m; },
  });

  $('#factura_select').on('select2:select', function (e) {
    var f = e.params.data;
    if (f.ya_retenida) return;
    agregarLineaConFactura(f);
    // Reset del buscador para encadenar selecciones.
    $('#factura_select').val(null).trigger('change');
  });
}

/* ─── Líneas de detalle ───────────────────────────────────────────── */

function agregarLineaConFactura(f) {
  agregarLinea();
  var $tr = $('#detalles_body tr').last();
  $tr.find('.in-tipo-doc').val(f.tipo_doc);
  $tr.find('.in-serie-num').val(f.serie_numero);
  $tr.find('.in-fecha-doc').val(f.fecha_emision);
  $tr.find('.in-importe-doc').val(parseFloat(f.importe_total || 0).toFixed(2));
  $tr.find('.in-importe-pago').val(parseFloat(f.importe_total || 0).toFixed(2));
  $tr.find('.in-moneda').val(f.moneda || 'PEN').trigger('change');
  recalcularTodo();
}

function agregarLinea() {
  contadorLineas++;
  var idx = contadorLineas;
  var optsTipoDoc = TIPOS_DOC_REL.map(function (o) {
    return '<option value="' + o.v + '">' + o.v + ' - ' + o.t + '</option>';
  }).join('');
  var hoy = new Date().toISOString().slice(0, 10);

  var tr =
    '<tr data-idx="' + idx + '">' +
    '  <td class="text-center"><span class="num-linea">' + idx + '</span></td>' +
    '  <td><select class="form-select form-select-sm in-tipo-doc">' + optsTipoDoc + '</select></td>' +
    '  <td><input type="text" class="form-control form-control-sm in-serie-num font-monospace" placeholder="F001-00000001" maxlength="15"></td>' +
    '  <td><input type="date" class="form-control form-control-sm in-fecha-doc" value="' + hoy + '"></td>' +
    '  <td><input type="number" class="form-control form-control-sm text-end in-importe-doc" step="0.01" min="0" value="0.00"></td>' +
    '  <td><select class="form-select form-select-sm in-moneda">' +
    '    <option value="PEN" selected>PEN</option><option value="USD">USD</option></select></td>' +
    '  <td><input type="number" class="form-control form-control-sm text-end in-factor-cambio" step="0.0001" min="0.0001" value="1.0000" disabled title="Factor PEN por unidad de moneda original"></td>' +
    '  <td><input type="date" class="form-control form-control-sm in-fecha-pago" value="' + hoy + '"></td>' +
    '  <td><input type="number" class="form-control form-control-sm text-end in-importe-pago" step="0.01" min="0" value="0.00"></td>' +
    '  <td class="text-end out-retenido fw-bold text-success">S/ 0.00</td>' +
    '  <td class="text-end out-neto">S/ 0.00</td>' +
    '  <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger btn-eliminar-linea" title="Eliminar"><i class="fa fa-trash"></i></button></td>' +
    '</tr>';

  $('#detalles_body').append(tr);
  $('#vacio_msg').hide();
  recalcularTodo();
}

$(document).on('click', '.btn-eliminar-linea', function () {
  $(this).closest('tr').remove();
  renumerar();
  if ($('#detalles_body tr').length === 0) $('#vacio_msg').show();
  recalcularTodo();
});

$(document).on('input change', '.in-importe-pago, .in-factor-cambio', recalcularTodo);

$(document).on('change', '.in-moneda', function () {
  var $tr = $(this).closest('tr');
  var $factor = $tr.find('.in-factor-cambio');
  if ($(this).val() === 'USD') {
    $factor.prop('disabled', false).val('').attr('placeholder', 'ej. 3.7500').focus();
  } else {
    $factor.prop('disabled', true).val('1.0000');
  }
  recalcularTodo();
});

$(document).on('change', '.in-importe-doc', function () {
  var $tr = $(this).closest('tr');
  var pago = parseFloat($tr.find('.in-importe-pago').val() || 0);
  if (!pago) {
    $tr.find('.in-importe-pago').val(parseFloat($(this).val() || 0).toFixed(2));
    recalcularTodo();
  }
});

function renumerar() {
  $('#detalles_body tr').each(function (i) { $(this).find('.num-linea').text(i + 1); });
}

function recalcularTodo() {
  var totPagPEN = 0, totRetPEN = 0, totNetoPEN = 0;
  $('#detalles_body tr').each(function () {
    var $tr     = $(this);
    var moneda  = $tr.find('.in-moneda').val() || 'PEN';
    var factor  = moneda === 'USD' ? parseFloat($tr.find('.in-factor-cambio').val() || 0) : 1.0;
    var pagoOri = parseFloat($tr.find('.in-importe-pago').val() || 0);
    var pagoPEN = +(pagoOri * factor).toFixed(2);
    var retPEN  = +(pagoPEN * TASA_DEC).toFixed(2);
    var netoPEN = +(pagoPEN - retPEN).toFixed(2);

    $tr.find('.out-retenido').text('S/ ' + retPEN.toFixed(2));
    $tr.find('.out-neto').text('S/ ' + netoPEN.toFixed(2));

    totPagPEN += pagoPEN; totRetPEN += retPEN; totNetoPEN += netoPEN;
  });
  var fmt = function (n) { return 'S/ ' + n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  $('#tot_pagado').text(fmt(totPagPEN));
  $('#tot_retenido').text(fmt(totRetPEN));
  $('#tot_neto').text(fmt(totNetoPEN));
}

/* ─── Registrar ──────────────────────────────────────────────────── */

$(document).on('click', '#btn_registrar', function () {
  if (!$('#proveedor_ruc').val() || $('#proveedor_ruc').val().length !== 11) {
    Swal.fire({ html: 'Selecciona un proveedor válido.', icon: 'warning' }); return;
  }
  if (!$('#serie').val() || !$('#numero').val()) {
    Swal.fire({ html: 'No hay serie configurada. Configúrala desde el listado de retenciones.', icon: 'warning' }); return;
  }
  if ($('#detalles_body tr').length === 0) {
    Swal.fire({ html: 'Agrega al menos una línea de detalle.', icon: 'warning' }); return;
  }

  var detallesValidos = true, razonInvalida = '';
  $('#detalles_body tr').each(function () {
    var $tr = $(this);
    var sn  = $tr.find('.in-serie-num').val();
    var pag = parseFloat($tr.find('.in-importe-pago').val() || 0);
    var moneda = $tr.find('.in-moneda').val() || 'PEN';
    var factor = parseFloat($tr.find('.in-factor-cambio').val() || 0);
    if (!sn || pag <= 0) {
      detallesValidos = false;
      razonInvalida = 'Cada línea necesita Serie-Número e Importe Pago > 0.';
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
    // NOTA: tasa y régimen ya no se envían — el backend usa los defaults SUNAT.

    $('#detalles_body tr').each(function (i) {
      var $tr = $(this);
      var p = 'detalles[' + i + ']';
      fd.append(p + '[tipo_doc_rel]',  $tr.find('.in-tipo-doc').val());
      fd.append(p + '[serie_num_rel]', $tr.find('.in-serie-num').val());
      fd.append(p + '[fecha_doc_rel]', $tr.find('.in-fecha-doc').val());
      fd.append(p + '[importe_doc]',   $tr.find('.in-importe-doc').val() || 0);
      fd.append(p + '[moneda_doc]',    $tr.find('.in-moneda').val());
      fd.append(p + '[fecha_pago]',    $tr.find('.in-fecha-pago').val());
      fd.append(p + '[importe_pago]',  $tr.find('.in-importe-pago').val());
      fd.append(p + '[moneda_pago]',   $tr.find('.in-moneda').val());
      fd.append(p + '[factor_cambio]', $tr.find('.in-factor-cambio').val() || '1.0');
    });

    $('#btn_registrar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registrando…');

    $.ajax({
      type: 'POST', url: route('retenciones.store'), data: fd,
      processData: false, contentType: false, dataType: 'json',
      success: function (resp) {
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo }).then(function () {
          if (resp.procede) window.location.href = route('retenciones.index');
          else $('#btn_registrar').prop('disabled', false).html('<i class="fa fa-save"></i> Registrar Retención');
        });
      },
      error: function (xhr) {
        var resp = xhr.responseJSON || { msj: 'Error al registrar', msj_tipo: 'error' };
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo || 'error' });
        $('#btn_registrar').prop('disabled', false).html('<i class="fa fa-save"></i> Registrar Retención');
      },
    });
  });
});
