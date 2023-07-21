<table class="table table-hover table-striped table-sm table-bordered">
  <thead>
    <th>Condicion</th>
    <th>Serie</th>
    <th>Cliente</th>
    <th>Fecha Emision</th>
    <th>Importe</th>
    <th>Accion</th>
  </thead>
  <tbody>
    @foreach ($list as $item)
    <tr>
      <td class="align-middle">Generada</td>
      <td class="align-middle">F00{{ $item->serie }}-{{$item->numero}}</td>
      <td class="align-middle">{{ $item->cliente_id }}</td>
      <td class="align-middle">{{ $item->fecha_emision }}</td>
      <td class="align-middle">{{ $item->total_venta }}</td>
      <td class="align-middle">
        <a href="{{ route('guiasalida.pdf', ['guia'=>$item->id]) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> Ver</a>
      </td>
    </tr>
        
    @endforeach
  </tbody>
</table>