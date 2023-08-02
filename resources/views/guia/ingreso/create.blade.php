@extends('layouts.app')


@section('content')
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <h5><i class="fa fa-ticket"></i> Guia de Ingreso</h5>
        <form name="form_store" id="form_store">
          @csrf
          <div class="row">
            <div class="col-md-6">
              <div class="row">
                <div class="col-md-3 mb-2">
                  <label class="form-label">Fecha Emision</label>
                  <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                </div>
                <div class="col-md-3 col-sm-4 mb-2">
                  <label class="form-label">Fecha Vencimiento</label>
                  <input type="date" class="form-control">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label">Contacto</label>
                  <select class="form-select" name="vendedor_id" id="vendedor_id" style="width: 100%">
                    @foreach ($listVendedores as $item)
                    <option value="{{ $item->codTrabajador }}" data-vendedor_nombre="{{ "{$item->apellidos} {$item->nombres}" }}">{{ "{$item->apellidos} {$item->nombres}" }}</option>
                        
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3 mb-2">
                  <label class="form-label">Estado</label>
                  <input type="text" class="form-control" readonly value="GENERADA">
                </div>
              </div>
  
              <div class="row">
                <div class="col-md-7 mb-2">
                  <label class="form-label">Relacionar Documento</label>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="relacion_pedido" id="pedido" value="1">
                        <label class="form-check-label" for="pedido">
                          Pedido
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="relacion_pedido" id="recepcion" value="2" checked>
                        <label class="form-check-label" for="recepcion">
                          Recepcion
                        </label>
                      </div>
                    </div>
                    <div class="col-md-8">
                      {{-- <label class="form-label">Serie-Nro</label> --}}
                      <div class="row">
                        <div class="col-md-4">
                          <input type="text" class="form-control" name="pedido_serie" placeholder="Serie">
                        </div>
                        <div class="col-md-8">
                          <input type="text" class="form-control" name="pedido_numero" placeholder="Numero">
                        </div>
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
  
            </div>
            <div class="col-md-6">
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
              </div>
  
              <div class="row">
                <div class="col-md-3 mb-2">
                  <label class="form-label">Divisa</label>
                  <select class="form-select" name="divisa_id" id="divisa_id">
                    <option value="1">Soles</option>
                  </select>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Condiciones</label>
                  <input type="text" class="form-control" name="condiciones" placeholder="Condiciones">
                </div>
              </div>
  
              <div class="row mb-2">
                <div class="col-md-3">
                  <label class="form-label">F. Pago</label>
                  <select class="form-select" name="forma_pago_id" id="forma_pago_id">
                    @foreach ($listFormasPago as $item)
                      <option value="{{ $item->codFormaPago }}" data-nombre="{{ $item->descripcion }}">{{ $item->descripcion }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-5">
                  <label class="form-label">Tipo Operacion</label>
                  <select class="form-select" name="tipo_operacion_id" id="tipo_operacion_id">
                    @foreach ($listTipoOperacion as $item)
                      @if ($item->ingresoSalida == 'Ingreso')
                        <option value="{{ $item->tipoOperacion }}" data-nombre="{{ $item->descripcion }}">{{ $item->descripcion }}</option>
                      @endif
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Almacen</label>
                  <select class="form-select" name="codalmacen" id="codalmacen">
                    @foreach ($listAlmacenes as $item)
                      <option value="{{ $item->codAlmacen }}" data-nombre="{{ $item->descripcion }}" data-codestacion="{{ $item->codEstacion }}" >{{ $item->descripcion }}</option>
                    @endforeach
                  </select>
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
                <label class="form-label">Productos</label>
                <select class="form-select select_2" name="producto_id" id="producto_id" style="width: 100%">
                  @foreach ($listArticulos as $item)
                    <option data-codigo_barra="{{ $item->CodBarra }}" data-cod_plu="{{ $item->CodPlu }}"
                      data-descripcion="{{ $item->NombreArticulo }}" data-precio_publico="{{ $item->PrecioPublico }}" data-precio_sin_igv="{{ $item->PrecioSinIGV }}" value="{{ $item->CodArticulo }}">
                      [{{ $item->CodPlu }}] {{ $item->NombreArticulo }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4 mt-4">
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
          <div class="col-md-12">
            <div class="row">
              <div class="col-md-8 mb-2">
                <div class="row">
                  <div class="col-md-10">
                    <label class="form-label">Comentario</label>
                    <textarea class="form-control" name="comentario" id="comentario" rows="2"></textarea>

                  </div>
                </div>
              </div>
              <div class="col-md-4">
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
              </div>
            </div>
            <div class="row">
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
                  {{-- <div class="col-md-3">
                    <label class="form-label">Flete</label>
                    <input class="form-control" type="text" readonly>
                  </div> --}}
                  <div class="col-md-3">
                    <label class="form-label">Base Calculo</label>
                    <select class="form-select" name="base_caluclo" id="base_calculo">
                      {{-- <option value="1">Sin IGV</option> --}}
                      <option value="2">Con IGV</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>


        <div class="row">
          <div class="col-md-12">
            <br>
            <a type="button" href="#" class="btn btn-danger float-start"><i class="fa fa-arrow-left"
                aria-hidden="true"></i>
              Cancelar</a>
            <button type="submit" form="form_store" class="btn btn-primary float-end" ><i class="fa fa-save" aria-hidden="true"></i>
              Guardar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @push('js-scripts')
    <script src="{{ asset('js/guias/ingreso/create.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
