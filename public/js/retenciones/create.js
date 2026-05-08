/* Retenciones - Crear */

/* ── Helpers ──────────────────────────────────────────────────────────── */
function _pad3(n) { n = String(n); while (n.length < 3) n = '0' + n; return n; }
function _escapeHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
  });
}

var TIPOS_DOC_REL = [
  { v: '01', t: 'Factura' },
  { v: '07', t: 'Nota de Credito' },
  { v: '08', t: 'Nota de Debito' },
  { v: '03', t: 'Boleta de Venta' },
  { v: '12', t: 'Recibo Honorarios' },
  { v: '14', t: 'Liq. de Compra' },
  { v: '99', t: 'Otros' },
];

var contadorLineas = 0;

$(document).ready(function () {

  /* Buscador de proveedores -> FastAPI (datamarket) via retenciones.listarProveedores */
  $('#proveedor_select').select2({
    theme: 'bootstrap-5',
    placeholder: 'Buscar por RUC o razon social...',
    minimumInputLength: 2,
    allowClear: true,
    ajax: {
      url: route('retenciones.listarProveedores'),
      type: 'GET',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { term: params.term, limit: 25 };
      },
      processResults: function (data) {
        return { results: data.items || [] };
      },
    },
  });

  $('#proveedor_select').on('select2:select', function (e) {
    var d = e.params.data;
    $('#proveedor_ruc').val(d.proveedor_ruc || '');
    $('#proveedor_razon').val((d.proveedor_nombre || '').toUpperCase());
    $('#proveedor_direccion').val(d.proveedor_direccion || '');
    inicializarSelectorFacturas(d.proveedor_ruc || '');
  });

  $('#proveedor_select').on('select2:clear', function () {
    $('#proveedor_ruc').val('');
    $('#proveedor_razon').val('');
    $('#proveedor_direccion').val('');
    destruirSelectorFacturas();
  });

  /* Recalcular cuando cambia la tasa */
  $('#tasa').on('input', recalcularTodo);

  /* Refrescar correlativo cuando cambia la serie (FastAPI -> SQL Server) */
  $('#serie').on('change', function () {
    var $opt = $(this).find(':selected');
    var numSerie = parseInt($opt.data('num-serie') || 0, 10);
    if (!numSerie) return;

    var fd = new FormData();
    fd.append('_token', _token);
    fd.append('num_serie', numSerie);

    $.ajax({
      type: 'POST',
      url: route('retenciones.siguienteNumero'),
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (resp) {
        $('#numero').val(resp.siguiente_numero || '00000001');
      },
    });
  });

  /* ── Modal Configuracion de Series ──────────────────────────────── */

  function cargarSeriesModal() {
    var $tbody = $('#series_body');
    var $err = $('#series_error').addClass('d-none').text('');
    $tbody.html('<tr><td colspan="4" class="text-center text-muted small py-3">Cargando...</td></tr>');
    $.ajax({ url: route('retenciones.listarSeries'), type: 'GET', dataType: 'json' })
      .done(function (resp) {
        if (!resp.procede) {
          $tbody.empty();
          $err.removeClass('d-none').text(resp.error || 'Error consultando series');
          return;
        }
        if (!resp.items.length) {
          $tbody.html('<tr><td colspan="4" class="text-center text-muted small py-3">Aun no hay series. Crea la primera ↗</td></tr>');
          repoblarDropdownSerie([]);
          return;
        }
        var html = '';
        resp.items.forEach(function (s) {
          var um  = (s.ultimo_valor === 0) ? '<span class="ultimo-cero">0</span>' : _escapeHtml(s.ultimo_valor);
          var umk = (s.ultimo_valor_market === 0) ? '<span class="ultimo-cero">0</span>' : _escapeHtml(s.ultimo_valor_market);
          html += '<tr>'
            + '<td><span class="serie-tag">' + _escapeHtml(s.serie_formateada) + '</span></td>'
            + '<td class="text-end">' + _escapeHtml(s.num_serie) + '</td>'
            + '<td class="text-end">' + um + '</td>'
            + '<td class="text-end">' + umk + '</td>'
            + '</tr>';
        });
        $tbody.html(html);
        repoblarDropdownSerie(resp.items);
      })
      .fail(function (xhr) {
        $tbody.empty();
        $err.removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.error) || ('HTTP ' + xhr.status));
      });
  }

  /* Reemplaza las options del select #serie del form preservando seleccion previa. */
  function repoblarDropdownSerie(items) {
    var $sel = $('#serie');
    var prevVal = $sel.val();
    $sel.empty();
    if (!items || !items.length) {
      $sel.append('<option value="" disabled selected>(no hay series — crear en config)</option>');
      return;
    }
    items.forEach(function (s) {
      var $opt = $('<option>')
        .val(s.serie_formateada)
        .attr('data-num-serie', s.num_serie)
        .attr('data-ultimo-valor', s.ultimo_valor)
        .text(s.serie_formateada);
      $sel.append($opt);
    });
    if (prevVal && $sel.find('option[value="' + prevVal + '"]').length) {
      $sel.val(prevVal);
    }
    $sel.trigger('change');
  }

  $('#num_serie').on('input', function () {
    var n = parseInt($(this).val() || 0, 10);
    $('#preview_serie').text((n && n >= 1) ? ('R' + _pad3(n)) : 'R???');
  });

  $('#btn_refresh_series').on('click', cargarSeriesModal);
  $('#modalSeries').on('shown.bs.modal', cargarSeriesModal);

  $('#form_crear_serie').on('submit', function (e) {
    e.preventDefault();
    var num = parseInt($('#num_serie').val() || 0, 10);
    if (!num || num < 1 || num > 9999) {
      Swal.fire({ html: 'Ingresa un numero de serie entre 1 y 9999.', icon: 'warning' });
      return;
    }
    var fd = new FormData();
    fd.append('_token', _token);
    fd.append('num_serie', num);
    var ctr = $('#ctr_resp').val();
    if (ctr) fd.append('ctr_resp', ctr);

    $('#btn_crear_serie').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creando...');

    $.ajax({
      url: route('retenciones.crearSerie'),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
    })
    .done(function (resp) {
      Swal.fire({ html: resp.msj, icon: resp.msj_tipo || 'success', timer: 1500, showConfirmButton: false });
      $('#num_serie').val('');
      $('#ctr_resp').val('');
      $('#preview_serie').text('R???');
      cargarSeriesModal();
    })
    .fail(function (xhr) {
      var resp = xhr.responseJSON || { msj: 'No se pudo crear la serie.', msj_tipo: 'error' };
      Swal.fire({ html: resp.msj, icon: resp.msj_tipo || 'error' });
    })
    .always(function () {
      $('#btn_crear_serie').prop('disabled', false).html('<i class="fa fa-plus"></i> Crear serie');
    });
  });
});

