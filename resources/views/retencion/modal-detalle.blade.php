{{-- Modal: ver detalle de una retencion --}}
<style>
  /* Hereda las CSS variables --db-* del index/create. Si el modal se usara
     standalone, hay un fallback abajo. */
  #modalDetalleRetencion {
    --md-text:   var(--db-text,   #1A3A5C);
    --md-blue:   var(--db-blue,   #0E6CB5);
    --md-teal:   var(--db-teal,   #2ECBA1);
    --md-bg:     var(--db-bg,     #F5F5F5);
    --md-border: var(--db-border, #e5e7eb);
    --md-muted:  var(--db-muted,  #6b7280);
  }

  #modalDetalleRetencion .modal-content { border: 1px solid var(--md-border); border-radius: 6px; }
  #modalDetalleRetencion .modal-header {
    background: #fff; border-bottom: 1px solid var(--md-border);
    padding: .75rem 1rem;
  }
  #modalDetalleRetencion .modal-header h5 {
    margin: 0; font-size: .95rem; font-weight: 700; color: var(--md-text);
  }
  #modalDetalleRetencion .modal-header .serie {
    display: inline-block; background: #E6F3FB; color: var(--md-blue);
    border: 1px solid #BFDDF1;
    font-family: ui-monospace, monospace; font-weight: 700; font-size: .8rem;
    padding: .1rem .45rem; border-radius: 4px; margin-left: 6px;
  }
  #modalDetalleRetencion .modal-body { padding: 1rem; }

  #modalDetalleRetencion .md-section {
    font-size: .65rem; font-weight: 700; color: var(--md-muted);
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px;
  }
  #modalDetalleRetencion .md-card {
    background: #fff; border: 1px solid var(--md-border); border-radius: 6px;
    padding: .65rem .75rem; height: 100%;
  }
  #modalDetalleRetencion .md-row {
    display: flex; justify-content: space-between; gap: 1rem;
    padding: 3px 0; font-size: .82rem; border-bottom: 1px dashed #f3f4f6;
  }
  #modalDetalleRetencion .md-row:last-child { border-bottom: 0; }
  #modalDetalleRetencion .md-row .k { color: var(--md-muted); font-weight: 600; }
  #modalDetalleRetencion .md-row .v { color: var(--md-text); text-align: right; }
  #modalDetalleRetencion .md-row .v.mono { font-family: ui-monospace, monospace; }
  /* highlight financiero — total retenido en teal */
  #modalDetalleRetencion .md-row .v.big-ok { color: var(--md-teal); font-weight: 700; }

  #modalDetalleRetencion .md-table-wrap {
    border: 1px solid var(--md-border); border-radius: 6px; overflow: hidden;
  }
  #modalDetalleRetencion .md-table { font-size: .8rem; margin: 0; }
  #modalDetalleRetencion .md-table thead th {
    background: var(--md-bg); color: var(--md-text); font-weight: 700;
    font-size: .65rem; text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1px solid var(--md-border); padding: .45rem .6rem;
  }
  #modalDetalleRetencion .md-table thead th:first-child { border-top-left-radius: 6px; }
  #modalDetalleRetencion .md-table thead th:last-child  { border-top-right-radius: 6px; }
  #modalDetalleRetencion .md-table tbody td {
    padding: .45rem .6rem; vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
  }
  #modalDetalleRetencion .md-table tbody tr:last-child td { border-bottom: 0; }
  #modalDetalleRetencion .md-table .ret-monto { color: var(--md-teal); font-weight: 700; }

  #modalDetalleRetencion .md-obs {
    background: #fff; border: 1px solid var(--md-border); color: #374151;
    padding: .5rem .65rem; border-radius: 6px; font-size: .8rem;
  }

  #modalDetalleRetencion .modal-footer {
    background: #fff; border-top: 1px solid var(--md-border); padding: .55rem .85rem;
  }

  /* chips simples — aceptado en teal (palette), resto semántico */
  #modalDetalleRetencion .md-chip {
    display: inline-block; padding: .12rem .5rem; border-radius: 4px;
    font-size: .68rem; font-weight: 700; border: 1px solid;
  }
  #modalDetalleRetencion .md-chip.c-acep { background: #fff; color: var(--md-teal); border-color: #A8E8D5; }
  #modalDetalleRetencion .md-chip.c-rech { background: #fff; color: #b91c1c; border-color: #b91c1c; }
  #modalDetalleRetencion .md-chip.c-pend { background: #fff; color: #b45309; border-color: #b45309; }
  #modalDetalleRetencion .md-chip.c-anul { background: #fff; color: #b45309; border-color: #b45309; }
  #modalDetalleRetencion .md-chip.c-act  { background: #fff; color: #4b5563; border-color: var(--md-border); }

  /* Bloque SUNAT */
  #modalDetalleRetencion .md-sunat {
    background: var(--md-bg); border: 1px solid var(--md-border); border-radius: 6px;
    padding: .65rem .75rem;
  }
  #modalDetalleRetencion .md-sunat .lbl-mini {
    font-size: .6rem; color: var(--md-muted); text-transform: uppercase;
    letter-spacing: .04em; font-weight: 700; margin-bottom: 2px;
  }
  #modalDetalleRetencion .md-sunat .val-mono {
    font-family: ui-monospace, monospace; font-size: .78rem;
    word-break: break-all; color: var(--md-text); line-height: 1.35;
  }
  #modalDetalleRetencion .md-sunat .val-err {
    color: #b91c1c; font-size: .78rem;
  }
