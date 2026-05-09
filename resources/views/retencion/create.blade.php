@extends('layouts.app')

<style>
  body { background-color: #f5f7fa !important; }

  /* ── Layout general ────────────────────────────────────────────── */
  .ret-create-wrap { max-width: 1500px; margin: 0 auto; padding: 14px 18px; }
  .card-soft { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
  .section-title {
    font-size: 11px; font-weight: 700; color: #1f2937;
    text-transform: uppercase; letter-spacing: .08em;
    border-left: 3px solid #2563eb; padding-left: .5rem; margin-bottom: 0;
  }

  /* ── Header ────────────────────────────────────────────────────── */
  .page-title {
    font-size: 1.45rem; font-weight: 700; color: #0f172a; margin: 0;
    letter-spacing: -.02em; line-height: 1.15;
  }
  .page-subtitle {
    font-size: .78rem; color: #64748b; margin-bottom: 4px;
  }
  .page-subtitle a { color: #475569; text-decoration: none; }
  .page-subtitle a:hover { color: #0f172a; text-decoration: underline; }

  /* ── Botones flat ─────────────────────────────────────────────── */
  .btn-flat-primary,
  .btn-flat-success,
  .btn-ghost {
    border: 0; border-radius: 6px;
    padding: .55rem 1.1rem; font-size: .85rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 7px;
    box-shadow: none; line-height: 1.2; transition: background .15s, color .15s;
  }
  .btn-flat-success {
    background: #16a34a; color: #fff;
  }
  .btn-flat-success:hover { background: #15803d; color: #fff; }
  .btn-flat-success:disabled { background: #cbd5e1; color: #fff; cursor: not-allowed; }
  .btn-flat-success:disabled:hover { background: #cbd5e1; }
  .btn-ghost {
    background: transparent; color: #64748b; font-weight: 500;
  }
  .btn-ghost:hover { background: #f1f5f9; color: #0f172a; }

  /* ── Step badge en títulos de sección (guía visual de orden) ──── */
  .step-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 50%;
    background: #2563eb; color: #fff;
    font-size: .68rem; font-weight: 700;
    margin-right: 6px; flex-shrink: 0;
  }
  .section-title.with-step { display: inline-flex; align-items: center; }

  /* ── Comprobante: TEXTO puro (no input, no caja).
       Usa la misma font-family que el label para que ambos compartan
       el mismo bearing del primer carácter → alineación perfecta en X. */
  .serie-text {
    /* Mismo alto que .form-control-sm para baseline uniforme con los inputs vecinos */
    height: calc(1.5em + .5rem + 2px);
    display: flex; align-items: center;
    padding: 0;                     /* mismo X de inicio que el label arriba */
    font-family: inherit;           /* NO monospace → sin bearing extraño */
    font-weight: 700; color: #1f2937;
    font-size: .95rem; letter-spacing: .01em;
  }
  .serie-text.is-empty {
    color: #b45309; font-style: italic; font-weight: 500; font-size: .85rem;
  }

  /* ── Proveedor: chip (vertical, encaja en media columna) ─────── */
  .prov-chip {
    display: flex; align-items: flex-start; gap: .65rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: .65rem .75rem;
  }
  .prov-chip .avatar {
    width: 34px; height: 34px; flex-shrink: 0;
    background: #0284c7; color: #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .82rem;
  }
  .prov-chip .body { min-width: 0; flex: 1; }
  .prov-chip .name { font-weight: 700; color: #0f172a; font-size: .9rem; line-height: 1.2; }
  .prov-chip .ruc  { font-family: ui-monospace, monospace; font-size: .72rem; color: #475569; margin-top: 2px; }
  .prov-chip .addr { font-size: .75rem; color: #334155; margin-top: 4px; line-height: 1.3; }
  .prov-chip .clear-btn {
    flex-shrink: 0; background: transparent; border: 0; color: #64748b;
    width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
  }
  .prov-chip .clear-btn:hover { background: #fee2e2; color: #b91c1c; }

  /* ── Tabla detalles: redondeo en header + datos como texto ───── */
  .tabla-wrap {
    border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;
  }
  table.tabla-detalles { margin: 0; font-size: .82rem; }
  table.tabla-detalles thead th {
    background: #f9fafb; color: #374151; font-weight: 700;
    font-size: .67rem; text-transform: uppercase; letter-spacing: .05em;
    border-bottom: 1px solid #e5e7eb; border-top: 0;
    padding: .55rem .55rem; white-space: nowrap;
  }
  table.tabla-detalles thead th:first-child { border-top-left-radius: 8px; }
  table.tabla-detalles thead th:last-child  { border-top-right-radius: 8px; }
  table.tabla-detalles tbody td {
    padding: .45rem .55rem; vertical-align: middle;
    border-bottom: 1px solid #f3f4f6; border-top: 0; color: #0f172a;
  }
  table.tabla-detalles tbody tr:last-child td { border-bottom: 1px solid #e5e7eb; }
  table.tabla-detalles tfoot td {
    padding: .55rem .55rem; vertical-align: middle;
    background: #f9fafb; font-weight: 700; font-size: .82rem;
    border-top: 1px solid #e5e7eb; border-bottom: 0; color: #0f172a;
    font-variant-numeric: tabular-nums;
  }
  table.tabla-detalles tfoot td.tot-lbl { color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; font-size: .68rem; }
  table.tabla-detalles tfoot td.tot-ret { color: #16a34a; font-weight: 700; }
  table.tabla-detalles tfoot td:first-child { border-bottom-left-radius: 8px; }
  table.tabla-detalles tfoot td:last-child  { border-bottom-right-radius: 8px; }

  /* Datos del datamarket: como texto plano (no input) */
  table.tabla-detalles .text-cell  { color: #0f172a; }
  table.tabla-detalles .mono-cell  { font-family: ui-monospace, monospace; font-weight: 600; color: #1f2937; }
  table.tabla-detalles .num-cell   { text-align: right; font-variant-numeric: tabular-nums; }
  table.tabla-detalles .moneda-tag {
    display: inline-block; font-size: .65rem; font-weight: 700;
    color: #4b5563; background: #f3f4f6; border: 1px solid #e5e7eb;
    padding: .1rem .45rem; border-radius: 4px;
  }

  /* Solo los inputs editables (F. Pago / Importe Pago / T. Cambio USD) */
  table.tabla-detalles input.form-control,
  table.tabla-detalles select.form-select { font-size: .82rem; height: 30px; padding: .2rem .45rem; }
  table.tabla-detalles .in-factor-cambio { width: 100%; }

  /* ── Select2 inline (compacto) ────────────────────────────────── */
  .inline-search { width: 280px; }
  .inline-search .select2-container { width: 100% !important; }
  .inline-search .select2-selection--single {
    height: 32px !important; min-height: 32px !important;
    border: 1px solid #d1d5db !important; border-radius: 6px !important;
  }
  .inline-search .select2-selection__rendered {
    line-height: 30px !important; font-size: .8rem; color: #6b7280;
  }
  .inline-search .select2-selection__arrow { height: 30px !important; }

  /* ── Selector de facturas: render del dropdown ────────────────── */
  .factura-result { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; padding: 2px 0; }
  .factura-result .left { min-width: 0; flex: 1; }
  .factura-result .top  { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
  .factura-result .tipo-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    background: #2563eb; color: #fff; padding: 1px 6px; border-radius: 3px;
  }
  .factura-result .serie {
    font-family: ui-monospace, monospace; font-weight: 700; color: #111827; font-size: 12px;
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
  <div class="ret-create-wrap">

    {{-- ────────── Encabezado ────────── --}}
    <div class="d-flex justify-content-between align-items-end mb-3 gap-3 flex-wrap">
      <div>
        <div class="page-subtitle">
          <a href="{{ route('retenciones.index') }}">Retenciones</a>
          <span class="text-muted"> / Nueva</span>
        </div>
        <h1 class="page-title">Nueva Retención</h1>
      </div>
      <div class="d-flex gap-1 align-items-center">
        <a href="{{ route('retenciones.index') }}" class="btn-ghost">
          Cancelar
        </a>
        <button type="button" id="btn_registrar" class="btn-flat-success">
          <i class="fa fa-check"></i> Registrar Retención
        </button>
      </div>
    </div>

    <form id="form_retencion" autocomplete="off">
      @csrf
      {{-- Hidden: serie/número resueltos en backend; proveedor del chip --}}
      <input type="hidden" id="serie"  name="serie"  value="{{ $serieDefault ?? '' }}">
      <input type="hidden" id="numero" name="numero" value="{{ $numeroSugerido ?? '' }}">
      <input type="hidden" id="proveedor_ruc"        name="numdocproveedor"      value="">
      <input type="hidden" id="proveedor_razon"      name="razonsocialproveedor" value="">
      <input type="hidden" id="proveedor_direccion"  name="direccionproveedor"   value="">

      {{-- ────────── Row: Datos del Comprobante (izq) + Proveedor (der) ────────── --}}
      <div class="row g-3 mb-3">
        {{-- Datos del Comprobante --}}
        <div class="col-md-6">
          <div class="card-soft p-3 h-100">
            <div class="section-title mb-2">Datos del Comprobante</div>
            {{-- Tres columnas en la misma fila → labels y valores alineados al
                 mismo baseline. --}}
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label small mb-1">Comprobante</label>
                <div class="serie-text {{ empty($serieDefault) ? 'is-empty' : '' }}">
                  @if (!empty($serieDefault))
                    {{ $serieDefault }}-{{ $numeroSugerido }}
                  @else
                    Sin serie
                  @endif
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Fecha Emisión *</label>
                <input type="date" id="fecha_emision" name="fecha_emision"
                       class="form-control form-control-sm"
                       value="{{ $fechaHoy }}" max="{{ $fechaHoy }}">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Observación</label>
                <input type="text" name="observacion"
                       class="form-control form-control-sm"
                       maxlength="250" placeholder="Opcional">
              </div>
            </div>
          </div>
        </div>

        {{-- Proveedor (Paso 1) --}}
        <div class="col-md-6">
          <div class="card-soft p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
              <div class="section-title with-step"><span class="step-badge">1</span>Proveedor</div>
              <div class="inline-search" id="proveedor_search_wrap">
                <select id="proveedor_select" class="form-select form-select-sm">
                  <option value=""></option>
                </select>
              </div>
            </div>

            <div id="proveedor_empty" class="text-center text-muted small py-3">
              <i class="fa fa-magnifying-glass d-block mb-1" style="font-size:1.1rem; opacity:.5;"></i>
              Busca un proveedor por RUC o razón social
            </div>

            <div id="proveedor_chip" class="prov-chip d-none">
              <div class="avatar" id="prov_avatar">·</div>
              <div class="body">
                <div class="name" id="prov_name"></div>
                <div class="ruc">RUC <span id="prov_ruc"></span></div>
                <div class="addr" id="prov_addr">—</div>
              </div>
              <button type="button" class="clear-btn" id="btn_limpiar_proveedor" title="Quitar proveedor">
                <i class="fa fa-xmark"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- ────────── Card: Documentos a Retener (Paso 2) ────────── --}}
      <div class="card-soft p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
          <div class="section-title with-step"><span class="step-badge">2</span>Documentos a Retener</div>
          <div class="inline-search" id="factura_search_wrap" style="width:340px;">
            <select id="factura_select" class="form-select form-select-sm" disabled>
              <option value="">Primero selecciona un proveedor…</option>
            </select>
          </div>
        </div>

        <div class="tabla-wrap">
          <table class="table table-sm align-middle tabla-detalles mb-0">
            <thead>
              <tr class="text-center">
                <th style="width:40px;">#</th>
                <th style="width:160px;">Serie-Número</th>
                <th style="width:110px;">F. Doc.</th>
                <th style="width:120px;">Importe Doc.</th>
                <th style="width:70px;">Mon.</th>
                <th style="width:90px;" title="Factor de tipo de cambio. Solo aplica para USD.">T. Cambio</th>
                <th style="width:135px;">F. Pago *</th>
                <th style="width:135px;">Importe Pago *</th>
                <th style="width:120px;">Retenido (PEN)</th>
                <th style="width:120px;">Neto (PEN)</th>
                <th style="width:42px;"></th>
              </tr>
            </thead>
            <tbody id="detalles_body"></tbody>
            <tfoot id="totales_foot" class="d-none">
              <tr>
                <td colspan="7" class="text-end tot-lbl">TOTALES (PEN)</td>
                <td class="text-end" id="tot_pagado">S/ 0.00</td>
                <td class="text-end tot-ret" id="tot_retenido">S/ 0.00</td>
                <td class="text-end" id="tot_neto">S/ 0.00</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div id="vacio_msg" class="text-center text-muted py-3 small">
          <i class="fa fa-inbox"></i> No has agregado documentos.
          Selecciona un proveedor y luego elige una factura del buscador.
        </div>
      </div>

    </form>
  </div>

  @push('js-scripts')
    <script src="{{ asset('js/retenciones/create.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
