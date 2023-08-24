@extends('layouts.app')

<style>
  body {
    background-color: #f5f7fa !important;
    padding-right: 0 !important
  }
</style>

@section('content')
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <h5><i class="fa fa-ticket"></i> Guia de Salida</h5>
        <form name="form_store" id="form_store" onkeydown="return event.key != 'Enter';">
          <input type="hidden" name="id_continua" id="id_continua" value="{{ $guia->id ?? '' }}">
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
                          <label class="form-label">Numero</label>
                          <input type="text" class="form-control" id="span_numero" readonly>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">Fecha Emision</label>
                          <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-md-6 mb-2">

                          <label class="form-label">Enviar a sunat</label>
                          <select class="form-select" name="envio_sunat" id="envio-sunat">
                            <option value="0" {{ ($guia->envio_sunat ?? '') == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ ($guia->envio_sunat ?? '') == 1 ? 'selected' : '' }}>Si</option>
                          </select>
                        </div>
                        <div class="col-md-6" hidden>
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
                            <input class="form-check-input" type="checkbox" value="1" id="pedido_interno"
                              {{ ($guia->pedido_interno ?? '') == 1 ? 'checked' : '' }} name="pedido_interno">
                            <label class="form-check-label" for="pedido_interno">
                              Interno
                            </label>
                          </div>
                        </div>
                        <div class="col-md-9">
                          <div class="row no-gutters">
                            <div class="col-md-5">
                              <input type="text" class="form-control" placeholder="Serie" name="pedido_serie"
                                value="{{ $guia->pedido_serie ?? '' }}">
                            </div>
                            <div class="col-md-7">
                              <input type="text" class="form-control" placeholder="Numero" name="pedido_numero"
                                value="{{ $guia->pedido_numero ?? '' }}">
                            </div>

                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
                <div class="col-md-6">
                  <div class="row">
                    <div class="col-md-2">
                      <label class="form-label mt-2">Codigo</label>
                    </div>
                    <div class="col-md-6">
                      {{-- <input class="form-control" type="text" name="b_codigo_empleado" id="b_codigo_empleado" placeholder="Codigo Empleado"> --}}
                      <div class="input-group">
                        <input type="text" class="form-control" id="vendedor_codigo" placeholder="Ingresar codigo" aria-describedby="button-addon2" value="{{ $guia->vendedor_id ?? '' }}">
                        <button class="btn btn-primary" type="button" id="btnBuscarVendedor"><i class="fa fa-search"></i></button>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-10 mb-2">
                      <label class="form-label">Vendedor</label>

                      <select class="form-select" name="vendedor_id" id="vendedor_id" style="width: 100%">
                        @foreach ($listVendedores as $item)
                          <option value="{{ $item->codTrabajador }}"
                            data-vendedor_nombre="{{ "{$item->apellidos} {$item->nombres}" }}"
                            {{ ($item->selected ?? '') == 'selected' ? 'selected' : '' }}>
                            {{ "[{$item->codTrabajador}] {$item->apellidos} {$item->nombres}" }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-10 mb-2">
                      <div class="row">
                        <div class="col-md-12">
                          <h5>Otros Datos</h5>
                          <div class="form-check">
                            <input class="form-check-input" id="indicar_proveedor" name="indicar_proveedor"
                              type="checkbox" {{ ($guia->indicar_proveedor ?? 0) == 1 ? 'checked' : '' }} />
                            <label class="form-check-label" for="indicar_proveedor">Proveedor</label>
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
              <div class="row" id="div_cliente"
                style="display: {{ ($guia->indicar_proveedor ?? 0) == 0 ? '' : 'none' }}">
                <h5>Datos el Cliente</h5>
                <div class="col-md-12">
                  <label class="form-label">Cliente</label>
                  <div class="row g-2">
                    <div class="col-md-3">
                      <select id="tipo_busqueda_cliente" class="form-select">
                        <option value="4">Nombre</option>
                        <option value="2">RUC</option>
                        <option value="3">DNI</option>
                      </select>
                    </div>
                    <div class="col-md-9">
                      <select class="form-select" name="cliente_id" id="cliente_id" style="width: 100%"
                        data-placeholder="Buscar Cliente">
                        @if (count($listClientes) > 0)
                          @foreach ($listClientes as $item)
                            <option value="{{ $item->codCliente }}">{{ $item->texto_cliente }}</option>
                          @endforeach
                        @endif
                      </select>
                    </div>
                    <input type="hidden" name="cliente_razon_social" id="cliente_razon_social"
                      value="{{ $guia->cliente_razon_social ?? '' }}">
                    <input type="hidden" name="cliente_nro_documento" id="cliente_nro_documento"
                      value="{{ $guia->cliente_nro_documento ?? '' }}">
                    <input type="hidden" name="cliente_documento_tipo_nombre" id="cliente_documento_tipo_nombre"
                      value="{{ $guia->cliente_documento_tipo_nombre ?? '' }}">

                    <input type="hidden" name="cliente_direccion" id="cliente_direccion"
                      value="{{ $guia->cliente_direccion ?? '' }}">
                  </div>
                </div>
                <div class="col-md-12">
                  <label class="form-label mt-1">Direccion</label>
                  <input type="text" class="form-control" name="direccion" id="direccion"
                    placeholder="Direccion del cliente" value="{{ $guia->cliente_direccion ?? '' }}">
                </div>
              </div>

              <div class="row" id="div_proveedor" style="display:  style="display:
                {{ ($guia->indicar_proveedor ?? 0) == 1 ? '' : 'none' }}"">
                <h5>Proveedor</h5>
                <div class="col-md-12">
                  <div class="row g-2">
                    <div class="col-md-3">
                      <select id="tipo_busqueda_proveedor" class="form-select" style="width: 100%">
                        <option value="3">Razon Social</option>
                        <option value="2">RUC</option>
                        <option value="1">Codigo</option>
                      </select>
                    </div>
                    <div class="col-md-9">
                      <select class="form-select" id="proveedor_id" name="proveedor_id"
                        data-placeholder="Buscar un proveedor" style="width: 100%">
                        @if (count($listProveedores) > 0)
                          @foreach ($listProveedores as $item)
                            <option value="{{ $item->codProveedor }}">
                              {{ "[{$item->ruc}] {$item->nombreproveedor}" }}</option>
                          @endforeach
                        @endif
                      </select>
                    </div>
                    <input type="hidden" name="proveedor_nombre" id="proveedor_nombre"
                      value="{{ $guia->proveedor_nombre ?? '' }}">
                    <input type="hidden" name="proveedor_ruc" id="proveedor_ruc"
                      value="{{ $guia->proveedor_ruc ?? '' }}">
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
                          <option value="{{ $item->codFormaPago }}" data-nombre="{{ $item->descripcion }}">
                            {{ $item->descripcion }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-12 mt-2">
                      <label class="form-label">Lista Precio</label>
                      <select class="form-select" name="codlistaprecio" id="codlistaprecio">
                        @foreach ($listPrecios as $item)
                          <option value="{{ $item->codListaPrecio }}" data-codestacion="{{ $item->codEstacion }}">
                            {{ $item->precio }}</option>
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
                            <option value="{{ $item->tipoOperacion }}"
                              data-codigo_motivo_traslado="{{ $item->motivotraslado }}"
                              data-nombre_motivo_traslado="{{ $item->descriMotivotraslado }}"
                              data-nombre="{{ $item->descripcion }}" {{ $item->selected ?? '' }}>
                              {{ $item->descripcion }}
                            </option>
                          @endif
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-12 mt-2" id="div_almacen_unico">
                      <label class="form-label">Almacen</label>
                      <select class="form-select almacen_select" data-almacen_tipo='1' name="codalmacen" id="codalmacen">
                        @foreach ($listAlmacenes as $item)
                          <option value="{{ $item->codAlmacen }}" data-nombre="{{ $item->descripcion }}"
                            data-ubigeo="{{ $item->ubigeo ?? '' }}"
                            data-direccion="{{ $item->direccion ?? '' }}"
                            {{ $item->selected ?? '' }}>
                            {{ $item->descripcion }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="mt-2" id="div_almacene_transferencia" style="display: none">
                      <div class="col-md-12">
                        <label class="form-label">Almacen Origen</label>
                        <select class="form-select almacen_select" data-almacen_tipo="1" name="cod_almacen_origen" id="cod_almacen_origen">
                          @foreach ($listAlmacenOrigen as $item)
                            <option value="{{ $item->codAlmacen }}" data-nombre="{{ $item->descripcion }}"
                              data-ubigeo="{{ $item->ubigeo ?? '' }}"
                              data-direccion="{{ $item->direccion ?? '' }}"
                              {{ $item->selected ?? '' }}>
                              {{ $item->descripcion }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Almacen Destino</label>
                        <select class="form-select almacen_select" data-almacen_tipo="2" name="cod_almacen_destino" id="cod_almacen_destino">
                          @foreach ($listAlmacenDestino as $item)
                            <option value="{{ $item->codAlmacen }}" data-nombre="{{ $item->descripcion }}"
                              data-ubigeo="{{ $item->ubigeo ?? '' }}"
                              data-direccion="{{ $item->direccion ?? '' }}"
                              {{ $item->selected ?? '' }}>
                              {{ $item->descripcion }}</option>
                          @endforeach
                        </select>
                      </div>
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
                  <select class="form-select" name="transportista_id" id="transportista_id" style="width: 100%"
                    data-placeholder="Seleccionar un Transportista">
                    @if (count($listTransportistas ?? []) > 0)
                      @foreach ($listTransportistas as $item)
                        <option value="{{ $item->codTransportista }}">{{ $item->texto_transportista }}</option>
                      @endforeach
                    @endif
                  </select>
                  <input type="hidden" name="transportista_ruc" id="transportista_ruc"
                    value="{{ $guia->transportista_ruc ?? '' }}">
                  <input type="hidden" name="transportista_nombre" id="transportista_nombre"
                    value="{{ $guia->transportista_nombre ?? '' }}">
                </div>
                <div class="col-md-12">
                  <label class="form-label">Direccion</label>
                  <input type="text" class="form-control" id="transportista_direccion"
                    name="transportista_direccion" placeholder="Direccion del Transportista"
                    value="{{ $guia->transportista_direccion ?? '' }}">
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
                      <option data-placa="{{ $item->placaVehiculo }}" data-marca="{{ $item->marcaVehiculo }}">Placa:
                        {{ $item->placaVehiculo }} - Marca: {{ $item->marcaVehiculo }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="row mt-2">
                <div class="col-md-6">
                  <label class="form-label">Chofer</label>
                  <select name="chofer_id" id="chofer_id" class="form-select">
                    @foreach ($listChoferes as $item)
                      <option data-dni_chofer="{{ $item->dniChofer }}"
                        data-brevete_chofer="{{ $item->breveteChofer }}" data-nombre="{{ $item->nombreChofer }}">
                        {{ $item->nombreChofer }}</option>
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
                  <select class="form-select ubigeo mt-1" data-tipo_busqueda="2" data-tipo_ubigeo="partida"
                    name="partida_departamento" id="partida_departamento">
                    @foreach ($listUbigeosDepartamentoPartida as $item)
                      <option value="{{ $item->codUbigeo }}" {{ $item->selected ?? '' }}>{{ $item->descripcion }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Provincia</label>
                  <select class="form-select mt-1 ubigeo" data-tipo_busqueda="3" data-tipo_ubigeo="partida"
                    name="partida_provincia" id="partida_provincia">
                    @if (count($listUbigeosProvinciaPartida) > 0)
                      @foreach ($listUbigeosProvinciaPartida as $item)
                        <option value="{{ $item->codUbigeo }}" {{ $item->selected }}>
                          {{ $item->descripcion }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Distrito</label>
                  <select class="form-select mt-1 ubigeo" name="ubigeo_partida" data-tipo_ubigeo="partida"
                    id="partida_distrito">
                    @if (count($listUbigeosDistritoPartida) > 0)
                      @foreach ($listUbigeosDistritoPartida as $item)
                        <option value="{{ $item->codUbigeo }}" {{ $item->selected }}>
                          {{ $item->descripcion }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <label class="form-label">Direccion Partida</label>
                  <input type="text" class="form-control" name="direccion_partida" id="direccion_partida"
                    placeholder="Direccion de Partida" value="{{ $guia->direccion_partida ?? '' }}">
                </div>
              </div>
            </div>
            <div class="col-md-6">

              <h5>Datos Llegada</h5>
              <div class="row mt-3 mb-2">
                <div class="col-md-6">
                  <label class="form-label">Departamento</label>
                  <select class="form-select ubigeo mt-1" data-tipo_busqueda="2" data-tipo_ubigeo="llegada"
                    name="llegada_departamento" id="llegada_departamento">
                    @foreach ($listUbigeosDepartamentoLlegada as $item)
                      <option value="{{ $item->codUbigeo }}" {{ $item->selected ?? '' }}>{{ $item->descripcion }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Provincia</label>
                  <select class="form-select mt-1 ubigeo" data-tipo_busqueda="3" data-tipo_ubigeo="llegada"
                    name="llegada_provincia" id="llegada_provincia">
                    @if (count($listUbigeosProvinciaLlegada) > 0)
                      @foreach ($listUbigeosProvinciaLlegada as $item)
                        <option value="{{ $item->codUbigeo }}" {{ $item->selected }}>
                          {{ $item->descripcion }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Distrito</label>
                  <select class="form-select mt-1 ubigeo" name="ubigeo_llegada" data-tipo_ubigeo="llegada"
                    id="llegada_distrito">
                    @if (count($listUbigeosDistritoLlegada) > 0)
                      @foreach ($listUbigeosDistritoLlegada as $item)
                        <option value="{{ $item->codUbigeo }}" {{ $item->selected }}>
                          {{ $item->descripcion }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <label class="form-label">Llegada</label>
                  <input type="text" class="form-control" name="direccion_llegada" id="direccion_llegada"
                    placeholder="Direccion de llegada" value="{{ $guia->direccion_llegada ?? '' }}">
                </div>
              </div>
            </div>
          </div>

        </form>

        <div class="row mt-4">
          <h5><i class="fa fa-list"></i> Detalle</h5>
          <div class="col-md-12">
            <label class="form-label">Articulo</label>
            <form name="form_buscar_articulo" id="form_buscar_articulo">
              @csrf
              <div class="row">
                <div class="col-md-2">
                  <select class="form-select" id="tipo_busqueda_articulo">
                    <option value="1">Codigo Barras</option>
                    <option value="2">Codigo Articulo</option>
                    <option value="3">Codigo Interno</option>
                    <option value="4">Descripcion</option>
                  </select>
                </div>
                <div class="col-md-8 mb-2" id="div_form_buscar_articulo">
                  {{-- <select class="form-select select_2" name="producto_select" id="producto_select" style="width: 100%"
                    data-placeholder="Indicar un Articulo"> --}}
                    {{-- @foreach ($listArticulos as $item)
                      <option data-codigo_barra="{{ $item->CodBarra }}" data-cod_plu="{{ $item->CodPlu }}"
                        data-descripcion="{{ $item->NombreArticulo }}" data-precio_publico="{{ $item->PrecioPublico }}"
                        data-precio_sin_igv="{{ $item->PrecioSinIGV }}" value="{{ $item->CodArticulo }}">
                        [{{ $item->CodPlu }}] {{ $item->NombreArticulo }}
                      </option>
                    @endforeach --}}
                  {{-- </select> --}}
                </div>


                <div class="col-md-2">
                  {{-- <button type="button" class="btn btn-success btn-primary" id="btnAdd"><i class="fa fa-plus"></i>
                    Agregar</button> --}}
                </div>

              </div>
            </form>


            <div>
              <input type="hidden" id="producto_id" name="producto_id">
              <input type="hidden" id="producto_codigo_barra" name="producto_codigo_barra">
              <input type="hidden" id="producto_descripcion" name="producto_descripcion">
              <input type="hidden" id="producto_precio_publico" name="producto_precio_publico">
              <input type="hidden" id="producto_precio_sin_igv" name="producto_precio_sin_igv">

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
                    @foreach ($detalle ?? [] as $item)
                      <tr data-producto_id='{{ $item->codarticulo }}' data-precio_unitario={{ $item->precio_publico }}
                        data-precio_publico={{ $item->precio_publico }}
                        data-precio_sin_igv='{{ $item->precio_sin_igv }}' data-descripcion='{{ $item->descripcion }}'
                        data-codigo='{{ $item->cod_plu }}' data-codigo_barra='{{ $item->codigo_barra }}'>
                        <td class='align-middle'>{{ $item->codigo_barra }}</td>
                        <td class='align-middle'>{{ $item->codarticulo }}</td>
                        <td class='align-middle'>{{ $item->cod_plu }}</td>
                        <td class='align-middle'>{{ $item->descripcion }}</td>
                        <td class='align-middle'><span name='span_precio'>{{ $item->precio }}</span></td>
                        <td class='align-middle'>
                          <input class='form-control form-control-sm input_cantidad_tr' name='cantidad'
                            value='{{ $item->cantidad }}'></input>
                        </td>
                        <td class='align-middle'>UNI</td>
                        <td class='align-middle'><span name='span_importe'>{{ $item->importe }}</span></td>
                        <td class='align-middle'>
                          {{-- {$inputPorcentajeDescuento} --}}
                          <input class='form-control form-control-sm input_porcentaje_descuento_tr'
                            name='porcentaje_descuento' value='{{ $item->porcentaje_descuento }}'></input>
                          {{-- {$inputDescuento} --}}
                          <input type='hidden' name='monto_descuento' value='{{ $item->monto_descuento }}'></input>
                        </td>
                        <td class='align-middle text-center'>
                          <button class='btn btn-danger btn-sm delete_item'><i class='fa fa-times-circle'></i></button>
                        </td>
                      </tr>
                    @endforeach
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
                <select class="form-select" name="base_calculo" id="base_calculo">
                  <option value="2" {{ ($guia->base_calculo ?? '') == 2 ? 'selected' : '' }}>Con IGV</option>
                  <option value="1" {{ ($guia->base_calculo ?? '') == 1 ? 'selected' : '' }}>Sin IGV</option>
                </select>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-md-8">
                <label class="form-label">Comentario</label>
                <textarea class="form-control" name="comentario" id="comentario" rows="2">{{ $guia->comentario ?? '' }}</textarea>
              </div>
              <div class="col-md-4 ">
                <label class="form-label">Peso total (Kg)</label>
                <input type="number" class="form-control" name="peso_bruto_total" id="peso_bruto_total"
                  value="{{ $guia->peso_bruto_total ?? '' }}">
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
                  <div class="col-md-5">
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
            <a type="button" href="{{ route('guiasalida.index') }}" class="btn btn-danger float-start"><i
                class="fa fa-arrow-left" aria-hidden="true"></i>
              Cancelar</a>
            {{-- <button class="btn btn-primary float-end" form="form_store"><i class="fa fa-save" aria-hidden="true"></i> Guardar</button> --}}
            <div class="btn-group float-end">
              <button type="submit" form="form_store" class="btn btn-primary"><i class="fa fa-save"
                  aria-hidden="true"></i> Guardar</button>
              <button type="button" class="btn btn-dark dropdown-toggle dropdown-toggle-split"
                data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" style="cursor: pointer" id="btnGuardarAvance"><i
                      class="fa fa-download"></i> <b>Guardar Avance</b></a></li>
              </ul>
            </div>
          </div>
        </div>


      </div>

    </div>
    <div id="modales"></div>
  </div>
  @push('js-scripts')
    <script src="{{ asset('js/guias/salida/create.js?v=') }}{{ rand() }}"></script>
    <script src="{{ asset('js/guias/salida/articulo.js?v=') }}{{ rand() }}"></script>
    <script src="{{ asset('js/guias/salida/ubigeo.js?v=') }}{{ rand() }}"></script>
    <script src="{{ asset('js/guias/salida/almacen.js?v=') }}{{ rand() }}"></script>
  @endpush
@endsection