</style>

<div class="modal fade" id="modalDetalleRetencion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5>Detalle de Retencion <span class="serie" id="md_serienumero"></span></h5>
          <div class="d-flex flex-wrap gap-1 mt-1">
            <span id="md_chip_estado_sunat"></span>
            <span id="md_chip_estado_doc"></span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <div class="md-section">Proveedor</div>
            <div class="md-card">
              <div class="md-row"><span class="k">RUC</span><span class="v mono" id="md_numdocproveedor"></span></div>
              <div class="md-row"><span class="k">Razon Social</span><span class="v" id="md_razonsocialproveedor"></span></div>
              <div class="md-row"><span class="k">Direccion</span><span class="v small" id="md_direccionproveedor"></span></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="md-section">Comprobante</div>
            <div class="md-card">
              <div class="md-row"><span class="k">F. Emision</span><span class="v" id="md_fechaemision"></span></div>
              <div class="md-row"><span class="k">Tasa</span><span class="v" id="md_tasaretencion"></span></div>
              <div class="md-row"><span class="k">Total Pagado</span><span class="v mono" id="md_total_pagado"></span></div>
              <div class="md-row"><span class="k">Total Retenido</span><span class="v big-ok mono" id="md_total_retenido"></span></div>
            </div>
          </div>
        </div>

        <div id="md_observacion_wrap" class="mb-3" style="display:none;">
          <div class="md-section">Observacion</div>
          <div class="md-obs" id="md_observacion"></div>
        </div>

        {{-- Datos del envio a SUNAT (solo si hay codigohash o mensaje_error) --}}
        <div id="md_sunat_wrap" class="mb-3" style="display:none;">
          <div class="md-section">Envio a SUNAT</div>
          <div class="md-sunat">
            <div class="row g-2">
              <div class="col-md-6" id="md_sunat_hash_wrap" style="display:none;">
                <div class="lbl-mini">CodigoHash</div>
                <div class="val-mono" id="md_sunat_hash"></div>
              </div>
              <div class="col-md-6" id="md_sunat_qr_wrap" style="display:none;">
                <div class="lbl-mini">CodigoQR</div>
                <div class="val-mono" id="md_sunat_qr"></div>
              </div>
              <div class="col-md-12" id="md_sunat_err_wrap" style="display:none;">
                <div class="lbl-mini">Mensaje de error</div>
                <div class="val-err" id="md_sunat_err"></div>
              </div>
            </div>
          </div>
        </div>

        {{-- Anulacion SUNAT (Resumen de Reversion). Solo si hay nro_ticket_baja --}}
        <div id="md_baja_wrap" class="mb-3" style="display:none;">
          <div class="md-section">Anulacion en SUNAT</div>
          <div class="md-sunat">
            <div class="row g-2">
              <div class="col-md-6">
                <div class="lbl-mini">N° Ticket</div>
                <div class="val-mono" id="md_baja_ticket"></div>
              </div>
              <div class="col-md-6">
                <div class="lbl-mini">ID Documento</div>
                <div class="val-mono" id="md_baja_iddoc"></div>
              </div>
              <div class="col-md-6">
                <div class="lbl-mini">Nombre Archivo</div>
                <div class="val-mono" id="md_baja_archivo"></div>
              </div>
              <div class="col-md-6">
                <div class="lbl-mini">Fecha de envio</div>
                <div class="val-mono" id="md_baja_fecha"></div>
              </div>
              <div class="col-md-12">
                <div class="lbl-mini">Motivo</div>
                <div class="val-mono" id="md_baja_motivo"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="md-section">Documentos relacionados</div>
        <div class="md-table-wrap">
          <table class="table md-table align-middle">
            <thead>
              <tr>
                <th class="text-center">N°</th>
                <th>Tipo Doc.</th>
                <th>Serie-Numero</th>
                <th>F. Emision</th>
                <th>F. Pago</th>
                <th class="text-end">Importe Doc.</th>
                <th class="text-end">Importe Pago</th>
                <th class="text-end">Retenido</th>
                <th class="text-end">Neto</th>
              </tr>
            </thead>
            <tbody id="md_detalles_body"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <a id="md_btn_pdf" href="#" target="_blank" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-file-pdf"></i> Ver PDF
        </a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
