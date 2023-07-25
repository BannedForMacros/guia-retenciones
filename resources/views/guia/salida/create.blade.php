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
        <h5><i class="fa fa-ticket"></i> Guia de Salida</h5>
        <form name="form_store" id="form_store" onkeydown="return event.key != 'Enter';" >
          @csrf
          <div class="row">
            <div class="col-md-12">
              <div class="row">
                <div class="col-md-6">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="row">
                        <h5>Guia</h5>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">Serie</label>
                          <select class="form-select" name="serie" id="serie">
                            @foreach ($listSeries as $item)
                              <option value="{{ $item->numserie }}">{{ $item->numserie }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">Fecha Emision</label>
                          <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-md-6 mb-2">
                          {{-- <label class="form-label">Estado</label> --}}
                          <label class="form-label">Enviar a sunat</label>
                          {{-- <input type="text" class="form-control" readonly value="GENERADA"> --}}
                          <select class="form-select" name="envio_sunat" id="envio-sunat">
                            <option value="0">No</option>
                            <option value="1">Si</option>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Comprobante Pago</label>
                          <input type="text" class="form-control" name="comprobante_pago">
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-2">
                      <label class="form-label">Pedido</label>
                      <div class="row">
                        <div class="col-md-3">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                            <label class="form-check-label" for="flexCheckDefault">
                              Interno
                            </label>
                          </div>
                        </div>
                        <div class="col-md-9">
                          <div class="row no-gutters">
                            <div class="col-md-5">
                              <input type="text" class="form-control" placeholder="Serie" name="pedido_serie">
                            </div>
                            <div class="col-md-7">
                              <input type="text" class="form-control" placeholder="Numero" name="pedido_numero">
                            </div>
  
                          </div>
                        </div>
                      </div>
                    </div>
  
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="row">
                    <div class="col-md-10 mb-2">
                      <label class="form-label">Vendedor</label>
                      <select class="form-select select_2" name="vendedor_id" id="vendedor_id" style="width: 100%">
                        @foreach ($listVendedores as $item)
                        <option value="{{ $item->codTrabajador }}" data-vendedor_nombre="{{ "{$item->apellidos} {$item->nombres}" }}">{{ "{$item->apellidos} {$item->nombres}" }}</option>
                            
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-10 mb-2">
                      <div class="row">
                        <div class="col-md-12">
                          <label class="form-label">Proveedor</label>
                          <div class="row g-2">
                            <div class="col-md-3">
                              <select id="tipo_busqueda_proveedor" class="form-select" style="width: 100%">
                                <option value="3">Razon Social</option>
                                <option value="2">RUC</option>
                              </select>
                            </div>
                            <div class="col-md-9">
                              <select class="form-select" id="proveedor_id" name="proveedor_id" data-placeholder="Buscar un proveedor"></select>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-12" hidden>
                          <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="guia_valodada" id="guia_valorada">
                            <label class="form-check-label" for="guia_valorada">
                              Guia Valorada
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
  
          </div>
          <div class="row mt-2">
            <div class="col-md-6">
              <h5>Datos el Cliente</h5>
              <div class="row">
                <div class="col-md-12">
                  <label class="form-label">Cliente</label>
                  <div class="row g-2">
                    <div class="col-md-3">
                      <select id="tipo_busqueda_cliente" class="form-select">
                        <option value="4">Nombre</option>
                        <option value="2">RUC</option>
                        <option value="3">Documento</option>
                      </select>
                    </div>
                    <div class="col-md-9">
                      <select class="form-select" name="cliente_id" id="cliente_id" style="width: 100%" data-placeholder="Buscar Cliente"></select>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Direccion</label>
                  <input type="text" class="form-control"  name="direccion" id="direccion" placeholder="Direccion del cliente">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="row">
                <div class="col-md-6">
                  <div class="row mt-4">
                    <div class="col-md-6">
                      <label class="form-label">Divisa</label>
                      <select class="form-select" name="divisa_id" id="divisa_id">
                        <option value="1" data-nombre="Soles">Soles</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">F Pago</label>
                      <select class="form-select" name="forma_pago_id" id="forma_pago_id">
                        @foreach ($listFormasPago as $item)
                          <option value="{{ $item->codFormaPago }}" data-nombre="{{ $item->descripcion }}">{{ $item->descripcion }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Lista Precio</label>
                      <select class="form-select" name="codlistaprecio" id="codlistaprecio">
                        @foreach ($listPrecios as $item)
                          <option value="{{ $item->codListaPrecio }}" data-codestacion="{{ $item->codEstacion }}" >{{ $item->precio }}</option>
                        @endforeach
                      </select>
                    </div>
  
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="row mt-4">
                    <div class="col-md-12">
                      <label class="form-label">Tipo Operacion</label>
                      <select class="form-select" name="tipo_operacion_id" id="tipo_operacion_id">
                        @foreach ($listTipoOperacion as $item)
                          @if ($item->ingresoSalida == 'Salida')
                            <option value="{{ $item->tipoOperacion }}" data-codigo_motivo_traslado="{{ $item->motivotraslado }}" data-nombre_motivo_traslado="{{ $item->descriMotivotraslado }}" data-nombre="{{ $item->descripcion }}">{{ $item->descripcion }}</option>
                          @endif
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Almacen</label>
                      <select class="form-select" name="codalmacen" id="codalmacen">
                        @foreach ($listAlmacenes as $item)
                          <option value="{{ $item->codAlmacen }}" data-nombre="{{ $item->descripcion }}">{{ $item->descripcion }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <h5>Datos transporte</h5>
            <div class="col-md-6">
              <div class="row">
                <div class="col-md-12">
                  <label class="form-label">Transportista</label>
                  <select class="form-select" name="transportista_id" id="transportista_id" style="width: 100%" data-placeholder="Seleccionar un Transportista"></select>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Direccion</label>
                  <input type="text" class="form-control" id="transportista_direccion" name="transportista_direccion" placeholder="Direccion del Transportista">
                </div>
              </div>

            </div>
            <div class="col-md-6">
              <div class="row">
                <div class="col-md-6">
                  <label class="form-label">Motivo Traslado</label>
                  <select class="form-select" name="motivo_traslado_id" id="motivo_traslado_id">
                    <option value="1">Envio Equipaje</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Vehiculo</label>
                  <select name="vehiculo_id" id="vehiculo_id" class="form-select">
                    @foreach ($listVehiculos as $item)
                      <option data-placa="{{ $item->placaVehiculo }}" data-marca="{{ $item->marcaVehiculo }}" >Placa: {{ $item->placaVehiculo }} - Marca: {{ $item->marcaVehiculo }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="row mt-2">
                <div class="col-md-6">
                  <label class="form-label">Chofer</label>
                  <select name="chofer_id" id="chofer_id" class="form-select">
                    @foreach ($listChoferes as $item)
                      <option data-dni_chofer="{{ $item->dniChofer }}" data-brevete_chofer="{{ $item->breveteChofer }}" data-nombre="{{ $item->nombreChofer }}">{{ $item->nombreChofer }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Brevete</label>
                  <input type="text" class="form-control" name="brevete" id="brevete">
                </div>
              </div>

            </div>
          </div>


          <div class="row mt-4">
            <div class="col-md-6">
              <h5>Datos Partida</h5>
              <div class="row mt-3 mb-2">
                <div class="col-md-6">
                  <label class="form-label">Departamento</label>
                  <select class="form-select ubigeo mt-1" data-tipo_busqueda="2" data-tipo_ubigeo="partida" name="partida_departamento" id="partida_departamento">
                    @foreach ($listUbigeos as $item)
                      <option value="{{ $item->codUbigeo }}">{{ $item->descripcion }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Provincia</label>
                  <select class="form-select mt-1 ubigeo" data-tipo_busqueda="3" data-tipo_ubigeo="partida" name="partida_provincia" id="partida_provincia"></select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Distrito</label>
                  <select class="form-select mt-1 ubigeo" name="ubigeo_partida" data-tipo_ubigeo="partida" id="partida_distrito"></select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <label class="form-label">Direccion Partida</label>
                  <input type="text" class="form-control" name="direccion_partida" placeholder="Direccion de Partida">
                </div>
              </div>
            </div>
            <div class="col-md-6">

              <h5>Datos Llegada</h5>
              <div class="row mt-3 mb-2">
                <div class="col-md-6">
                  <label class="form-label">Departamento</label>
                  <select class="form-select ubigeo mt-1" data-tipo_busqueda="2" data-tipo_ubigeo="llegada" name="llegada_departamento" id="llegada_departamento">
                    @foreach ($listUbigeos as $item)
                      <option value="{{ $item->codUbigeo }}">{{ $item->descripcion }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Provincia</label>
                  <select class="form-select mt-1 ubigeo" data-tipo_busqueda="3" data-tipo_ubigeo="llegada" name="llegada_provincia" id="llegada_provincia"></select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Distrito</label>
                  <select class="form-select mt-1 ubigeo" name="ubigeo_llegada" data-tipo_ubigeo="llegada" id="llegada_distrito"></select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <label class="form-label">Llegada</label>
                  <input type="text" class="form-control" name="direccion_llegada" placeholder="Direccion de llegada">
                </div>
              </div>
            </div>
          </div>

        </form>

        <div class="row mt-4">
          <h5><i class="fa fa-list"></i> Detalle</h5>
          <div class="col-md-12">
            <div class="row">
              <div class="col-md-8 mb-2">
                <label class="form-label">Articulo</label>
                <select class="form-select select_2" name="producto_id" id="producto_id" style="width: 100%" data-placeholder="Indicar un Articulo">
                  @foreach ($listArticulos as $item)
                    <option data-codigo_barra="{{ $item->CodBarra }}" data-cod_plu="{{ $item->CodPlu }}"
                      data-descripcion="{{ $item->NombreArticulo }}" data-precio_publico="{{ $item->PrecioPublico }}" data-precio_sin_igv="{{ $item->PrecioSinIGV }}" value="{{ $item->CodArticulo }}">
                      [{{ $item->CodPlu }}] {{ $item->NombreArticulo }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4 mt-3">
                <button class="btn btn-success btn-primary mt-1" id="btnAdd"><i class="fa fa-plus"></i> Agregar</button>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-md-12 table-responsive">
                <table class="table table-hover table-striped table-sm table-bordered">
                  <thead>
                    <th class="text-center">Cod. Barras</th>
                    <th class="text-center">Codigo</th>
                    <th class="text-center">Cod. Int</th>
                    <th class="text-center">Descripcion</th>
                    <th class="text-center">Precio</th>
                    <th class="text-center" style="width: 7rem">Cantidad</th>
                    <th class="text-center">Uni</th>
                    <th class="text-center">Importe</th>
                    <th class="text-center" style="width: 4rem">Descto</th>
                    <th class="text-center">Accion</th>
                  </thead>
                  <tbody id="tbody">
                    {{-- <tr>
                      <td>1234567</td>
                      <td>333333</td>
                      <td>766767</td>
                      <td>Producto de prueba</td>
                      <td>23</td>
                      <td>23 mtrs</td>
                      <td>
                        <button class="btn btn-danger btn-sm"><i class="fa fa-times-circle"></i></button>
                      </td>
                    </tr> --}}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-2">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-3">
                <label class="form-label">Item(s)</label>
                <input class="form-control" type="text" id="total_items" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Cantidad</label>
                <input class="form-control" type="text" id="total_cantidad" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Base Calculo</label>
                <select class="form-select" name="base_caluclo" id="base_calculo">
                  <option value="2">Con IGV</option>
                  {{-- <option value="1">Sin IGV</option> --}}
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-10">
                <label class="form-label">Comentario</label>
                <textarea class="form-control" name="comentario" id="comentario" rows="2"></textarea>
              </div>
            </div>

          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-3">
                <label class="form-label">Descuento</label>
                <input class="form-control" type="text" id="monto_descuento" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Valor Venta</label>
                <input class="form-control" type="text" id="importe_sin_igv" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">IGV</label>
                <input class="form-control" type="text" id="monto_igv" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Total Venta</label>
                <input class="form-control" type="text" id="total_venta" readonly>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="row mt-4" hidden>
                  <div class="col-md-5" >
                    <div class="form-check mt-2">
                      <input class="form-check-input" type="checkbox" value="" id="descuento_porcentual">
                      <label class="form-check-label" for="descuento_porcentual">
                        Aplicar Descto Porcentual
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <input class="form-control" type="text" placeholder="porcentaje descuento">
                  </div>
                </div>
              </div>
            </div>

          </div>


        </div>

        <div class="row">
          <div class="col-md-12">
            <br>
            <a type="button" href="{{ route('guiasalida.index') }}" class="btn btn-danger float-start"><i class="fa fa-arrow-left"
                aria-hidden="true"></i>
              Cancelar</a>
            <button class="btn btn-primary float-end" form="form_store"><i class="fa fa-save" aria-hidden="true"></i> Guardar</button>
          </div>
        </div>


      </div>

    </div>
  </div>
  @push('js-scripts')
    <script src="{{ asset('js/guias/salida/create.js?v=') }}{{ rand() }}"></script>
    <script src="{{ asset('js/guias/salida/ubigeo.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
