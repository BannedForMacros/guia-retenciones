/* Retenciones - Listado */

$(document).ready(function () {
  callListarRetenciones();
});

$(document).on('submit', '#form_busqueda', function (event) {
  event.preventDefault();
  callListarRetenciones();
});

$(document).on('click', '#btn_reset', function () {
  var f = document.getElementById('form_busqueda');
  f.reset();
  // valores por defecto
  $('select[name="estado_sunat"]').val('all');
  callListarRetenciones();
});

var callListarRetenciones = function () {
  var formData = new FormData(document.getElementById('form_busqueda'));

  $('#div_loading').css('display', 'flex');
  $('#resultados').html('');

  $.ajax({
    type: 'POST',
    url: route('retenciones.listar'),
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'html',
    success: function (response) {
      $('#resultados').html(response);
      $('#div_loading').hide();

      try {
        var totales = JSON.parse($('#kpi_data').text() || '{}') || {};
        var fmt = function (n) { return 'S/ ' + Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        $('#kpi_total').text(totales.total_retenciones || 0);
        $('#kpi_prov').text(totales.proveedores_distintos || 0);
        $('#kpi_ret').text(fmt(totales.total_retenido));
        $('#kpi_acep').text(totales.aceptadas || 0);
        $('#kpi_rech').text(totales.rechazadas || 0);
        $('#kpi_anul').text(totales.anuladas || 0);
      } catch (e) { /* no rompemos la tabla por los KPIs */ }

      if ($.fn.DataTable && $('#tabla_retenciones').length) {
        $('#tabla_retenciones').DataTable({
          responsive: true,
          paging: true,
          pageLength: 25,
          lengthMenu: [10, 25, 50, 100],
          order: [],
          // Toolbar arriba (length+filter), tabla en su card, paginacion abajo. Cada uno separado.
          dom:
            "<'ret-dt-top'<'left'l><'right'f>>" +
            "<'ret-dt-table'rt>" +
            "<'ret-dt-bottom'<'left'i><'right'p>>",
          language: typeof DataTable_Spanish !== 'undefined' ? DataTable_Spanish : {
            emptyTable: 'No se encontraron retenciones para los filtros aplicados.',
            lengthMenu: 'Mostrar _MENU_ registros',
            search: 'Buscar:',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Sin registros',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' },
          },
        });
      }
    },
    error: function () {
      $('#div_loading').hide();
      $('#resultados').html('<div class="alert alert-danger m-3">Error al cargar las retenciones.</div>');
    },
  });
};

/* Igualar fechas si se invierten */
$(document).on('change', '.fecha', function () {
  var tipo = $(this).data('tipo');
  var ini  = $('#fecha_inicio').val();
  var fin  = $('#fecha_fin').val();
  if (tipo === 'inicio' && ini > fin) $('#fecha_fin').val(ini);
  if (tipo === 'fin'    && ini > fin) $('#fecha_inicio').val(fin);
});

/* ── Ver detalle ─────────────────────────────────────────────────────── */

$(document).on('click', '.ver_retencion', function () {
  var serienumero = $(this).data('serienumero');

  var fd = new FormData();
  fd.append('_token', _token);
  fd.append('serienumero', serienumero);

  $.ajax({
    type: 'POST',
    url: route('retenciones.show'),
    data: fd,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (resp) {
      if (!resp.procede) {
        Swal.fire({ html: resp.msj || 'Error', icon: 'error' });
        return;
      }
      pintarModalDetalle(resp.cabecera, resp.detalles);
    },
  });
});

