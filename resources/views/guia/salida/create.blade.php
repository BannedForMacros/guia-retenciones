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
                        <select class="form-select" name="serie_id" id="serie_id">
                          <option value="1">0001</option>
                        </select>
                      </div>
                      <div class="col-md-6 mb-2">
                        <label class="form-label">Fecha Emision</label>
                        <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                      </div>
                      <div class="col-md-6 mb-2">
                        <label class="form-label">Estado</label>
                        <input type="text" class="form-control" readonly value="GENERADA">
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
                            <input type="text" class="form-control" placeholder="Serie">
                          </div>
                          <div class="col-md-7">
                            <input type="text" class="form-control" placeholder="Numero">
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
              <div class="col-md-6">
                <div class="row">
                  <div class="col-md-6 mb-2">
                    <label class="form-label">Vendedor</label>
                    <select class="form-select" name="vendedor_id" id="vendedor_id">
                      <option value="1">Vendedor 1</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-2">
                    <div class="row">
                      <div class="col-md-12">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" name="proveedor_id" id="proveedor_id">
                          <option value="-1">No indicar</option>
                          <option value="1">Vendedor 1</option>
                        </select>
                      </div>
                      <div class="col-md-12">
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
                <select class="form-select" name="cliente_id" id="cliente_id">
                  <option value="1">cliente 1</option>
                </select>
              </div>
              <div class="col-md-12">
                <label class="form-label">Direccion</label>
                <input type="text" class="form-control" placeholder="Direccion del cliente">
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
                      <option value="1">divisa 1</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">F Pago</label>
                    <select class="form-select" name="forma_pago_id" id="forma_pago_id">
                      <option value="1">forma 1</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Lista Precio</label>
                    <select class="form-select" name="lista_precio_id" id="lista_precio_id">
                      <option value="1">lista 1</option>
                    </select>
                  </div>

                </div>
              </div>
              <div class="col-md-6">
                <div class="row mt-4">
                  <div class="col-md-12">
                    <label class="form-label">Tipo Operacion</label>
                    <select class="form-select" name="tipo_operacion_id" id="tipo_operacion_id">
                      <option value="1">Tipo Operacion 1</option>
                    </select>
                  </div>
                  <div class="col-md-12">
                    <label class="form-label">Almacen</label>
                    <select class="form-select" name="almacen_id" id="almacen_id">
                      <option value="1">Almacen 1</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-4">
          <h5><i class="fa fa-list"></i> Detalle</h5>
          <div class="col-md-12">
            <div class="row">
              <div class="col-md-8 mb-2">
                <label class="form-label">Productos</label>
                <select class="form-select" name="producto_id" id="producto_id">
                  <option value="1">Producto 1</option>
                </select>
              </div>
              <div class="col-md-4 mt-4">
                <button class="btn btn-success btn-primary mt-1"><i class="fa fa-plus"></i> Agregar</button>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 table-responsive">
                <table class="table table-hover table-striped table-sm table-bordered">
                  <thead>
                    <th>Cod. Barras</th>
                    <th>Codigo</th>
                    <th>Cod. Int</th>
                    <th>Descripcion</th>
                    <th>Cantidad</th>
                    <th>Med</th>
                    <th>Accion</th>
                  </thead>
                  <tbody id="tbody">
                    <tr>
                      <td>1234567</td>
                      <td>333333</td>
                      <td>766767</td>
                      <td>Producto de prueba</td>
                      <td>23</td>
                      <td>23 mtrs</td>
                      <td>
                        <button class="btn btn-danger btn-sm"><i class="fa fa-times-circle"></i></button>
                      </td>
                    </tr>
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
                <input class="form-control" type="text" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Cantidad</label>
                <input class="form-control" type="text" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Base Calculo</label>
                <select class="form-select" name="base_caluclo" id="base_calculo">
                  <option value="1">Sin IGV</option>
                  <option value="2">Con IGV</option>
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
                <input class="form-control" type="text" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Valor Venta</label>
                <input class="form-control" type="text" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">IGV</label>
                <input class="form-control" type="text" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Total Venta</label>
                <input class="form-control" type="text" readonly>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="row mt-4">
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
            <a type="button" href="#" class="btn btn-danger float-start"><i class="fa fa-arrow-left" aria-hidden="true"></i>
              Cancelar</a>
            <button class="btn btn-primary float-end" id="btn_guardar"><i class="fa fa-save" aria-hidden="true"></i>
              Guardar</button>
          </div>
        </div>


      </div>

    </div>
  </div>
@endsection
