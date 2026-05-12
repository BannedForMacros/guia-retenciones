/* Modal: Proveedores Afectos a Retencion
 *
 *   - Lista paginada server-side via /retenciones/proveedoresRetenidos.
 *   - Buscador con debounce (300ms).
 *   - Los checkboxes son SOLO un staging area: marcar/desmarcar no llama
 *     a la API, solo acumula cambios pendientes en memoria. El usuario
 *     puede paginar/buscar y los pendientes se conservan.
 *   - "Confirmar Retenidos": Swal con el resumen, y al aceptar dispara
 *     los PATCH en paralelo (Promise.all). Si alguno falla, se reporta.
 *   - "Descartar": limpia los pendientes y vuelve a la vista del server.
 *   - Cerrar con pendientes: Swal pregunta si confirmar / descartar.
 */
(function () {
  'use strict';

  var PAGE_SIZE = 50;
  var SEARCH_DEBOUNCE_MS = 300;

  // pending[ruc] = { nombre, current: bool, original: bool }
  var state = {
    search: '',
    offset: 0,
    total:  0,
    rows:   [],
    pending: {},
    searchTimer: null,
    loading: false,
    confirming: false,
  };

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }

  /** Devuelve 'add' si la accion pendiente es marcar, 'remove' si es desmarcar. */
  function pendingKind(ruc) {
    var p = state.pending[ruc];
    if (!p) return null;
    return p.current ? 'add' : 'remove';
  }

  function badgeHtml(kind) {
    return kind === 'add'
      ? '<span class="pr-pending-badge add" title="Se agregara al padron de retencion al confirmar">' +
          '<i class="fa fa-plus"></i> Agregar</span>'
      : '<span class="pr-pending-badge remove" title="Se quitara del padron de retencion al confirmar">' +
          '<i class="fa fa-minus"></i> Quitar</span>';
  }

  function pendingCount() {
    return Object.keys(state.pending).length;
  }

  /** Devuelve el "current" efectivo de un proveedor: lo pendiente si existe, si no, lo del server. */
  function currentValue(ruc, serverValue) {
    var p = state.pending[ruc];
    return p ? p.current : !!serverValue;
  }

  /** Sumar / restar un toggle al diario de pendientes. */
  function registrarToggle(ruc, nombre, newValue, originalValue) {
    if (newValue === originalValue) {
      delete state.pending[ruc];
    } else {
      state.pending[ruc] = {
        nombre:   nombre,
        current:  newValue,
        original: originalValue,
      };
    }
  }

  function setLoading(on) {
    state.loading = !!on;
    $('#pr_prev, #pr_next').prop('disabled', on || false);
    if (on) {
      $('#pr_body').html('<tr><td colspan="4" class="pr-empty">' +
        '<i class="fa fa-spinner fa-spin"></i>&nbsp; Cargando…</td></tr>');
    }
  }

  function actualizarFooter() {
    var n = pendingCount();
    if (n === 0) {
      $('#pr_cambios_info').html('<span class="pr-no-changes">Sin cambios pendientes</span>');
      $('#pr_btn_confirmar, #pr_btn_descartar').prop('disabled', true);
      return;
    }

    var marcados = 0, desmarcados = 0;
    Object.keys(state.pending).forEach(function (ruc) {
      if (state.pending[ruc].current) marcados++;
      else                            desmarcados++;
    });

    var html = '<span class="pr-counter"><i class="fa fa-clock-rotate-left"></i> ' +
               n + ' pendiente' + (n === 1 ? '' : 's') + '</span>';
    if (marcados)    html += '<span class="pr-delta plus">+'  + marcados    + '</span>';
    if (desmarcados) html += '<span class="pr-delta minus">−' + desmarcados + '</span>';
    $('#pr_cambios_info').html(html);
    $('#pr_btn_confirmar, #pr_btn_descartar').prop('disabled', false);
  }

  function renderRows() {
    var $tbody = $('#pr_body').empty();
    $('#pr_error').addClass('d-none').text('');

    if (!state.rows.length) {
      $tbody.html('<tr><td colspan="4" class="pr-empty">' +
        (state.search
          ? 'Sin resultados para "' + escapeHtml(state.search) + '".'
          : 'No hay proveedores cargados.') + '</td></tr>');
      return;
    }

    var html = '';
    state.rows.forEach(function (p) {
      var ruc       = p.ruc || '';
      var nom       = p.razon_social || '';
      var dir       = (p.direccion || '').trim();
      var serverV   = !!p.afecto_retencion;
      var currentV  = currentValue(ruc, serverV);
      var kind      = pendingKind(ruc);   // 'add' | 'remove' | null
      var rowCls    = kind === 'add' ? ' class="pr-pending-add"'
                    : kind === 'remove' ? ' class="pr-pending-remove"'
                    : '';
      var chkId     = 'pr_chk_' + ruc;

      html +=
        '<tr data-ruc="' + escapeHtml(ruc) + '"' + rowCls + '>' +
          '<td><span class="pr-ruc">' + escapeHtml(ruc) + '</span></td>' +
          '<td>' +
            '<span class="pr-name">' + escapeHtml(nom) + '</span>' +
            (kind ? badgeHtml(kind) : '') +
          '</td>' +
          '<td>' +
            '<div class="pr-direccion' + (dir ? '' : ' empty') + '" title="' +
              escapeHtml(dir || 'Sin dirección') + '">' +
              escapeHtml(dir || '—') +
            '</div>' +
          '</td>' +
          '<td class="text-center">' +
            '<input type="checkbox" class="form-check-input pr-toggle" ' +
              'id="' + chkId + '" ' +
              'data-ruc="'    + escapeHtml(ruc) + '" ' +
              'data-nombre="' + escapeHtml(nom) + '" ' +
              'data-server="' + (serverV ? '1' : '0') + '" ' +
              (currentV ? 'checked' : '') + '>' +
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
    })
    .fail(function (xhr) {
      state.rows = [];
      state.total = 0;
      renderRows();
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
    state.search   = '';
    state.offset   = 0;
    state.pending  = {};
    $('#pr_search').val('');
    actualizarFooter();
    fetchPagina();
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

  // ── Toggle SIN llamar a la API: solo actualiza pending ──
  $(document).on('change', '.pr-toggle', function () {
    var $chk    = $(this);
    var ruc     = $chk.data('ruc');
    var nombre  = $chk.data('nombre') || '';
    var serverV = $chk.data('server') === 1 || $chk.data('server') === '1';
    var newV    = $chk.is(':checked');

    registrarToggle(ruc, nombre, newV, serverV);

    var $row = $chk.closest('tr');
    var kind = pendingKind(ruc);    // 'add' | 'remove' | null

    // Limpiar primero (sea cual sea el estado anterior) y luego aplicar
    $row.removeClass('pr-pending-add pr-pending-remove');
    $row.find('.pr-pending-badge').remove();

    if (kind) {
      $row.addClass(kind === 'add' ? 'pr-pending-add' : 'pr-pending-remove');
      $row.find('.pr-name').after(badgeHtml(kind));
    }

    actualizarFooter();
  });

  // ── Descartar pendientes ──
  $(document).on('click', '#pr_btn_descartar', function () {
    if (pendingCount() === 0) return;

    Swal.fire({
      title: 'Descartar cambios',
      html: 'Se descartaran <b>' + pendingCount() + '</b> cambio(s) pendientes.<br>' +
            '<small class="text-muted">Esto no toca la base de datos.</small>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Si, descartar',
      cancelButtonText:  'No',
      confirmButtonColor: '#dc2626',
    }).then(function (result) {
      if (!result.isConfirmed) return;
      state.pending = {};
      renderRows();
      actualizarFooter();
    });
  });

  // ── Confirmar Retenidos: dispara las PATCH en paralelo ──
  function confirmarPendientes() {
    if (state.confirming || pendingCount() === 0) return Promise.resolve(false);

    var pendings = Object.keys(state.pending).map(function (ruc) {
      return Object.assign({ ruc: ruc }, state.pending[ruc]);
    });
    var marcados   = pendings.filter(function (p) { return p.current; });
    var desmarcados= pendings.filter(function (p) { return !p.current; });

    var html = '<div class="text-start small">';
    html += '<div class="mb-2">Se aplicaran <b>' + pendings.length + '</b> ' +
            'cambio' + (pendings.length === 1 ? '' : 's') + ' al datamarket:</div>';
    if (marcados.length) {
      html += '<div class="mb-1 text-success"><b><i class="fa fa-check"></i> ' +
              marcados.length + ' a marcar como afectos:</b></div>';
      html += '<ul class="mb-2 ps-3" style="max-height:140px; overflow:auto;">';
      marcados.forEach(function (p) {
        html += '<li><code>' + escapeHtml(p.ruc) + '</code> — ' + escapeHtml(p.nombre) + '</li>';
      });
      html += '</ul>';
    }
    if (desmarcados.length) {
      html += '<div class="mb-1 text-danger"><b><i class="fa fa-xmark"></i> ' +
              desmarcados.length + ' a desmarcar:</b></div>';
      html += '<ul class="mb-0 ps-3" style="max-height:140px; overflow:auto;">';
      desmarcados.forEach(function (p) {
        html += '<li><code>' + escapeHtml(p.ruc) + '</code> — ' + escapeHtml(p.nombre) + '</li>';
      });
      html += '</ul>';
    }
    html += '</div>';

    return Swal.fire({
      title: 'Confirmar Retenidos',
      html: html,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Si, aplicar al datamarket',
      cancelButtonText:  'No, revisar',
      confirmButtonColor: '#0E6CB5',
      showLoaderOnConfirm: true,
      allowOutsideClick: function () { return !Swal.isLoading(); },
      width: 560,
      preConfirm: function () {
        state.confirming = true;
        return ejecutarBulk(pendings)
          .finally(function () { state.confirming = false; });
      },
    }).then(function (result) {
      if (!result.isConfirmed) return false;

      var report = result.value || { ok: 0, fail: 0, errores: [] };
      mostrarResultadoBulk(report);
      // Limpiar pendientes que se aplicaron OK
      report.aplicadosRucs.forEach(function (ruc) { delete state.pending[ruc]; });
      // Refrescar tabla para reflejar el estado real del server
      fetchPagina();
      actualizarFooter();
      return true;
    });
  }

  /**
   * Ejecuta los PATCH en paralelo (un request por proveedor). Si quieres una
   * sola request, agrega un endpoint bulk en FastAPI; para N<=50 esto es OK.
   */
  function ejecutarBulk(pendings) {
    var promesas = pendings.map(function (p) {
      var fd = new FormData();
      fd.append('_token', _token);
      fd.append('ruc',    p.ruc);
      fd.append('afecto', p.current ? '1' : '0');
      return $.ajax({
        url: route('retenciones.togglearAfectoRetencion'),
        type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
      })
      .then(function (resp) { return { ok: true,  ruc: p.ruc, nombre: p.nombre, resp: resp }; })
      .catch(function (xhr) {
        var msg = (xhr.responseJSON && (xhr.responseJSON.msj || xhr.responseJSON.message))
                  || ('HTTP ' + xhr.status);
        return { ok: false, ruc: p.ruc, nombre: p.nombre, error: msg };
      });
    });

    return Promise.all(promesas).then(function (results) {
      var ok = 0, fail = 0;
      var errores = [];
      var aplicadosRucs = [];
      results.forEach(function (r) {
        if (r.ok) { ok++; aplicadosRucs.push(r.ruc); }
        else      { fail++; errores.push(r); }
      });
      return { ok: ok, fail: fail, errores: errores, aplicadosRucs: aplicadosRucs };
    });
  }

  function mostrarResultadoBulk(report) {
    if (report.fail === 0) {
      Swal.fire({
        title: '¡Listo!',
        html: '<b>' + report.ok + '</b> proveedor' + (report.ok === 1 ? '' : 'es') +
              ' actualizado' + (report.ok === 1 ? '' : 's') + ' en el datamarket.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false,
      });
      return;
    }

    var html = '<div class="text-start small">';
    if (report.ok) {
      html += '<div class="mb-2 text-success"><i class="fa fa-check"></i> ' +
              report.ok + ' actualizados correctamente.</div>';
    }
    html += '<div class="mb-1 text-danger"><b><i class="fa fa-triangle-exclamation"></i> ' +
            report.fail + ' fallaron:</b></div>';
    html += '<ul class="mb-0 ps-3" style="max-height:200px; overflow:auto;">';
    report.errores.forEach(function (e) {
      html += '<li><code>' + escapeHtml(e.ruc) + '</code> — ' +
              escapeHtml(e.nombre) + '<br>' +
              '<small class="text-muted">' + escapeHtml(e.error) + '</small></li>';
    });
    html += '</ul></div>';

    Swal.fire({
      title: 'Hubo errores',
      html: html,
      icon: report.ok ? 'warning' : 'error',
      width: 600,
    });
  }

  $(document).on('click', '#pr_btn_confirmar', function () {
    confirmarPendientes();
  });

  // ── Cerrar: si hay pendientes, preguntar ──
  $(document).on('click', '#pr_btn_cerrar', function () {
    if (pendingCount() === 0) {
      $('#modalProveedoresRetenidos').modal('hide');
      return;
    }
    Swal.fire({
      title: 'Tienes ' + pendingCount() + ' cambio(s) pendiente(s)',
      text:  '¿Que quieres hacer?',
      icon:  'warning',
      showCancelButton:      true,
      showDenyButton:        true,
      confirmButtonText:     'Confirmar ahora',
      denyButtonText:        'Descartar y cerrar',
      cancelButtonText:      'Seguir editando',
      confirmButtonColor:    '#0E6CB5',
      denyButtonColor:       '#dc2626',
    }).then(function (result) {
      if (result.isConfirmed) {
        confirmarPendientes().then(function () {
          // Si despues de aplicar no quedaron pendientes, cerramos.
          if (pendingCount() === 0) $('#modalProveedoresRetenidos').modal('hide');
        });
      } else if (result.isDenied) {
        state.pending = {};
        $('#modalProveedoresRetenidos').modal('hide');
      }
    });
  });

  // El "X" / Escape de Bootstrap NO pasa por #pr_btn_cerrar — interceptamos
  // el hide para preguntar. Si tras el Swal el usuario confirma cerrar,
  // dejamos pasar el hide; si decide quedarse, lo prevenimos.
  $('#modalProveedoresRetenidos').on('hide.bs.modal', function (e) {
    if (state.confirming) return;        // estamos aplicando, no estorbar
    if (pendingCount() === 0) return;    // sin pendientes, cerrar libre

    // Si ya estabamos en medio de un prompt de cierre, dejamos pasar
    if (state._closing) { state._closing = false; return; }

    e.preventDefault();
    Swal.fire({
      title: 'Tienes ' + pendingCount() + ' cambio(s) pendiente(s)',
      text:  '¿Que quieres hacer?',
      icon:  'warning',
      showCancelButton:      true,
      showDenyButton:        true,
      confirmButtonText:     'Confirmar ahora',
      denyButtonText:        'Descartar y cerrar',
      cancelButtonText:      'Seguir editando',
      confirmButtonColor:    '#0E6CB5',
      denyButtonColor:       '#dc2626',
    }).then(function (result) {
      if (result.isConfirmed) {
        confirmarPendientes().then(function () {
          if (pendingCount() === 0) {
            state._closing = true;
            $('#modalProveedoresRetenidos').modal('hide');
          }
        });
      } else if (result.isDenied) {
        state.pending = {};
        state._closing = true;
        $('#modalProveedoresRetenidos').modal('hide');
      }
    });
  });
})();