var pintarModalDetalle = function (cab, detalles) {
  var fmt = function (v, mon) {
    var sym = mon === 'USD' ? 'US$' : 'S/';
    return sym + ' ' + Number(v || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  var labelSunat = { '00': 'Pendiente', '05': 'Aceptada', '09': 'Rechazada' };
  var classSunat = { '00': 'c-pend',    '05': 'c-acep',    '09': 'c-rech' };

  $('#md_serienumero').text(cab.serienumero);
  $('#md_numdocproveedor').text(cab.numdocproveedor);
  $('#md_razonsocialproveedor').text(cab.razonsocialproveedor);
  $('#md_direccionproveedor').text(cab.direccionproveedor || '—');
  $('#md_fechaemision').text(cab.fechaemision);
  $('#md_tasaretencion').text(((cab.tasaretencion || '').replace(/\.?0+$/, '')) + '%');
  $('#md_total_pagado').text(fmt(cab.importetotalpagado, cab.monedaimportetotalpagado));
  $('#md_total_retenido').text(fmt(cab.importetotalretenido, cab.monedaimportetotalretenido));

  var sLabel = labelSunat[cab.estadosunat] || cab.estadosunat || 'Sin estado';
  var sClass = classSunat[cab.estadosunat] || 'c-act';
  $('#md_chip_estado_sunat').html('<span class="md-chip ' + sClass + '">SUNAT: ' + sLabel + '</span>');

  var docAnul = cab.estadodocumento === '11';
  $('#md_chip_estado_doc').html(
    '<span class="md-chip ' + (docAnul ? 'c-anul' : 'c-act') + '">' + (docAnul ? 'ANULADA' : 'ACTIVA') + '</span>'
  );

  if (cab.observacion) {
    $('#md_observacion').text(cab.observacion);
    $('#md_observacion_wrap').show();
  } else {
    $('#md_observacion_wrap').hide();
  }

  // Bloque SUNAT: solo si hay hash, qr o mensaje_error
  var hash = cab.codigohash || '';
  var qr   = cab.codigoqr || '';
  var err  = cab.mensaje_error || '';
  if (hash || qr || err) {
    if (hash) { $('#md_sunat_hash').text(hash); $('#md_sunat_hash_wrap').show(); } else { $('#md_sunat_hash_wrap').hide(); }
    if (qr)   { $('#md_sunat_qr').text(qr);     $('#md_sunat_qr_wrap').show();   } else { $('#md_sunat_qr_wrap').hide(); }
    if (err)  { $('#md_sunat_err').text(err);   $('#md_sunat_err_wrap').show();  } else { $('#md_sunat_err_wrap').hide(); }
    $('#md_sunat_wrap').show();
  } else {
    $('#md_sunat_wrap').hide();
  }

  var tiposDoc = { '01':'Factura','03':'Boleta','07':'N. Credito','08':'N. Debito','12':'R. Honorarios','14':'Liq. Compra','99':'Otros' };

  var html = '';
  detalles.forEach(function (d, i) {
    var moneda = d.monedaimporteretenido || 'PEN';
    html += '<tr>';
    html +=   '<td class="text-center">' + (i + 1) + '</td>';
    html +=   '<td>' + (tiposDoc[d.tipodocrelacionado] || d.tipodocrelacionado) + '</td>';
    html +=   '<td class="font-monospace">' + d.serienumerorelacionado + '</td>';
    html +=   '<td>' + (d.fechaemisiondocrelacionado || '—') + '</td>';
    html +=   '<td>' + d.fechapago + '</td>';
    html +=   '<td class="text-end">' + fmt(d.importetotaldocrela, moneda) + '</td>';
    html +=   '<td class="text-end">' + fmt(d.importepagosinretencion, moneda) + '</td>';
    html +=   '<td class="text-end ret-monto">' + fmt(d.importeretenido, moneda) + '</td>';
    html +=   '<td class="text-end">' + fmt(d.montonetopagar, moneda) + '</td>';
    html += '</tr>';
  });
  $('#md_detalles_body').html(html);

  $('#md_btn_pdf').attr('href', '/retenciones/pdf/' + encodeURIComponent(cab.serienumero));

  var modalEl = document.getElementById('modalDetalleRetencion');
  var modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
};

/* ── Anular ──────────────────────────────────────────────────────────── */

$(document).on('click', '.anular_retencion', function () {
  var serienumero = $(this).data('serienumero');

  Swal.fire({
    title: 'Anular Retencion',
    html: '¿Seguro de anular <b>' + serienumero + '</b>?<br><small class="text-muted">Esta accion es de baja logica.</small>',
    icon: 'warning',
    input: 'text',
    inputLabel: 'Motivo (opcional)',
    inputPlaceholder: 'Ingrese motivo',
    showCancelButton: true,
    confirmButtonText: 'Si, Anular',
    cancelButtonText: 'No, cancelar',
    confirmButtonColor: '#dc2626',
  }).then(function (result) {
    if (!result.isConfirmed) return;

    var fd = new FormData();
    fd.append('_token', _token);
    fd.append('serienumero', serienumero);
    fd.append('motivo', result.value || 'Anulacion solicitada por el usuario');

    $.ajax({
      type: 'POST',
      url: route('retenciones.anular'),
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (resp) {
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo }).then(function () {
          if (resp.procede) callListarRetenciones();
        });
      },
      error: function (xhr) {
        var resp = xhr.responseJSON || { msj: 'Error al anular', msj_tipo: 'error' };
        Swal.fire({ html: resp.msj, icon: resp.msj_tipo || 'error' });
      },
    });
  });
});

/* ─── Modal: Configuración de Series ──────────────────────────────── */

(function () {
  function _escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }
  function _pad3(n) { n = String(n); while (n.length < 3) n = '0' + n; return n; }

  function cargarSeriesModal() {
    var $tbody = $('#series_body');
    var $err = $('#series_error').addClass('d-none').text('');
    $tbody.html('<tr><td colspan="4" class="text-center text-muted small py-3">Cargando…</td></tr>');
    $.ajax({ url: route('retenciones.listarSeries'), type: 'GET', dataType: 'json' })
      .done(function (resp) {
        if (!resp.procede) {
          $tbody.empty();
          $err.removeClass('d-none').text(resp.error || 'Error consultando series');
          return;
        }
        if (!resp.items.length) {
          $tbody.html('<tr><td colspan="4" class="text-center text-muted small py-3">Aún no hay series. Crea la primera ↗</td></tr>');
          return;
        }
        var html = '';
        resp.items.forEach(function (s) {
          var um  = (s.ultimo_valor === 0)        ? '<span class="ultimo-cero">0</span>' : _escapeHtml(s.ultimo_valor);
          var umk = (s.ultimo_valor_market === 0) ? '<span class="ultimo-cero">0</span>' : _escapeHtml(s.ultimo_valor_market);
          html +=
            '<tr>' +
              '<td><span class="serie-tag">' + _escapeHtml(s.serie_formateada) + '</span></td>' +
              '<td class="text-end">' + _escapeHtml(s.num_serie) + '</td>' +
              '<td class="text-end">' + um + '</td>' +
              '<td class="text-end">' + umk + '</td>' +
            '</tr>';
        });
        $tbody.html(html);
      })
      .fail(function (xhr) {
        $tbody.empty();
        $err.removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.error) || ('HTTP ' + xhr.status));
      });
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
      Swal.fire({ html: 'Ingresa un número de serie entre 1 y 9999.', icon: 'warning' });
      return;
    }
    var fd = new FormData();
    fd.append('_token', _token);
    fd.append('num_serie', num);
    var ctr = $('#ctr_resp').val();
    if (ctr) fd.append('ctr_resp', ctr);

    $('#btn_crear_serie').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creando…');

    $.ajax({
      url: route('retenciones.crearSerie'), type: 'POST', data: fd,
      processData: false, contentType: false, dataType: 'json',
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
})();
