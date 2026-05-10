{{-- Parcial: tabla de retenciones + KPIs ($list, $totales) --}}

@php
  // Esquema de estados (DB Peru): estadosunat='A' (Aceptada), estadoproceso='C/F/P', estadodocumento='1'/'11'(anulada)
  $estadoSunatLabel = function ($r) {
    if ($r->estadosunat === 'A')         return ['label' => 'Aceptada',  'class' => 'b-acep'];
    if ($r->estadoproceso === 'F')        return ['label' => 'Fallida',   'class' => 'b-rech'];
    if ($r->estadoproceso === 'C')        return ['label' => 'Aceptada',  'class' => 'b-acep'];
    if ($r->estadoproceso === 'P' || $r->estadoproceso === null) return ['label' => 'Pendiente', 'class' => 'b-pend'];
    // Compat con codigos legados ('00','05','09')
    if ($r->estadosunat === '05')         return ['label' => 'Aceptada',  'class' => 'b-acep'];
    if ($r->estadosunat === '09')         return ['label' => 'Rechazada', 'class' => 'b-rech'];
    return ['label' => $r->estadosunat ?? 'Sin estado', 'class' => 'b-pend'];
  };
  $fmt = function ($v, $mon = 'PEN') {
    $sym = $mon === 'USD' ? 'US$' : 'S/';
    return $sym.' '.number_format((float) $v, 2, '.', ',');
  };
@endphp

{{-- Datos para refrescar KPIs --}}
<script id="kpi_data" type="application/json">@json($totales)</script>

<table id="tabla_retenciones" class="table align-middle" style="width:100%">
  <thead>
    <tr>
      <th>Serie-Numero</th>
      <th>F. Emision</th>
      <th>Proveedor</th>
      <th class="text-end">Tasa</th>
      <th class="text-end">Total Pagado</th>
      <th class="text-end">Retenido</th>
      <th class="text-center">Estado SUNAT</th>
      <th class="text-center">Estado Doc.</th>
      <th class="text-center">Acciones</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($list as $r)
      @php
        $est        = $estadoSunatLabel($r);
        $estLabel   = $est['label'];
        $estClass   = $est['class'];
        $estDocAnul = ($r->estadodocumento === '11');
      @endphp
      <tr>
        <td class="serie-cell">{{ $r->serienumero }}</td>
        <td class="text-nowrap">{{ $r->fechaemision }}</td>
        <td>
          <div class="prov-name text-truncate" style="max-width: 280px;" title="{{ $r->razonsocialproveedor }}">
            {{ $r->razonsocialproveedor }}
          </div>
          <div class="ruc-mono">RUC: {{ $r->numdocproveedor }}</div>
        </td>
        <td class="text-end monto">{{ rtrim(rtrim($r->tasaretencion ?? '0', '0'), '.') }}%</td>
        <td class="text-end monto">{{ $fmt($r->importetotalpagado, $r->monedaimportetotalpagado) }}</td>
        <td class="text-end ret-monto">{{ $fmt($r->importetotalretenido, $r->monedaimportetotalretenido) }}</td>
        <td class="text-center">
          <span class="ret-badge {{ $estClass }}"><span class="dot"></span>{{ $estLabel }}</span>
        </td>
        <td class="text-center">
          @if ($estDocAnul)
            <span class="ret-badge b-anul"><i class="fa fa-ban"></i> Anulada</span>
          @else
            <span class="ret-badge b-act"><i class="fa fa-circle-check"></i> Activa</span>
          @endif
        </td>
        <td class="text-center text-nowrap ret-actions">
          <button type="button" class="btn btn-outline-primary ver_retencion"
                  data-serienumero="{{ $r->serienumero }}" title="Ver detalle">
            <i class="fa fa-eye"></i>
          </button>
          <a href="{{ route('retenciones.pdf', ['serienumero' => $r->serienumero]) }}"
             target="_blank" class="btn btn-outline-secondary" title="PDF">
            <i class="fa fa-file-pdf"></i>
          </a>
          @if (!$estDocAnul)
            <button type="button" class="btn btn-outline-danger anular_retencion"
                    data-serienumero="{{ $r->serienumero }}" title="Anular">
              <i class="fa fa-ban"></i>
            </button>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
