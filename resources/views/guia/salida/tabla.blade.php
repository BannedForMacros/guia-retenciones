@inject('carbon', 'Carbon\Carbon')
<table class="table table-hover table-striped table-sm table-bordered" id="tabla_guias" style="width: 100%">
  <thead>
    <th>Condicion</th>
    <th>Serie</th>
    <th>Razon Social</th>
    <th>Fecha Emision</th>
    <th>Importe</th>
    <th>Estado</th>
    <th>Accion</th>
  </thead>
  <tbody>
    @foreach ($list as $item)
    <tr>
      <td class="align-middle">{{ $item->estado_nombre}}</td>
      <td class="align-middle">{{ $item->serie }}-{{$item->numero}}</td>
      <td class="align-middle">{{ $item->texto_razon_social }}</td>
      <td class="align-middle">{{ $carbon::parse($item->fecha_emision)->format('d/m/Y') }}</td>
      <td class="align-middle">{{ $item->total_venta }}</td>
      <td class="align-middle">{{ $item->estado_nombre }}</td>
      <td class="align-middle">
        {{-- <a href="{{ route('guiasalida.pdf', ['guia'=>$item->id]) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> Ver</a> --}}

        <div class="btn-group btn-group-sm">
          {{-- <a href="{{ route('guiasalida.pdf', ['guia'=>$item->id]) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> Ver</a> --}}
          <a href="{{ $item->url_pdf }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> Ver</a>
          <button type="button" class="btn btn-dark dropdown-toggle dropdown-toggle-split"
            data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle Dropdown</span>
          </button>
          <ul class="dropdown-menu">
            {{-- <li><hr class="dropdown-divider"></li> --}}
            @if ($item->guia_estado_id == 4)
            <li><a class="dropdown-item" href="{{ route('guiasalida.continuar', ['guia'=>$item->id]) }}"><i class="fa fa-edit"></i> Continuar</a></li>
                
            @endif
          </ul>
        </div>

      </td>
    </tr>
        
    @endforeach
  </tbody>
</table>