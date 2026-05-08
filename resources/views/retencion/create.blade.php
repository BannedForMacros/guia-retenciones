@extends('layouts.app')

<style>
  body { background-color: #f5f7fa !important; }
  .section-title {
    font-size: 11px; font-weight: 700; color: #1f2937;
    text-transform: uppercase; letter-spacing: .08em;
    border-left: 3px solid #2563eb; padding-left: .5rem; margin-bottom: .75rem;
  }
  .card-soft { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; }
  .totales .lbl { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
  .totales .val { font-weight: 700; }
  .totales .val.big { font-size: 18px; color: #16a34a; }
  table.tabla-detalles input.form-control { font-size: 12px; }
  table.tabla-detalles .in-factor-cambio:disabled { background: #f3f4f6; color: #9ca3af; }
  .totales .totales-hint { font-size: 10px; color: #9ca3af; font-weight: 500; letter-spacing: .04em; }

  /* Modal config series */
  #modalSeries .serie-tag {
    font-family: ui-monospace, Menlo, Consolas, monospace;
    font-weight: 700; color: #1f2937; background: #eff6ff;
    border: 1px solid #dbeafe; padding: 2px 8px; border-radius: 4px;
  }
  #modalSeries .ultimo-cero { color: #9ca3af; font-style: italic; }

  /* Selector de facturas (Select2) */
  .factura-result { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; padding: 2px 0; }
  .factura-result .left { min-width: 0; flex: 1; }
  .factura-result .top  { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
  .factura-result .tipo-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    background: #2563eb; color: #fff; padding: 1px 6px; border-radius: 3px;
  }
  .factura-result .serie {
    font-family: ui-monospace, Menlo, Consolas, monospace; font-weight: 700; color: #111827; font-size: 12px;
  }
  .factura-result .meta { font-size: 10px; color: #6b7280; margin-top: 1px; }
  .factura-result .importe { font-weight: 700; color: #111827; font-size: 12px; white-space: nowrap; }

  .factura-result.is-retenida .serie { text-decoration: line-through; color: #9ca3af; }
  .factura-result.is-retenida .importe { color: #9ca3af; }
  .factura-result .badge-retenida {
    font-size: 9px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: #92400e; background: #fef3c7; border: 1px solid #fde68a;
    padding: 1px 6px; border-radius: 3px;
  }
  .select2-results__option[aria-disabled="true"] { background: #f9fafb; cursor: not-allowed; }
</style>

@section('content')
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-md-12">

        {{-- Encabezado --}}
        <div class="d-flex justify-content-between align-items-end mb-3">
          <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('retenciones.index') }}">Retenciones</a></li>
                <li class="breadcrumb-item active">Nueva</li>
              </ol>
            </nav>
            <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Nueva Retencion</h5>
            <small class="text-muted">
              Emisor: <strong>{{ $razonSocial }}</strong> · RUC <strong>{{ $rucempresa }}</strong>
            </small>
          </div>
          <div>
            <a href="{{ route('retenciones.index') }}" class="btn btn-sm btn-outline-secondary">
              <i class="fa fa-times"></i> Cancelar
            </a>
            <button type="button" id="btn_registrar" class="btn btn-sm btn-success">
              <i class="fa fa-save"></i> Registrar Retencion
            </button>
          </div>
        </div>

        <form id="form_retencion" autocomplete="off">
          @csrf

          <div class="row g-3">
            {{-- Proveedor --}}
            <div class="col-md-6">
              <div class="card-soft p-3 h-100">
                <div class="section-title">Proveedor</div>

                <label class="form-label small mb-1">Buscar proveedor (RUC o razon social) *</label>
                <select id="proveedor_select" class="form-control form-select" style="width:100%;"></select>

                <div class="row g-2 mt-2">
                  <div class="col-md-4">
                    <label class="form-label small mb-1">RUC</label>
                    <input type="text" id="proveedor_ruc" name="numdocproveedor" class="form-control form-control-sm" maxlength="11" readonly>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label small mb-1">Razon Social</label>
                    <input type="text" id="proveedor_razon" name="razonsocialproveedor" class="form-control form-control-sm" readonly>
                  </div>
                  <div class="col-md-12">
                    <label class="form-label small mb-1">Direccion</label>
                    <input type="text" id="proveedor_direccion" name="direccionproveedor" class="form-control form-control-sm" readonly>
                  </div>
                </div>
              </div>
            </div>

            {{-- Comprobante --}}
            <div class="col-md-6">
              <div class="card-soft p-3 h-100">
                <div class="section-title">Comprobante de Retencion</div>

                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label small mb-1 d-flex justify-content-between align-items-center">
                      <span>Serie *</span>
                      <button type="button" class="btn btn-link btn-sm p-0 small text-decoration-none"
                              data-bs-toggle="modal" data-bs-target="#modalSeries"
                              title="Configurar series (MaestroDocumentoSerie)">
                        <i class="fa fa-cog"></i> config
                      </button>
                    </label>
                    <select id="serie" name="serie" class="form-select form-select-sm">
                      @forelse ($series as $s)
                        <option value="{{ $s['serie_formateada'] }}"
                                data-num-serie="{{ $s['num_serie'] }}"
                                data-ultimo-valor="{{ $s['ultimo_valor'] }}"
                                {{ $s['serie_formateada'] === $serieDefault ? 'selected' : '' }}>
                          {{ $s['serie_formateada'] }}
                        </option>
                      @empty
                        <option value="" disabled selected>(no hay series — crear en config)</option>
                      @endforelse
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small mb-1">Numero *</label>
                    <input type="text" id="numero" name="numero" class="form-control form-control-sm font-monospace"
                           maxlength="8" value="{{ $numeroSugerido }}" readonly
                           title="Calculado a partir de UltimoValor de la serie">
                  </div>
                  <div class="col-md-5">
                    <label class="form-label small mb-1">F. Emision *</label>
                    <input type="date" id="fecha_emision" name="fecha_emision" class="form-control form-control-sm"
                           value="{{ $fechaHoy }}" max="{{ $fechaHoy }}">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label small mb-1">Regimen</label>
                    <select name="regimenretencion" class="form-select form-select-sm">
                      <option value="01" selected>01 - Tasa General</option>
                      <option value="02">02 - Tasa Especial</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small mb-1">Tasa (%) *</label>
                    <input type="number" id="tasa" name="tasa" class="form-control form-control-sm text-end"
                           step="0.01" min="0" value="{{ $tasa }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small mb-1">Observacion</label>
                    <input type="text" name="observacion" class="form-control form-control-sm" maxlength="250">
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Detalles --}}
          <div class="card-soft p-3 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title mb-0">Documentos a Retener</div>
              <button type="button" id="btn_agregar_linea" class="btn btn-sm btn-outline-secondary"
                      title="Agregar una linea en blanco para tipear manualmente (solo si la factura no esta en el datamarket)">
                <i class="fa fa-plus"></i> Linea manual
              </button>
            </div>

            {{-- Selector de facturas pendientes del proveedor (datamarket) --}}
            <div class="mb-3">
              <label class="form-label small mb-1">
                Buscar factura pendiente del proveedor
                <span class="text-muted">(las ya retenidas aparecen marcadas y deshabilitadas)</span>
              </label>
              <select id="factura_select" class="form-control form-select" style="width:100%;" disabled></select>
              <div class="form-text small">
                Se listan facturas del proveedor con estado de pago pendiente.
                Al elegir una se agrega como nueva linea con los datos auto-completados.
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle tabla-detalles">
                <thead class="table-light">
                  <tr class="text-center small">
                    <th style="width:40px;">#</th>
                    <th style="width:120px;">Tipo Doc *</th>
                    <th style="width:150px;">Serie-Numero *</th>
                    <th style="width:130px;">F. Doc.</th>
                    <th style="width:120px;">Importe Doc.</th>
                    <th style="width:80px;">Moneda</th>
                    <th style="width:90px;" title="Factor de tipo de cambio. Solo aplica para USD.">T. Cambio</th>
                    <th style="width:130px;">F. Pago *</th>
                    <th style="width:130px;">Importe Pago *</th>
                    <th style="width:120px;">Retenido (PEN)</th>
                    <th style="width:120px;">Neto (PEN)</th>
                    <th style="width:50px;"></th>
                  </tr>
                </thead>
                <tbody id="detalles_body">
                  {{-- filas dinamicas --}}
                </tbody>
              </table>
            </div>

            <div id="vacio_msg" class="text-center text-muted py-3 small">
              <i class="fa fa-inbox"></i> No has agregado documentos. Selecciona un proveedor y haz click en "Agregar linea".
            </div>
          </div>

          {{-- Totales (siempre en PEN segun normativa SUNAT) --}}
          <div class="card-soft p-3 mt-3 totales">
            <div class="d-flex justify-content-between align-items-end mb-2">
              <span class="totales-hint">TOTALES EN PEN (USD se convierte usando el factor por linea)</span>
            </div>
            <div class="row text-end">
              <div class="col-md-4">
                <div class="lbl">Total Pagado</div>
                <div class="val" id="tot_pagado">S/ 0.00</div>
              </div>
              <div class="col-md-4">
                <div class="lbl">Total Retenido</div>
                <div class="val big" id="tot_retenido">S/ 0.00</div>
              </div>
              <div class="col-md-4">
                <div class="lbl">Neto al Proveedor</div>
                <div class="val" id="tot_neto">S/ 0.00</div>
              </div>
            </div>
          </div>

        </form>

      </div>
    </div>
  </div>

  {{-- ── Modal: Configuracion de Series ─────────────────────────────── --}}
  <div class="modal fade" id="modalSeries" tabindex="-1" aria-labelledby="modalSeriesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header py-2">
          <h6 class="modal-title" id="modalSeriesLabel">
            <i class="fa fa-cog"></i> Series para Retencion
          </h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            {{-- Lista --}}
            <div class="col-md-7">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="section-title mb-0">Series Existentes</div>
                <button type="button" id="btn_refresh_series" class="btn btn-sm btn-outline-secondary"
                        title="Refrescar"><i class="fa fa-sync"></i></button>
              </div>
              <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr class="small text-uppercase">
                      <th style="width:90px;">Serie</th>
                      <th class="text-end">Num.</th>
                      <th class="text-end">Ult. Valor</th>
                      <th class="text-end">Ult. Market</th>
                    </tr>
                  </thead>
                  <tbody id="series_body">
                    <tr><td colspan="4" class="text-center text-muted small py-3">Cargando...</td></tr>
                  </tbody>
                </table>
              </div>
              <div id="series_error" class="alert alert-danger small d-none mt-2 mb-0"></div>
            </div>

            {{-- Crear --}}
            <div class="col-md-5">
              <div class="section-title">Crear Nueva Serie</div>
              <form id="form_crear_serie" autocomplete="off">
                @csrf
                <div class="mb-2">
                  <label class="form-label small mb-1">Numero de serie *</label>
                  <input type="number" id="num_serie" name="num_serie" class="form-control form-control-sm"
                         min="1" max="9999" placeholder="ej. 1, 11, 200">
                  <div class="form-text small">
                    Solo el numero (1-9999). Prefijo lo aporta MaestroDocumento (<strong>R</strong> para retencion).
                    Quedara como <code id="preview_serie">R???</code>.
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small mb-1">Ctr. Resp. (opcional)</label>
                  <input type="text" id="ctr_resp" name="ctr_resp" class="form-control form-control-sm" maxlength="10">
                </div>
                <button type="submit" id="btn_crear_serie" class="btn btn-sm btn-primary w-100">
                  <i class="fa fa-plus"></i> Crear serie
                </button>
              </form>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  @push('js-scripts')
    <script src="{{ asset('js/retenciones/create.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
