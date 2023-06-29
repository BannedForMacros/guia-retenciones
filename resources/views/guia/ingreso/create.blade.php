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
        <h5><i class="fa fa-ticket"></i> Guia de Ingreso</h5>

        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-3 mb-2">
                <label class="form-label">Fecha Emision</label>
                <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label">Fecha Vencimiento</label>
                <input type="date" class="form-control">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-2">
                <label class="form-label">Contacto</label>
                <input type="text" class="form-control" value="Empleado de contacto" readonly>
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
                  <div class="col-md-4 mt-4">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="pedido" id="pedido">
                      <label class="form-check-label" for="pedido">
                        Pedido
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="recepcion" id="recepcion" checked>
                      <label class="form-check-label" for="recepcion">
                        Recepcion
                      </label>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Serie-Nro</label>
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" class="form-control" name="serie" placeholder="Serie">
                      </div>
                      <div class="col-md-8">
                        <input type="text" class="form-control" name="numero" placeholder="Numero">
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>


          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-12 mb-2">
                <label class="form-label">Proveedores</label>
                <select class="form-select" name="proveedor_id" id="proveedor_id">
                  <option value="1">proveedor 1</option>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3 mb-2">
                <label class="form-label">Divisa</label>
                <select class="form-select" name="divisa_id" id="divisa_id">
                  <option value="1">Divisa 1</option>
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
                  <option value="1">Forma Pago 1</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Tipo Operacion</label>
                <select class="form-select" name="tipo_operacion_id" id="tipo_operacion_id">
                  <option value="1">Ingreso Por Compra</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Almacen</label>
                <select class="form-select" name="almacen_id" id="almacen_id">
                  <option value="1">Ingreso Por Compra</option>
                </select>
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
              <div class="col-md-12">
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
              </div>
            </div>
            <div class="row">
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
                    <label class="form-label">Flete</label>
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