/* ── Selector de facturas pendientes (datamarket) ───────────────────── */

var destruirSelectorFacturas = function () {
  var $sel = $('#factura_select');
  if ($sel.data('select2')) $sel.select2('destroy');
  $sel.empty().prop('disabled', true);
};

var inicializarSelectorFacturas = function (rucProveedor) {
  destruirSelectorFacturas();
  if (!rucProveedor || rucProveedor.length !== 11) return;

  $('#factura_select').prop('disabled', false).select2({
    theme: 'bootstrap-5',
    placeholder: 'Buscar por serie-numero...',
    allowClear: true,
    minimumInputLength: 0,
    ajax: {
      url: route('retenciones.facturasProveedor'),
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          ruc: rucProveedor,
          q: params.term || '',
          solo_disponibles: 0,
          limit: 50,
        };
      },
      processResults: function (data) {
        // Series ya seleccionadas en el form actual: las ocultamos del dropdown.
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
      var tipoTxt = TIPOS_DOC_REL.find(function (t) { return t.v === f.tipo_doc; });
      tipoTxt = tipoTxt ? tipoTxt.t : f.tipo_doc;
      var clase = f.ya_retenida ? 'factura-result is-retenida' : 'factura-result';
      var badge = f.ya_retenida && f.retencion_serienumero
        ? '<span class="badge-retenida" title="Retenida el ' + (f.retencion_fecha || '') + '">Ya retenida · ' + f.retencion_serienumero + '</span>'
        : '';
      var html =
        '<div class="' + clase + '">' +
        '  <div class="left">' +
        '    <div class="top">' +
        '      <span class="tipo-tag">' + f.tipo_doc + ' ' + tipoTxt + '</span>' +
        '      <span class="serie">' + f.serie_numero + '</span>' +
        badge +
        '    </div>' +
        '    <div class="meta">Emitida: ' + f.fecha_emision + '</div>' +
        '  </div>' +
        '  <div class="importe">' + sym + ' ' + importe + '</div>' +
        '</div>';
      return $(html);
    },
    templateSelection: function (f) {
      if (!f.id) return 'Buscar por serie-numero...';
      return f.tipo_doc + ' ' + f.serie_numero;
    },
    escapeMarkup: function (m) { return m; },
  });

  $('#factura_select').on('select2:select', function (e) {
    var f = e.params.data;
    if (f.ya_retenida) return;        // hard-stop por si Select2 dejara pasar
    agregarLineaConFactura(f);
    // limpiamos para permitir agregar mas facturas en cadena
    $('#factura_select').val(null).trigger('change');
  });
};

