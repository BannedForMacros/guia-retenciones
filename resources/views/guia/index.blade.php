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
        <h5>Guia de Remision Electronica</h5>

        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-3 mb-2">
                <label class="form-label">Fecha Emision</label>
                <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label">Fecha Traslado</label>
                <input type="date" class="form-control" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 mb-2">
                <label class="form-label">Motivo Traslado</label>
                <select class="form-control form-select" name="motivo_traslado_id" id="motivo_traslado_id">
                  <option value="1">motivo 1</option>
                </select>
              </div>
              <div class="col-md-12 mb-2">
                <label class="form-label">Otro Motivo</label>
                <textarea class="form-control" name="motivo_traslado_otro" id="motivo_traslado_otro" rows="2"></textarea>
              </div>
            </div>

          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-12 mb-2">
                <label class="form-label">Destinatario</label>
                <select class="form-control form-select" name="motivo_traslado_id" id="motivo_traslado_id">
                  <option value="1">motivo 1</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3 mb-2">
                <label class="form-label">Peso Total (KG)</label>
                <input type="text" class="form-control" name="peso_total" id="peso_total">
              </div>
              <div class="col-md-5 mb-2">
                <label class="form-label">Nro de Comprobante</label>
                <input type="text" class="form-control" name="nro_comprobante" id="nro_comprobante">
              </div>
              <div class="col-md-4 mb-2">
                <label class="form-label">Nº Orden de Compra</label>
                <input type="text" class="form-control" name="nro_comprobante" id="nro_comprobante">
              </div>
            </div>

          </div>

        </div>

      </div>
    </div>
  </div>
@endsection
