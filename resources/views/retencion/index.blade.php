@extends('layouts.app')

<style>
  /* ========== Scope: solo modulo Retenciones ========== */
  body { background-color: #f3f4f6 !important; }

  .ret-wrap { max-width: 1600px; margin: 0 auto; padding: 16px; }
  @media (min-width: 768px) { .ret-wrap { padding: 18px 22px; } }

  /* ─── Header ───────────────────────────────────────────────────────── */
  .ret-head {
    display: flex; flex-wrap: wrap; gap: 12px;
    justify-content: space-between; align-items: center;
    padding-bottom: 14px; border-bottom: 1px solid #e5e7eb; margin-bottom: 16px;
  }
  .ret-head .title-wrap { display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1 1 auto; }
  .ret-head .icon-tile {
    width: 40px; height: 40px; background: #111827; color: #fff;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
  }
  .ret-head h1 { margin: 0; font-size: 1.3rem; font-weight: 700; color: #0f172a; letter-spacing: -.015em; line-height: 1.1; }
  .ret-head .meta { font-size: .77rem; color: #6b7280; margin-top: 3px; display: flex; flex-wrap: wrap; gap: 6px 14px; }
  .ret-head .meta .item { display: inline-flex; align-items: center; gap: 5px; }
  .ret-head .meta b { color: #0f172a; font-weight: 600; }

  .ret-head .btn-new {
    background: #111827; color: #fff; border: 1px solid #111827;
    padding: .5rem 1rem; border-radius: 6px; font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    white-space: nowrap;
  }
  .ret-head .btn-new:hover { background: #000; color: #fff; }
  .ret-head .btn-cfg {
    background: #fff; color: #111827; border: 1px solid #d1d5db;
    padding: .5rem .85rem; border-radius: 6px; font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    white-space: nowrap;
  }
  .ret-head .btn-cfg:hover { background: #f3f4f6; }
  .ret-head .actions { display: flex; gap: 8px; flex-wrap: wrap; }
  /* Modal config series */
  #modalSeries .serie-tag {
    font-family: ui-monospace, Menlo, Consolas, monospace;
    font-weight: 700; color: #1f2937; background: #eff6ff;
    border: 1px solid #dbeafe; padding: 2px 8px; border-radius: 4px;
  }
  #modalSeries .ultimo-cero { color: #9ca3af; font-style: italic; }
  @media (max-width: 575px) {
    .ret-head { flex-direction: column; align-items: stretch; }
    .ret-head .btn-new { width: 100%; }
    .ret-head h1 { font-size: 1.15rem; }
  }

  /* ─── KPIs ─────────────────────────────────────────────────────────── */
  .ret-kpis {
    display: grid; gap: 10px; margin-bottom: 14px;
    grid-template-columns: repeat(2, 1fr);
  }
  @media (min-width: 576px)  { .ret-kpis { grid-template-columns: repeat(3, 1fr); } }
  @media (min-width: 992px)  { .ret-kpis { grid-template-columns: repeat(6, 1fr); } }

  .ret-kpi {
    position: relative; background: #fff; border: 1px solid #d1d5db; border-radius: 8px;
    padding: .65rem .85rem .65rem 1rem; overflow: hidden;
  }
  .ret-kpi::before {
    content: ""; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: #9ca3af;
  }
  .ret-kpi:hover { background: #f9fafb; border-color: #9ca3af; }

  .ret-kpi .ico { position: absolute; right: .7rem; top: .65rem; color: #9ca3af; font-size: .82rem; opacity: .6; }
  .ret-kpi .lbl { font-size: .65rem; color: #6b7280; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
  .ret-kpi .val { font-size: 1.35rem; font-weight: 700; color: #0f172a; line-height: 1.1; margin-top: 4px; font-variant-numeric: tabular-nums; }

  .ret-kpi.k-tot::before  { background: #2563eb; }
  .ret-kpi.k-prov::before { background: #7c3aed; }
  .ret-kpi.k-ret::before  { background: #16a34a; }
  .ret-kpi.k-acep::before { background: #16a34a; }
  .ret-kpi.k-rech::before { background: #dc2626; }
  .ret-kpi.k-anul::before { background: #d97706; }

  .ret-kpi.k-acep .val { color: #15803d; }
  .ret-kpi.k-rech .val { color: #b91c1c; }
  .ret-kpi.k-anul .val { color: #b45309; }

  /* ─── Filtros ──────────────────────────────────────────────────────── */
  .ret-card { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; }

  .ret-filters { padding: .65rem .85rem; }
  .ret-filters .form-label {
    font-size: .65rem; color: #4b5563; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px;
    display: inline-flex; align-items: center; gap: 5px;
  }
  .ret-filters .form-label .fa { font-size: .7rem; color: #9ca3af; }

  .ret-filters .form-control, .ret-filters .form-select {
    border: 1px solid #d1d5db; border-radius: 5px; font-size: .82rem;
    padding: .35rem .6rem; height: 34px; color: #0f172a;
  }
  .ret-filters .form-control:focus, .ret-filters .form-select:focus {
    border-color: #111827; box-shadow: 0 0 0 1px #111827; outline: 0;
  }
  .ret-filters .input-icon { position: relative; }
  .ret-filters .input-icon .fa-prefix {
    position: absolute; left: 9px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: .75rem; pointer-events: none;
  }
  .ret-filters .input-icon .form-control { padding-left: 26px; }

  .ret-filters .btn { font-size: .8rem; padding: .35rem .8rem; height: 34px; border-radius: 5px; font-weight: 600; }
  .ret-filters .btn-search { background: #111827; color: #fff; border: 1px solid #111827; }
  .ret-filters .btn-search:hover { background: #000; color: #fff; }
  .ret-filters .btn-clear { background: #fff; color: #4b5563; border: 1px solid #d1d5db; }
  .ret-filters .btn-clear:hover { background: #f3f4f6; }

  /* ─── DataTables: 3 secciones separadas ────────────────────────────── */
  .ret-dt-top, .ret-dt-bottom {
    background: #fff; border: 1px solid #d1d5db; border-radius: 8px;
    padding: .55rem .85rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
  }
  .ret-dt-top    { margin-top: 12px; margin-bottom: 8px; }
  .ret-dt-bottom { margin-top: 8px; }
  .ret-dt-top > div, .ret-dt-bottom > div { min-width: 0; }
  @media (max-width: 575px) {
    .ret-dt-top, .ret-dt-bottom { flex-direction: column; align-items: stretch; }
    .dataTables_wrapper .dataTables_filter input { width: 100% !important; }
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label { justify-content: space-between; }
    .dataTables_wrapper .dataTables_paginate { display: flex; justify-content: center; }
  }

  .dataTables_wrapper .dataTables_length,
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate {
    margin: 0; font-size: .78rem; color: #4b5563;
  }
  .dataTables_wrapper .dataTables_length label,
  .dataTables_wrapper .dataTables_filter label { margin: 0; display: flex; align-items: center; gap: 8px; font-weight: 500; }
  .dataTables_wrapper .dataTables_length select,
  .dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d5db; border-radius: 5px; padding: .3rem .55rem; font-size: .8rem; height: 30px;
    color: #0f172a;
  }
  .dataTables_wrapper .dataTables_filter input { width: 240px; }
  .dataTables_wrapper .dataTables_filter input:focus,
  .dataTables_wrapper .dataTables_length select:focus { border-color: #111827; outline: 0; box-shadow: 0 0 0 1px #111827; }

  .dataTables_wrapper .pagination { margin: 0; }
  .dataTables_wrapper .page-link {
    border: 1px solid #d1d5db; color: #111827; font-size: .78rem;
    padding: .25rem .55rem; margin: 0 2px; border-radius: 5px;
    min-width: 28px; text-align: center;
  }
  .dataTables_wrapper .page-link:hover { background: #f3f4f6; color: #111827; }
  .dataTables_wrapper .page-item.active .page-link { background: #111827; border-color: #111827; color: #fff; }
  .dataTables_wrapper .page-item.disabled .page-link { color: #9ca3af; background: #fff; }

  /* ─── Tabla (solo thead + tbody) ───────────────────────────────────── */
  .ret-dt-table { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .ret-dt-table .table { margin: 0; font-size: .83rem; min-width: 880px; }
  .ret-dt-table .table thead th {
    background: #f9fafb; color: #374151; font-weight: 700;
    font-size: .67rem; text-transform: uppercase; letter-spacing: .05em;
    border-bottom: 1px solid #d1d5db; border-top: 0;
    padding: .65rem .8rem; white-space: nowrap;
  }
  .ret-dt-table .table tbody td {
    padding: .7rem .8rem; vertical-align: middle;
    border-bottom: 1px solid #f3f4f6; border-top: 0; color: #0f172a;
  }
  .ret-dt-table .table tbody tr:hover td { background: #f9fafb; }
  .ret-dt-table .table tbody tr:last-child td { border-bottom: 0; }

  .serie-pill {
    display: inline-block; background: #fff; color: #1d4ed8;
    border: 1px solid #93c5fd;
    font-family: ui-monospace, monospace; font-weight: 700; font-size: .76rem;
    padding: .15rem .5rem; border-radius: 4px;
  }

  .prov-name { font-weight: 600; color: #0f172a; line-height: 1.2; }
  .ruc-mono  { font-family: ui-monospace, monospace; color: #6b7280; font-size: .72rem; margin-top: 1px; }

  .monto     { font-variant-numeric: tabular-nums; color: #374151; }
  .ret-monto { font-weight: 700; color: #16a34a; font-variant-numeric: tabular-nums; }

  /* Estado tags (puros, no parecen botones) */
  .ret-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: .12rem .45rem; border-radius: 3px;
    font-size: .68rem; font-weight: 600; border: 1px solid; background: #fff;
    letter-spacing: .02em;
    cursor: default; user-select: none;
  }
  .ret-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
  .b-acep { color: #15803d; border-color: #86efac; }
  .b-rech { color: #b91c1c; border-color: #fca5a5; }
  .b-pend { color: #b45309; border-color: #fcd34d; }
  .b-anul { color: #b45309; border-color: #fcd34d; }
  .b-act  { color: #15803d; border-color: #86efac; }

  .ret-actions .btn {
    --bs-btn-padding-x: .5rem; --bs-btn-padding-y: .25rem;
    --bs-btn-font-size: .78rem;
    border-radius: 5px; border-width: 1px;
  }
  .ret-actions .btn:hover { background: #f3f4f6; }
  .ret-actions .btn-outline-primary:hover { background: #eff6ff; color: #1d4ed8; border-color: #1d4ed8; }
  .ret-actions .btn-outline-danger:hover  { background: #fef2f2; color: #b91c1c; border-color: #b91c1c; }

  /* Loading */
  .ret-loading {
    display: none; align-items: center; gap: 8px;
    padding: .45rem .85rem; background: #fff; border: 1px solid #d1d5db;
    border-radius: 8px; color: #4b5563; font-size: .8rem; margin-top: 12px;
  }
</style>

@section('content')
<div class="ret-wrap">

  {{-- ────────── Header ────────── --}}
  <div class="ret-head">
    <div class="title-wrap">
      <div class="icon-tile"><i class="fa fa-file-invoice-dollar"></i></div>
      <div>
        <h1>Retenciones</h1>
        <div class="meta">
          <span class="item"><i class="fa fa-building text-muted"></i> RUC <b>{{ $rucempresa }}</b></span>
          <span class="item"><i class="fa fa-percent text-muted"></i> Tasa <b>{{ $tasa }}%</b></span>
          <span class="item"><i class="fa fa-calendar text-muted"></i> Periodo
            <b>{{ str_pad($mesActual, 2, '0', STR_PAD_LEFT) }}/{{ $anioActual }}</b>
          </span>
        </div>
      </div>
    </div>
    <div class="actions">
      <button type="button" class="btn-cfg" data-bs-toggle="modal" data-bs-target="#modalSeries"
              title="Configurar series (MaestroDocumentoSerie)">
        <i class="fa fa-cog"></i> Series
      </button>
      <a href="{{ route('retenciones.create') }}" class="btn-new">
        <i class="fa fa-plus"></i> Nueva Retencion
      </a>
    </div>
  </div>

  {{-- ────────── KPIs ────────── --}}
  <div class="ret-kpis">
    <div class="ret-kpi k-tot">
      <i class="fa fa-file-invoice ico"></i>
      <div class="lbl">Total</div><div class="val" id="kpi_total">0</div>
    </div>
    <div class="ret-kpi k-prov">
      <i class="fa fa-users ico"></i>
      <div class="lbl">Proveedores</div><div class="val" id="kpi_prov">0</div>
    </div>
    <div class="ret-kpi k-ret">
      <i class="fa fa-coins ico"></i>
      <div class="lbl">Retenido</div><div class="val" id="kpi_ret">S/ 0.00</div>
    </div>
    <div class="ret-kpi k-acep">
      <i class="fa fa-check-circle ico"></i>
      <div class="lbl">Aceptadas</div><div class="val" id="kpi_acep">0</div>
    </div>
    <div class="ret-kpi k-rech">
      <i class="fa fa-times-circle ico"></i>
      <div class="lbl">Rechazadas</div><div class="val" id="kpi_rech">0</div>
    </div>
    <div class="ret-kpi k-anul">
      <i class="fa fa-ban ico"></i>
      <div class="lbl">Anuladas</div><div class="val" id="kpi_anul">0</div>
    </div>
  </div>

  {{-- ────────── Filtros ────────── --}}
  <div class="ret-card">
    <div class="ret-filters">
      <form id="form_busqueda" autocomplete="off">
        @csrf
        <div class="row g-2 align-items-end">
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label"><i class="fa fa-calendar"></i> Fecha Inicio</label>
            <input class="form-control fecha" data-tipo="inicio" type="date"
              name="fecha_inicio" id="fecha_inicio"
              value="{{ sprintf('%04d-%02d-01', $anioActual, $mesActual) }}">
          </div>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label"><i class="fa fa-calendar"></i> Fecha Fin</label>
            <input class="form-control fecha" data-tipo="fin" type="date"
              name="fecha_fin" id="fecha_fin" value="{{ date('Y-m-d') }}">
          </div>
          <div class="col-4 col-md-2 col-xl-1">
            <label class="form-label"><i class="fa fa-hashtag"></i> Serie</label>
            <input type="text" class="form-control text-uppercase" name="serie" placeholder="R001">
          </div>
          <div class="col-8 col-md-6 col-xl-2">
            <label class="form-label"><i class="fa fa-id-card"></i> RUC Proveedor</label>
            <input type="text" class="form-control" name="ruc_proveedor" maxlength="11" placeholder="20XXXXXXXXX">
          </div>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label"><i class="fa fa-flag"></i> Estado SUNAT</label>
            <select class="form-select" name="estado_sunat">
              <option value="all">Todos</option>
              <option value="00">Pendiente</option>
              <option value="05">Aceptada</option>
              <option value="09">Rechazada</option>
              <option value="null">Sin estado</option>
            </select>
          </div>
          <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label"><i class="fa fa-search"></i> Busqueda</label>
            <div class="input-icon">
              <i class="fa fa-magnifying-glass fa-prefix"></i>
              <input type="text" class="form-control" name="busqueda" placeholder="Serie, RUC, razon...">
            </div>
          </div>
          <div class="col-12 col-md-4 col-xl-1 d-flex gap-1">
            <button class="btn btn-search flex-fill" type="submit" title="Buscar">
              <i class="fa fa-search"></i>
            </button>
            <button class="btn btn-clear" type="button" id="btn_reset" title="Limpiar">
              <i class="fa fa-rotate-left"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="ret-loading" id="div_loading">
    <div class="spinner-border spinner-border-sm text-dark" role="status"></div>
    <span>Cargando retenciones...</span>
  </div>

  {{-- ────────── Resultados ────────── --}}
  <div id="resultados"></div>

</div>

{{-- Modal detalle --}}
@include('retencion.modal-detalle')

{{-- ────────── Modal: Configuración de Series ────────── --}}
<div class="modal fade" id="modalSeries" tabindex="-1" aria-labelledby="modalSeriesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="modalSeriesLabel">
          <i class="fa fa-cog"></i> Series para Retención
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="small fw-bold text-uppercase text-muted" style="letter-spacing:.06em;">Series Existentes</div>
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
                  <tr><td colspan="4" class="text-center text-muted small py-3">Cargando…</td></tr>
                </tbody>
              </table>
            </div>
            <div id="series_error" class="alert alert-danger small d-none mt-2 mb-0"></div>
          </div>

          <div class="col-md-5">
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.06em;">Crear Nueva Serie</div>
            <form id="form_crear_serie" autocomplete="off">
              @csrf
              <div class="mb-2">
                <label class="form-label small mb-1">Número de serie *</label>
                <input type="number" id="num_serie" name="num_serie" class="form-control form-control-sm"
                       min="1" max="9999" placeholder="ej. 1, 11, 200">
                <div class="form-text small">
                  Solo el número (1–9999). El prefijo es <strong>R</strong> (de MaestroDocumento).
                  Quedará como <code id="preview_serie">R???</code>.
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
  <script src="{{ asset('js/retenciones/index.js?v=') }}{{ rand() }}"></script>
@endpush
@endsection
