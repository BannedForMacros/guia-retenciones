{{-- Parcial: tabla de retenciones + KPIs ($list, $totales) --}}

@php
  // Estado SUNAT. `estadosunat` guarda el CodigoRespuesta de la consulta/envio:
  //   A=Aceptada  B=Rechazada  O=Observado  P=Pendiente   ('05'/'09' = legados)
  // Si no hay codigo, se deriva del proceso del envio (estadoproceso C/F).
  $estadoSunatLabel = function ($r) {
    switch ($r->estadosunat) {
      case 'A':  case '05': return ['label' => 'Aceptada',  'class' => 'b-acep'];
      case 'B':  case '09': return ['label' => 'Rechazada', 'class' => 'b-rech'];
      case 'O':             return ['label' => 'Observado', 'class' => 'b-obs'];
      case 'P':             return ['label' => 'Pendiente', 'class' => 'b-pend'];
    }
    if ($r->estadoproceso === 'F') return ['label' => 'Fallida',  'class' => 'b-rech'];
    if ($r->estadoproceso === 'C') return ['label' => 'Aceptada', 'class' => 'b-acep'];
    return ['label' => 'Pendiente', 'class' => 'b-pend'];
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
        // Reenvio: solo cuando NUNCA fue confirmado por SUNAT (Pendiente/Fallida) y no esta anulada.
        $estResuelto  = in_array($r->estadosunat, ['A', 'O', '05', 'B', '09'], true) || $r->estadoproceso === 'C';
        $puedeReenviar = !$estDocAnul && !$estResuelto;
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
            @if (!empty($r->nro_ticket_baja))
              <div class="ticket-baja mt-1" title="ID: {{ $r->iddocumento_baja }}{{ $r->fecha_envio_baja ? ' · '.$r->fecha_envio_baja : '' }}">
                Tk: {{ $r->nro_ticket_baja }}
              </div>
            @endif
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
            <button type="button" class="btn btn-outline-info consultar_estado"
                    data-serienumero="{{ $r->serienumero }}" title="Consultar estado en SUNAT">
              <i class="fa fa-arrows-rotate"></i>
            </button>
          @endif
          @if ($puedeReenviar)
            <button type="button" class="btn btn-outline-warning reenviar_retencion"
                    data-serienumero="{{ $r->serienumero }}" title="Reenviar a SUNAT">
              <i class="fa fa-paper-plane"></i>
            </button>
          @endif
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