/* Agrega una linea pre-rellenada con los datos que vinieron del datamarket. */
var agregarLineaConFactura = function (f) {
  agregarLinea();
  var $tr = $('#detalles_body tr').last();

  $tr.find('.in-tipo-doc').val(f.tipo_doc);
  $tr.find('.in-serie-num').val(f.serie_numero);
  $tr.find('.in-fecha-doc').val(f.fecha_emision);
  $tr.find('.in-importe-doc').val(parseFloat(f.importe_total || 0).toFixed(2));
  $tr.find('.in-importe-pago').val(parseFloat(f.importe_total || 0).toFixed(2));
  var $moneda = $tr.find('.in-moneda').val(f.moneda || 'PEN');
  // dispara el handler de moneda para enabled/disabled del factor de cambio
  $moneda.trigger('change');
  recalcularTodo();
};

/* ── Lineas de detalle ───────────────────────────────────────────────── */

$(document).on('click', '#btn_agregar_linea', function () {
  if (!$('#proveedor_ruc').val()) {
    Swal.fire({ html: 'Primero selecciona un proveedor.', icon: 'warning' });
    return;
  }
  agregarLinea();
});

var agregarLinea = function () {
  contadorLineas++;
  var idx = contadorLineas;

  var optsTipoDoc = TIPOS_DOC_REL.map(function (o) {
    return '<option value="' + o.v + '">' + o.v + ' - ' + o.t + '</option>';
  }).join('');

  var hoy = new Date().toISOString().slice(0, 10);

  var tr = ''
    + '<tr data-idx="' + idx + '">'
    + '  <td class="text-center"><span class="num-linea">' + idx + '</span></td>'
    + '  <td><select class="form-select form-select-sm in-tipo-doc">' + optsTipoDoc + '</select></td>'
    + '  <td><input type="text" class="form-control form-control-sm in-serie-num font-monospace" placeholder="F001-00000001" maxlength="15"></td>'
    + '  <td><input type="date" class="form-control form-control-sm in-fecha-doc" value="' + hoy + '"></td>'
    + '  <td><input type="number" class="form-control form-control-sm text-end in-importe-doc" step="0.01" min="0" value="0.00"></td>'
    + '  <td>'
    + '    <select class="form-select form-select-sm in-moneda">'
    + '      <option value="PEN" selected>PEN</option>'
    + '      <option value="USD">USD</option>'
    + '    </select>'
    + '  </td>'
    + '  <td>'
    + '    <input type="number" class="form-control form-control-sm text-end in-factor-cambio" step="0.0001" min="0.0001" value="1.0000" disabled title="Factor PEN por unidad de moneda original">'
    + '  </td>'
    + '  <td><input type="date" class="form-control form-control-sm in-fecha-pago" value="' + hoy + '"></td>'
    + '  <td><input type="number" class="form-control form-control-sm text-end in-importe-pago" step="0.01" min="0" value="0.00"></td>'
    + '  <td class="text-end out-retenido fw-bold text-success">S/ 0.00</td>'
    + '  <td class="text-end out-neto">S/ 0.00</td>'
    + '  <td class="text-center">'
    + '    <button type="button" class="btn btn-sm btn-link text-danger btn-eliminar-linea" title="Eliminar"><i class="fa fa-trash"></i></button>'
    + '  </td>'
    + '</tr>';

  $('#detalles_body').append(tr);
  $('#vacio_msg').hide();
  recalcularTodo();
};

$(document).on('click', '.btn-eliminar-linea', function () {
  $(this).closest('tr').remove();
  renumerar();
  if ($('#detalles_body tr').length === 0) $('#vacio_msg').show();
  recalcularTodo();
});

$(document).on('input change', '.in-importe-pago, .in-factor-cambio', function () {
  recalcularTodo();
});

/* Cambio de moneda: habilita/deshabilita factor de cambio */
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

/* Si cambia el importe del documento y el pago esta en 0, lo igualamos */
$(document).on('change', '.in-importe-doc', function () {
  var $tr = $(this).closest('tr');
  var pago = parseFloat($tr.find('.in-importe-pago').val() || 0);
  if (!pago) {
    $tr.find('.in-importe-pago').val(parseFloat($(this).val() || 0).toFixed(2));
    recalcularTodo();
  }
});

var renumerar = function () {
  $('#detalles_body tr').each(function (i) {
    $(this).find('.num-linea').text(i + 1);
  });
};

