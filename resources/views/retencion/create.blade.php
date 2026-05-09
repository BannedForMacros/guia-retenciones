@extends('layouts.app')

<style>
  body { background-color: #f5f7fa !important; }

  /* ── Layout ─────────────────────────────────────────────────────── */
  .ret-create-wrap { max-width: 1500px; margin: 0 auto; padding: 14px 18px; }
  .card-soft { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
  .section-title {
    font-size: 11px; font-weight: 700; color: #1f2937;
    text-transform: uppercase; letter-spacing: .08em;
    border-left: 3px solid #2563eb; padding-left: .5rem; margin-bottom: 0;
  }

  /* ── Header del documento (serie-número compacto) ───────────────── */
  .doc-tag {
    display: inline-flex; align-items: center; gap: .35rem;
    background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
    border-radius: 6px; padding: .25rem .55rem;
    font-family: ui-monospace, Menlo, Consolas, monospace;
    font-weight: 700; font-size: .85rem;
  }
  .doc-tag .label { font-size: .65rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; font-family: inherit; }
  .doc-tag.is-empty { background: #fff7ed; color: #b45309; border-color: #fed7aa; }
  .meta-pill {
    display: inline-flex; align-items: center; gap: .35rem;
    background: #f9fafb; color: #4b5563; border: 1px solid #e5e7eb;
    border-radius: 999px; padding: .15rem .55rem; font-size: .7rem;
  }

  /* ── Proveedor: chip seleccionado ───────────────────────────────── */
  .prov-chip {
    display: flex; align-items: flex-start; gap: .65rem;
    background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px;
    padding: .55rem .7rem;
  }
  .prov-chip .avatar {
    width: 32px; height: 32px; flex-shrink: 0;
    background: #0284c7; color: #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .8rem;
  }
  .prov-chip .body { min-width: 0; flex: 1; }
  .prov-chip .name { font-weight: 700; color: #0f172a; font-size: .88rem; line-height: 1.2; }
  .prov-chip .ruc  { font-family: ui-monospace, monospace; font-size: .72rem; color: #075985; margin-top: 1px; }
  .prov-chip .addr { font-size: .72rem; color: #64748b; margin-top: 2px; }
  .prov-chip .clear-btn {
    flex-shrink: 0; background: transparent; border: 0; color: #64748b;
    width: 26px; height: 26px; border-radius: 6px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
  }
  .prov-chip .clear-btn:hover { background: #fee2e2; color: #b91c1c; }

  /* ── Tabla detalles ─────────────────────────────────────────────── */
  table.tabla-detalles input.form-control,
  table.tabla-detalles select.form-select { font-size: 12px; }
  table.tabla-detalles .in-factor-cambio:disabled { background: #f3f4f6; color: #9ca3af; }

  /* ── Totales ────────────────────────────────────────────────────── */
  .totales .lbl { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
  .totales .val { font-weight: 700; font-variant-numeric: tabular-nums; }
  .totales .val.big { font-size: 18px; color: #16a34a; }
  .totales .totales-hint { font-size: 10px; color: #9ca3af; font-weight: 500; letter-spacing: .04em; }

  /* ── Select2 inline (para los buscadores compactos en headers) ──── */
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

  /* ── Selector de facturas: cómo se ve cada opción del dropdown ──── */
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
    <div class="d-flex justify-content-between align-items-end mb-3 gap-2 flex-wrap">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('retenciones.index') }}">Retenciones</a></li>
            <li class="breadcrumb-item active">Nueva</li>
          </ol>
        </nav>
        <h5 class="mb-1"><i class="fa fa-plus-circle"></i> Nueva Retención</h5>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <span class="meta-pill"><i class="fa fa-building"></i> {{ $razonSocial }} · {{ $rucempresa }}</span>
          <span class="meta-pill"><i class="fa fa-percent"></i> Régimen General · 3 %</span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('retenciones.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fa fa-times"></i> Cancelar
        </a>
        <button type="button" id="btn_registrar" class="btn btn-sm btn-success">
          <i class="fa fa-save"></i> Registrar Retención
        </button>
      </div>
    </div>

    <form id="form_retencion" autocomplete="off">
      @csrf
      {{-- Hidden: la serie/número se determinan en backend, no se editan en el form --}}
      <input type="hidden" id="serie"  name="serie"  value="{{ $serieDefault ?? '' }}">
      <input type="hidden" id="numero" name="numero" value="{{ $numeroSugerido ?? '' }}">
      <input type="hidden" id="proveedor_ruc"        name="numdocproveedor"      value="">
      <input type="hidden" id="proveedor_razon"      name="razonsocialproveedor" value="">
      <input type="hidden" id="proveedor_direccion"  name="direccionproveedor"   value="">

      <div class="row g-3">
        {{-- ────── Card 1: Datos del Comprobante (compacto) ────── --}}
        <div class="col-md-6">
          <div class="card-soft p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title">Datos del Comprobante</div>
              <span class="doc-tag {{ empty($serieDefault) ? 'is-empty' : '' }}" id="lbl_serie_numero">
                <span class="label">Serie · N°</span>
                @if (!empty($serieDefault))
                  {{ $serieDefault }}-{{ $numeroSugerido }}
                @else
                  Sin serie configurada
                @endif
              </span>
            </div>

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small mb-1"><i class="fa fa-calendar"></i> Fecha Emisión *</label>
                <input type="date" id="fecha_emision" name="fecha_emision"
                       class="form-control form-control-sm"
                       value="{{ $fechaHoy }}" max="{{ $fechaHoy }}">
              </div>
              <div class="col-md-6">
                <label class="form-label small mb-1"><i class="fa fa-pen"></i> Observación</label>
                <input type="text" name="observacion"
                       class="form-control form-control-sm"
                       maxlength="250" placeholder="Opcional">
              </div>
            </div>

            @if (empty($serieDefault))
              <div class="alert alert-warning small mt-2 mb-0 py-2">
                <i class="fa fa-triangle-exclamation"></i>
                No hay series configuradas. Configúralas desde el listado
                (<a href="{{ route('retenciones.index') }}">Retenciones → Series</a>).
              </div>
            @endif
          </div>
        </div>

        {{-- ────── Card 2: Proveedor ────── --}}
        <div class="col-md-6">
          <div class="card-soft p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
              <div class="section-title">Proveedor</div>
              {{-- Buscador a la derecha del título — compacto --}}
              <div class="inline-search" id="proveedor_search_wrap">
                <select id="proveedor_select"></select>
              </div>
            </div>

            {{-- Estado vacío --}}
            <div id="proveedor_empty" class="text-center text-muted small py-3">
              <i class="fa fa-magnifying-glass d-block mb-1" style="font-size:1.1rem; opacity:.5;"></i>
              Busca un proveedor por RUC o razón social
            </div>

            {{-- Chip cuando ya hay proveedor seleccionado --}}
            <div id="proveedor_chip" class="prov-chip d-none">
              <div class="avatar" id="prov_avatar">·</div>
              <div class="body">
                <div class="name" id="prov_name"></div>
                <div class="ruc">RUC <span id="prov_ruc"></span></div>
                <div class="addr" id="prov_addr"></div>
              </div>
              <button type="button" class="clear-btn" id="btn_limpiar_proveedor" title="Quitar proveedor">
                <i class="fa fa-xmark"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- ────── Card 3: Documentos a Retener ────── --}}
      <div class="card-soft p-3 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
          <div class="section-title">Documentos a Retener</div>
          {{-- Buscador inline a la derecha --}}
          <div class="d-flex gap-2 align-items-center">
            <div class="inline-search" id="factura_search_wrap" style="width:340px;">
              <select id="factura_select" disabled></select>
            </div>
            <button type="button" id="btn_agregar_linea" class="btn btn-sm btn-outline-secondary"
                    title="Agregar una línea en blanco (manual)">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle tabla-detalles">
            <thead class="table-light">
              <tr class="text-center small">
                <th style="width:40px;">#</th>
                <th style="width:110px;">Tipo Doc *</th>
                <th style="width:150px;">Serie-Número *</th>
                <th style="width:130px;">F. Doc.</th>
                <th style="width:120px;">Importe Doc.</th>
                <th style="width:75px;">Mon.</th>
                <th style="width:90px;" title="Factor de tipo de cambio. Solo aplica para USD.">T. Cambio</th>
                <th style="width:130px;">F. Pago *</th>
                <th style="width:130px;">Importe Pago *</th>
                <th style="width:120px;">Retenido (PEN)</th>
                <th style="width:120px;">Neto (PEN)</th>
                <th style="width:50px;"></th>
              </tr>
            </thead>
            <tbody id="detalles_body"></tbody>
          </table>
        </div>

        <div id="vacio_msg" class="text-center text-muted py-3 small">
          <i class="fa fa-inbox"></i> No has agregado documentos.
          Selecciona un proveedor y luego elige una factura del buscador.
        </div>
      </div>

      {{-- ────── Card 4: Totales ────── --}}
      <div class="card-soft p-3 mt-3 totales">
        <div class="d-flex justify-content-between align-items-end mb-2">
          <span class="totales-hint">TOTALES EN PEN (USD se convierte usando el factor por línea)</span>
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

  @push('js-scripts')
    <script src="{{ asset('js/retenciones/create.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
