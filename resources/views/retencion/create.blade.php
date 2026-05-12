@extends('layouts.app')

<style>
  /* ─── Paleta Data Business ─────────────────────────────────────── */
  :root {
    --db-navy:    #0D2E6E;  /* navy oscuro — acciones primarias, hover fuerte */
    --db-blue:    #0E6CB5;  /* azul medio — títulos, badges, borde sección */
    --db-cyan:    #00AEEF;  /* cyan/cielo — avatares, íconos accent */
    --db-teal:    #2ECBA1;  /* teal/verde acento — highlight financiero */
    --db-teal-d:  #22b890;  /* teal hover */
    --db-text:    #1A3A5C;  /* azul oscuro — títulos en fondo blanco */
    --db-bg:      #F5F5F5;  /* gris claro — fondo de secciones */
    --db-muted:   #6b7280;
    --db-border:  #e5e7eb;
  }

  body { background-color: var(--db-bg) !important; }

  /* ── Layout general ────────────────────────────────────────────── */
  .ret-create-wrap { max-width: 1500px; margin: 0 auto; padding: 14px 18px; }

  /* Cards con accent superior brand (3px) — identidad visible sin saturar */
  .card-soft {
    background: #fff;
    border: 1px solid var(--db-border);
    border-radius: 8px;
    border-top: 3px solid var(--db-blue);
    box-shadow: 0 1px 2px rgba(13, 46, 110, .04);
  }

  /* Section titles: texto en azul brand, más presencia */
  .section-title {
    font-size: 11.5px; font-weight: 800; color: var(--db-blue);
    text-transform: uppercase; letter-spacing: .08em;
    border-left: 3px solid var(--db-cyan); padding-left: .55rem; margin-bottom: 0;
  }

  /* ── Header ────────────────────────────────────────────────────── */
  .page-title {
    font-size: 1.55rem; font-weight: 800; color: var(--db-text); margin: 0;
    letter-spacing: -.02em; line-height: 1.15;
    /* Subrayado brand sólido — un solo color */
    padding-bottom: 6px;
    background-image: linear-gradient(var(--db-blue), var(--db-blue));
    background-size: 48px 3px;
    background-repeat: no-repeat;
    background-position: 0 100%;
  }
  .page-subtitle {
    font-size: .78rem; color: var(--db-muted); margin-bottom: 4px;
  }
  .page-subtitle a { color: var(--db-blue); text-decoration: none; font-weight: 600; }
  .page-subtitle a:hover { color: var(--db-navy); text-decoration: underline; }

  /* ── Botones flat ─────────────────────────────────────────────── */
  .btn-flat-success,
  .btn-ghost {
    border: 0; border-radius: 6px;
    padding: .55rem 1.1rem; font-size: .85rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 7px;
    box-shadow: none; line-height: 1.2; transition: background .15s, color .15s;
  }
  /* Acción primaria: navy con texto blanco (alto contraste, AAA).
     El teal queda reservado para highlights financieros. */
  .btn-flat-success {
    background: var(--db-navy); color: #fff;
  }
  .btn-flat-success:hover { background: var(--db-blue); color: #fff; }
  .btn-flat-success:disabled { background: #cbd5e1; color: #fff; cursor: not-allowed; }
  .btn-flat-success:disabled:hover { background: #cbd5e1; }
  .btn-ghost {
    background: transparent; color: var(--db-muted); font-weight: 500;
  }
  .btn-ghost:hover { background: #ECEFF3; color: var(--db-text); }

  /* ── Step badge en títulos de sección (guía visual de orden) ──── */
  .step-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 50%;
    background: var(--db-blue);
    color: #fff;
    font-size: .7rem; font-weight: 800;
    margin-right: 8px; flex-shrink: 0;
  }
  .section-title.with-step { display: inline-flex; align-items: center; }

  /* ── Comprobante: TEXTO puro en azul brand (visible) ──────────── */
  .serie-text {
    height: calc(1.5em + .5rem + 2px);
    display: flex; align-items: center;
    padding: 0;
    font-family: inherit;
    font-weight: 800; color: var(--db-blue);
    font-size: 1rem; letter-spacing: .01em;
  }
  .serie-text.is-empty {
    color: #b45309; font-style: italic; font-weight: 500; font-size: .85rem;
  }

  /* ── Proveedor: chip con tinte cyan ─────────────────────────── */
  .prov-chip {
    display: flex; align-items: flex-start; gap: .75rem;
    background: #E6F3FB;
    border: 1px solid #BFDDF1; border-radius: 8px;
    padding: .7rem .85rem;
  }
  .prov-chip .avatar {
    width: 36px; height: 36px; flex-shrink: 0;
    background: var(--db-cyan);
    color: #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .85rem;
  }
  .prov-chip .body { min-width: 0; flex: 1; }
  .prov-chip .name { font-weight: 700; color: var(--db-text); font-size: .92rem; line-height: 1.2; }
  .prov-chip .ruc  { font-family: ui-monospace, monospace; font-size: .73rem; color: var(--db-blue); margin-top: 2px; font-weight: 700; }
  .prov-chip .addr { font-size: .75rem; color: #475569; margin-top: 4px; line-height: 1.3; }
  .prov-chip .clear-btn {
    flex-shrink: 0; background: transparent; border: 0; color: var(--db-muted);
    width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
  }
  .prov-chip .clear-btn:hover { background: #fee2e2; color: #b91c1c; }

  /* ── Tabla detalles: redondeo en header + datos como texto ───── */
  .tabla-wrap {
    border: 1px solid var(--db-border); border-radius: 8px; overflow: hidden;
  }
  table.tabla-detalles { margin: 0; font-size: .82rem; }
  table.tabla-detalles thead th {
    /* Header sólido en navy brand con texto blanco */
    background: var(--db-navy);
    color: #fff; font-weight: 700;
    font-size: .68rem; text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 0; border-top: 0;
    padding: .6rem .55rem; white-space: nowrap;
  }
  table.tabla-detalles thead th:first-child { border-top-left-radius: 8px; }
  table.tabla-detalles thead th:last-child  { border-top-right-radius: 8px; }
  table.tabla-detalles tbody td {
    padding: .45rem .55rem; vertical-align: middle;
    border-bottom: 1px solid #f3f4f6; border-top: 0; color: var(--db-text);
  }
  table.tabla-detalles tbody tr:hover td { background: #FAFCFE; }
  table.tabla-detalles tbody tr:last-child td { border-bottom: 1px solid var(--db-border); }
  table.tabla-detalles tfoot td {
    padding: .6rem .55rem; vertical-align: middle;
    background: #F0FAFE; font-weight: 700; font-size: .82rem;
    border-top: 2px solid var(--db-cyan); border-bottom: 0; color: var(--db-text);
    font-variant-numeric: tabular-nums;
  }
  table.tabla-detalles tfoot td.tot-lbl { color: var(--db-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .04em; font-size: .68rem; }
  /* Highlight financiero: total retenido en teal (palette accent) */
  table.tabla-detalles tfoot td.tot-ret { color: var(--db-teal); font-weight: 700; }
  table.tabla-detalles tfoot td:first-child { border-bottom-left-radius: 8px; }
  table.tabla-detalles tfoot td:last-child  { border-bottom-right-radius: 8px; }

  /* Datos del datamarket: como texto plano (no input) */
  table.tabla-detalles .text-cell  { color: var(--db-text); }
  table.tabla-detalles .mono-cell  { font-family: ui-monospace, monospace; font-weight: 600; color: var(--db-text); }
  table.tabla-detalles .num-cell   { text-align: right; font-variant-numeric: tabular-nums; }
  /* Filas: el "Retenido" por línea también en teal para consistencia */
  table.tabla-detalles .out-retenido { color: var(--db-teal); font-weight: 700; }

  /* Tag de moneda — PEN azul, USD ámbar para destacar que requiere TC */
  table.tabla-detalles .moneda-tag {
    display: inline-block; font-size: .68rem; font-weight: 800;
    padding: .12rem .5rem; border-radius: 4px;
    border: 1px solid;
  }
  table.tabla-detalles .moneda-tag.is-pen {
    color: var(--db-blue); background: #E6F3FB; border-color: #BFDDF1;
  }
  table.tabla-detalles .moneda-tag.is-usd {
    color: #92400e; background: #fef3c7; border-color: #fcd34d;
  }

  /* Filas en USD: tinte sutil + borde izquierdo ámbar para identificarlas
     a primera vista (requieren tipo de cambio editable). */
  table.tabla-detalles tr.is-usd > td {
    background: #FFFBEB;
  }
  table.tabla-detalles tr.is-usd > td:first-child {
    box-shadow: inset 3px 0 0 #f59e0b;
  }
  table.tabla-detalles tr.is-usd .in-factor-cambio {
    border-color: #f59e0b;
    background: #FFFBEB;
    font-weight: 700;
    color: #92400e;
  }
  table.tabla-detalles tr.is-usd .in-factor-cambio:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 .15rem rgba(245, 158, 11, .15);
  }
  /* Pequeño indicador "PEN" debajo del retenido en filas USD */
  table.tabla-detalles tr.is-usd .out-retenido,
  table.tabla-detalles tr.is-usd .out-neto {
    position: relative;
  }
  table.tabla-detalles tr.is-usd .out-retenido::after,
  table.tabla-detalles tr.is-usd .out-neto::after {
    content: "≈ PEN";
    display: block;
    font-size: .58rem; font-weight: 600;
    color: #94a3b8; letter-spacing: .04em;
    text-transform: uppercase; margin-top: 1px;
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
    border: 1px solid var(--db-border) !important; border-radius: 6px !important;
  }
  .inline-search .select2-selection--single:focus,
  .inline-search .select2-container--focus .select2-selection--single {
    border-color: var(--db-blue) !important;
    box-shadow: 0 0 0 .15rem rgba(14, 108, 181, .12) !important;
  }
  .inline-search .select2-selection__rendered {
    line-height: 30px !important; font-size: .8rem; color: var(--db-muted);
  }
  .inline-search .select2-selection__arrow { height: 30px !important; }

  /* Inputs editables: focus en azul DB para consistencia */
  .form-control:focus, .form-select:focus {
    border-color: var(--db-blue) !important;
    box-shadow: 0 0 0 .15rem rgba(14, 108, 181, .12) !important;
  }

  /* ── Selector de facturas: render del dropdown ────────────────── */
  .factura-result { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; padding: 2px 0; }
  .factura-result .left { min-width: 0; flex: 1; }
  .factura-result .top  { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
  .factura-result .tipo-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    background: var(--db-blue); color: #fff; padding: 1px 6px; border-radius: 3px;
  }
  .factura-result .serie {
    font-family: ui-monospace, monospace; font-weight: 700; color: var(--db-text); font-size: 12px;
  }
  .factura-result .meta { font-size: 10px; color: var(--db-muted); margin-top: 1px; }
  .factura-result .importe { font-weight: 700; color: var(--db-text); font-size: 12px; white-space: nowrap; }
  .factura-result.is-retenida .serie { text-decoration: line-through; color: #9ca3af; }
  .factura-result.is-retenida .importe { color: #9ca3af; }
  .factura-result .badge-retenida {
    font-size: 9px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: #92400e; background: #fef3c7; border: 1px solid #fde68a;
    padding: 1px 6px; border-radius: 3px;
  }
  /* Badge "Parcial": la factura ya tiene retencion previa pero queda saldo */
  .factura-result .badge-parcial {
    font-size: 9px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: var(--db-blue); background: #E6F3FB; border: 1px solid #BFDDF1;
    padding: 1px 6px; border-radius: 3px;
  }
  /* Saldo destacado en la columna importe del dropdown */
  .factura-result .importe { text-align: right; line-height: 1.1; font-size: 11px; color: var(--db-muted); font-weight: 600; }
  .factura-result .importe br + * { font-size: 13px; color: var(--db-teal); font-weight: 700; }
  .select2-results__option[aria-disabled="true"] { background: var(--db-bg); cursor: not-allowed; }

  /* Pista "Pagando X · queda Y" debajo del input importe pago */
  table.tabla-detalles .saldo-hint {
    font-size: .65rem; line-height: 1.1; min-height: 12px;
    font-variant-numeric: tabular-nums;
  }
  table.tabla-detalles .saldo-hint.text-success { color: var(--db-teal) !important; }
  table.tabla-detalles .saldo-hint.text-warning { color: #b45309 !important; }
  table.tabla-detalles .saldo-hint.text-danger  { color: #b91c1c !important; font-weight: 700; }
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

      {{-- ────────── Row: Proveedor (izq, Paso 1) + Datos del Comprobante (der) ────────── --}}
      <div class="row g-3 mb-3">
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
                <th style="width:155px;">Serie-Número</th>
                <th style="width:105px;">F. Doc.</th>
                <th style="width:115px;">Importe Doc.</th>
                <th style="width:65px;">Mon.</th>
                <th style="width:90px;" title="Factor de tipo de cambio. Solo aplica para USD.">T. Cambio</th>
                <th style="width:130px;">F. Pago *</th>
                <th style="width:75px;" title="Número de pago / cuota">N° Pago</th>
                <th style="width:130px;">Importe Pago *</th>
                <th style="width:115px;">Retenido (PEN)</th>
                <th style="width:115px;">Neto (PEN)</th>
                <th style="width:42px;"></th>
              </tr>
            </thead>
            <tbody id="detalles_body"></tbody>
            <tfoot id="totales_foot" class="d-none">
              <tr>
                <td colspan="8" class="text-end tot-lbl">TOTALES (PEN)</td>
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
    <script src="{{ asset('js/retenciones/utils.js?v=') }}{{ rand() }}"></script>
    <script src="{{ asset('js/retenciones/create.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