var recalcularTodo = function () {
  var tasaPct = parseFloat($('#tasa').val() || 0);
  var tasaDec = tasaPct / 100;

  var totPagPEN = 0, totRetPEN = 0, totNetoPEN = 0;

  $('#detalles_body tr').each(function () {
    var $tr     = $(this);
    var moneda  = $tr.find('.in-moneda').val() || 'PEN';
    var factor  = moneda === 'USD'
                    ? parseFloat($tr.find('.in-factor-cambio').val() || 0)
                    : 1.0;
    var pagoOri = parseFloat($tr.find('.in-importe-pago').val() || 0);
    var pagoPEN = +(pagoOri * factor).toFixed(2);
    var retPEN  = +(pagoPEN * tasaDec).toFixed(2);
    var netoPEN = +(pagoPEN - retPEN).toFixed(2);

    $tr.find('.out-retenido').text('S/ ' + retPEN.toFixed(2));
    $tr.find('.out-neto').text('S/ ' + netoPEN.toFixed(2));

    totPagPEN  += pagoPEN;
    totRetPEN  += retPEN;
    totNetoPEN += netoPEN;
  });

  $('#tot_pagado').text('S/ ' + totPagPEN.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  $('#tot_retenido').text('S/ ' + totRetPEN.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  $('#tot_neto').text('S/ ' + totNetoPEN.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
};

/* ── Registrar ───────────────────────────────────────────────────────── */

$(document).on('click', '#btn_registrar', function () {

  // Validacion previa
  if (!$('#proveedor_ruc').val() || $('#proveedor_ruc').val().length !== 11) {
    Swal.fire({ html: 'Selecciona un proveedor valido.', icon: 'warning' }); return;
  }
  if (!$('#numero').val()) {
    Swal.fire({ html: 'Ingresa el numero.', icon: 'warning' }); return;
  }
  if ($('#detalles_body tr').length === 0) {
    Swal.fire({ html: 'Agrega al menos una linea de detalle.', icon: 'warning' }); return;
  }

  var detallesValidos = true;
  var razonInvalida = '';
  $('#detalles_body tr').each(function () {
    var $tr     = $(this);
    var sn      = $tr.find('.in-serie-num').val();
    var pag     = parseFloat($tr.find('.in-importe-pago').val() || 0);
    var moneda  = $tr.find('.in-moneda').val() || 'PEN';
    var factor  = parseFloat($tr.find('.in-factor-cambio').val() || 0);
    if (!sn || pag <= 0) {
      detallesValidos = false;
      razonInvalida = 'Cada linea necesita Serie-Numero e Importe Pago > 0.';
    }
    if (moneda === 'USD' && (!factor || factor <= 0)) {
      detallesValidos = false;
      razonInvalida = 'Las lineas en USD necesitan un factor de tipo de cambio > 0.';
    }
  });

  if (!detallesValidos) {
    Swal.fire({ html: razonInvalida, icon: 'warning' });
    return;
  }

  Swal.fire({
    html: '¿Registrar la retencion <b>' + $('#serie').val() + '-' + $('#numero').val() + '</b>?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Si, Registrar',
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
    fd.append('regimenretencion',     $('select[name="regimenretencion"]').val());
    fd.append('tasa',                 $('#tasa').val());
    fd.append('observacion',          $('input[name="observacion"]').val() || '');

    $('#detalles_body tr').each(function (i) {
      var $tr = $(this);
      var p = 'detalles[' + i + ']';
      fd.append(p + '[tipo_doc_rel]', $tr.find('.in-tipo-doc').val());
      fd.append(p + '[serie_num_rel]', $tr.find('.in-serie-num').val());
      fd.append(p + '[fecha_doc_rel]', $tr.find('.in-fecha-doc').val());
      fd.append(p + '[importe_doc]',   $tr.find('.in-importe-doc').val() || 0);
      fd.append(p + '[moneda_doc]',    $tr.find('.in-moneda').val());
      fd.append(p + '[fecha_pago]',    $tr.find('.in-fecha-pago').val());
      fd.append(p + '[importe_pago]',  $tr.find('.in-importe-pago').val());
      fd.append(p + '[moneda_pago]',   $tr.find('.in-moneda').val());
      fd.append(p + '[factor_cambio]', $tr.find('.in-factor-cambio').val() || '1.0');
    });

    $('#btn_registrar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registrando...');

    $.ajax({
      type: 'POST',
      url: route('retenciones.store'),
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (resp) {
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo }).then(function () {
          if (resp.procede) {
            window.location.href = route('retenciones.index');
          } else {
            $('#btn_registrar').prop('disabled', false).html('<i class="fa fa-save"></i> Registrar Retencion');
          }
        });
      },
      error: function (xhr) {
        var resp = xhr.responseJSON || { msj: 'Error al registrar', msj_tipo: 'error' };
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo || 'error' });
        $('#btn_registrar').prop('disabled', false).html('<i class="fa fa-save"></i> Registrar Retencion');
      },
    });
  });
});
