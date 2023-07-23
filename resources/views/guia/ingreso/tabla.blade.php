@inject('carbon', 'Carbon\Carbon')
<table class="table table-hover table-striped table-sm table-bordered" id="tabla_guias" style="width: 100%">
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
      <td class="align-middle">{{ $item->serie }}-{{$item->numero}}</td>
      <td class="align-middle">{{ $item->proveedor_nombre }}</td>
      <td class="align-middle">{{ $carbon::parse($item->fecha_emision)->format('d/m/Y') }}</td>
      <td class="align-middle">{{ $item->total_venta }}</td>
      <td class="align-middle">
        <a href="{{ route('guiaingreso.pdf', ['guia'=>$item->id]) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> Ver</a>
      </td>
    </tr>
        
    @endforeach
  </tbody>
</table>