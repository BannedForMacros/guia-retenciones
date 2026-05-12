/* Modal: Proveedores Afectos a Retencion
 *
 *   - Lista paginada server-side via /retenciones/proveedoresRetenidos.
 *   - Busqueda con debounce (300ms), se reinicia al primer offset.
 *   - Cada checkbox dispara un Swal de confirmacion antes de
 *     llamar a /retenciones/togglearAfectoRetencion.
 *   - Al cerrar el modal, si hubo cambios, muestra un resumen con la
 *     lista de proveedores afectados en esta sesion.
 */
(function () {
  'use strict';

  var PAGE_SIZE = 50;
  var SEARCH_DEBOUNCE_MS = 300;

  // Estado del modal (vive mientras la pagina esta abierta)
  var state = {
    search: '',
    offset: 0,
    total:  0,
    rows:   [],      // ultimo resultset visible
    cambios: {},     // {ruc: {nombre, afecto, prev_afecto}}
    searchTimer: null,
    loading: false,
  };

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }

  function setLoading(on) {
    state.loading = !!on;
    $('#pr_prev, #pr_next').prop('disabled', on || false);
    if (on) {
      $('#pr_body').html('<tr><td colspan="4" class="text-center text-muted small py-3">' +
        '<i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>');
    }
  }

  function actualizarResumenFooter() {
    var n = Object.keys(state.cambios).length;
    if (n === 0) {
      $('#pr_cambios_info').text('Sin cambios en esta sesion.');
    } else {
      $('#pr_cambios_info').html('<i class="fa fa-clock-rotate-left"></i> ' + n +
        ' cambio' + (n === 1 ? '' : 's') + ' en esta sesion.');
    }
  }

  function renderRows() {
    var $tbody = $('#pr_body').empty();
    var $err   = $('#pr_error').addClass('d-none').text('');

    if (!state.rows.length) {
      $tbody.html('<tr><td colspan="4" class="text-center text-muted small py-3">' +
        (state.search
          ? 'Sin resultados para "' + escapeHtml(state.search) + '".'
          : 'No hay proveedores cargados.') + '</td></tr>');
      return;
    }

    var html = '';
    state.rows.forEach(function (p) {
      var ruc   = p.ruc || '';
      var nom   = p.razon_social || '';
      var dir   = p.direccion || '—';
      var afect = !!p.afecto_retencion;
      var chkId = 'pr_chk_' + ruc;
      html +=
        '<tr data-ruc="' + escapeHtml(ruc) + '">' +
          '<td class="font-monospace small">' + escapeHtml(ruc) + '</td>' +
          '<td><span class="prov-name">' + escapeHtml(nom) + '</span></td>' +
          '<td class="small text-muted">' + escapeHtml(dir) + '</td>' +
          '<td class="text-center">' +
            '<div class="form-check d-inline-block m-0">' +
              '<input type="checkbox" class="form-check-input pr-toggle" ' +
                'id="' + chkId + '" ' +
                'data-ruc="' + escapeHtml(ruc) + '" ' +
                'data-nombre="' + escapeHtml(nom) + '" ' +
                (afect ? 'checked' : '') + '>' +
            '</div>' +
          '</td>' +
        '</tr>';
    });
    $tbody.html(html);
  }

  function actualizarPaginacion() {
    var desde = state.total === 0 ? 0 : state.offset + 1;
    var hasta = Math.min(state.offset + state.rows.length, state.total);
    $('#pr_rango').text(desde + '–' + hasta);
    $('#pr_total').text(state.total);
    $('#pr_total_info').text(state.total);

    $('#pr_prev').prop('disabled', state.loading || state.offset <= 0);
    $('#pr_next').prop('disabled', state.loading || (state.offset + state.rows.length) >= state.total);
  }

  function fetchPagina() {
    setLoading(true);

    $.ajax({
      url: route('retenciones.proveedoresRetenidos'),
      type: 'GET',
      dataType: 'json',
      data: { search: state.search || '', limit: PAGE_SIZE, offset: state.offset },
    })
    .done(function (resp) {
      if (!resp.procede) {
        state.rows = [];
        state.total = 0;
        $('#pr_error').removeClass('d-none').text(resp.msj || 'Error consultando proveedores.');
      } else {
        state.rows  = resp.items || [];
        state.total = resp.total || 0;
      }
      renderRows();
      actualizarPaginacion();
    })
    .fail(function (xhr) {
      state.rows = [];
      state.total = 0;
      renderRows();
      actualizarPaginacion();
      var msg = (xhr.responseJSON && (xhr.responseJSON.msj || xhr.responseJSON.message))
                || ('HTTP ' + xhr.status);
      $('#pr_error').removeClass('d-none').text(msg);
    })
    .always(function () {
      setLoading(false);
      actualizarPaginacion();
    });
  }

  function resetYRecargar() {
    state.offset = 0;
    fetchPagina();
  }

  // ── Init al abrir el modal ──
  $('#modalProveedoresRetenidos').on('shown.bs.modal', function () {
    // No reseteamos `cambios` para que el usuario pueda abrir-cerrar y
    // seguir viendo el resumen en footer. Si quieres limpiarlo siempre,
    // descomenta la siguiente linea.
    // state.cambios = {};
    state.search = '';
    $('#pr_search').val('');
    actualizarResumenFooter();
    resetYRecargar();
  });

  // ── Cierre: mostrar resumen si hubo cambios ──
  $('#modalProveedoresRetenidos').on('hidden.bs.modal', function () {
    var cambios = state.cambios;
    var rucs = Object.keys(cambios);
    if (rucs.length === 0) return;

    var marcados   = rucs.filter(function (r) { return cambios[r].afecto; });
    var desmarcados= rucs.filter(function (r) { return !cambios[r].afecto; });

    var html = '<div class="text-start small">';
    html += '<div class="mb-2">Se realizaron <b>' + rucs.length + '</b> ' +
            'actualizacion' + (rucs.length === 1 ? '' : 'es') + ':</div>';

    if (marcados.length) {
      html += '<div class="mb-1 text-success"><b><i class="fa fa-check"></i> ' +
              marcados.length + ' marcados como afectos:</b></div>';
      html += '<ul class="mb-2 ps-3" style="max-height:160px; overflow:auto;">';
      marcados.forEach(function (ruc) {
        html += '<li><code>' + escapeHtml(ruc) + '</code> — ' +
                escapeHtml(cambios[ruc].nombre) + '</li>';
      });
      html += '</ul>';
    }
    if (desmarcados.length) {
      html += '<div class="mb-1 text-danger"><b><i class="fa fa-xmark"></i> ' +
              desmarcados.length + ' ya no afectos:</b></div>';
      html += '<ul class="mb-0 ps-3" style="max-height:160px; overflow:auto;">';
      desmarcados.forEach(function (ruc) {
        html += '<li><code>' + escapeHtml(ruc) + '</code> — ' +
                escapeHtml(cambios[ruc].nombre) + '</li>';
      });
      html += '</ul>';
    }
    html += '</div>';

    Swal.fire({
      title: 'Resumen de cambios',
      html: html,
      icon: 'info',
      confirmButtonText: 'Entendido',
      width: 560,
    });

    // Despues de mostrar el resumen, lo "consumimos" para no repetirlo
    state.cambios = {};
    actualizarResumenFooter();
  });

  // ── Buscador con debounce ──
  $(document).on('input', '#pr_search', function () {
    var val = $(this).val() || '';
    state.search = val.trim();
    if (state.searchTimer) clearTimeout(state.searchTimer);
    state.searchTimer = setTimeout(resetYRecargar, SEARCH_DEBOUNCE_MS);
  });

  // ── Paginacion ──
  $(document).on('click', '#pr_prev', function () {
    if (state.loading || state.offset <= 0) return;
    state.offset = Math.max(0, state.offset - PAGE_SIZE);
    fetchPagina();
  });
  $(document).on('click', '#pr_next', function () {
    if (state.loading) return;
    if ((state.offset + state.rows.length) >= state.total) return;
    state.offset += PAGE_SIZE;
    fetchPagina();
  });

  // ── Toggle del checkbox con SweetAlert de confirmacion ──
  // IMPORTANTE: el handler revierte la UI si el usuario cancela o si la API falla.
  $(document).on('change', '.pr-toggle', function () {
    var $chk    = $(this);
    var ruc     = $chk.data('ruc');
    var nombre  = $chk.data('nombre') || '';
    var afecto  = $chk.is(':checked');           // valor que quiere quedar
    var prev    = !afecto;                       // valor previo (antes del toggle)
    var accion  = afecto ? 'marcar' : 'desmarcar';
    var verbo   = afecto ? 'afecto a retencion' : 'NO afecto a retencion';

    Swal.fire({
      title: 'Confirmar cambio',
      html:
        '¿Seguro de <b>' + accion + '</b> al proveedor como <b>' + verbo + '</b>?<br>' +
        '<div class="mt-2 small text-muted">' +
          '<code>' + escapeHtml(ruc) + '</code> — ' + escapeHtml(nombre) +
        '</div>',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Si, ' + accion,
      cancelButtonText:  'Cancelar',
      confirmButtonColor: afecto ? '#0E6CB5' : '#dc2626',
      showLoaderOnConfirm: true,
      allowOutsideClick: function () { return !Swal.isLoading(); },
      preConfirm: function () {
        var fd = new FormData();
        fd.append('_token', _token);
        fd.append('ruc',    ruc);
        fd.append('afecto', afecto ? '1' : '0');

        return $.ajax({
          url: route('retenciones.togglearAfectoRetencion'),
          type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
        })
        .then(function (resp) { return resp; })
        .catch(function (xhr) {
          var resp = xhr.responseJSON || { msj: 'Error al actualizar.', msj_tipo: 'error' };
          // El swal mostrara el error como resultado normal (no como reject)
          return Object.assign({ procede: false }, resp);
        });
      },
    }).then(function (result) {
      if (!result.isConfirmed) {
        // Usuario cancelo: revertir checkbox a su estado previo
        $chk.prop('checked', prev);
        return;
      }

      var resp = result.value || {};
      if (!resp.procede) {
        $chk.prop('checked', prev);
        Swal.fire({
          title: 'No se pudo actualizar',
          html: resp.msj || 'Error al actualizar.',
          icon: resp.msj_tipo || 'error',
        });
        return;
      }

      // Persistir en el estado en memoria del row actual
      var row = state.rows.find(function (r) { return String(r.ruc) === String(ruc); });
      if (row) row.afecto_retencion = !!resp.afecto_retencion;

      // Llevar el cambio al "diario de cambios" de la sesion. Si el usuario
      // volvio al valor original, lo sacamos del diario.
      var actual   = !!resp.afecto_retencion;
      var original = !!state.cambios[ruc] ? state.cambios[ruc].prev_afecto : prev;
      if (actual === original) {
        delete state.cambios[ruc];
      } else {
        state.cambios[ruc] = {
          nombre: nombre,
          afecto: actual,
          prev_afecto: state.cambios[ruc] ? state.cambios[ruc].prev_afecto : prev,
        };
      }
      actualizarResumenFooter();

      // Toast discreto (no interrumpe el flujo)
      Swal.fire({
        toast: true, position: 'top-end', timer: 1500, showConfirmButton: false,
        icon: resp.msj_tipo || 'success',
        title: resp.msj || (actual ? 'Marcado como afecto' : 'Ya no afecto'),
      });
    });
  });
})();
