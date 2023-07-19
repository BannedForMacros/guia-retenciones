@extends('layouts.app')

<style>
  body {
    background-color: #f5f7fa !important;
  }
</style>

@section('content')
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <h5><i class="fa fa-list"></i> Guia de Salida Generadas</h5>
        
        <div class="row mt-4">
          <div class="col-md-12">
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
                    <button class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> Ver</button>
                  </td>
                </tr>
                    
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
  @push('js-scripts')
    <script src="{{ asset('js/guias/salida/index.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
