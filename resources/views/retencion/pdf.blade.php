<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Retencion {{ $cabecera->serienumero }}</title>
<style>
  @page { size: A4 portrait; margin: 1cm; }
  html, body {
    margin: 0; padding: 0;
    background: #ffffff;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
  }

  /* ========== CABECERA ========== */
  table.cabecera { width: 100%; border-collapse: collapse; }
  table.cabecera td { vertical-align: middle; }
  td.cab-izq { width: 65%; padding-right: 10px; }
  td.cab-der { width: 35%; }

  .logo { height: 80px; }
  .empresa-nombre {
    font-weight: bold;
    font-size: 16px;
    text-transform: uppercase;
    text-align: center;
    margin: 4px 0 2px 0;
  }
  .empresa-direccion {
    font-size: 8px;
    text-align: center;
    line-height: 1.15;
  }

  .recuadro {
    border: 2px solid #000;
    padding: 8px;
    text-align: center;
  }
  .rec-ruc {
    font-size: 10px;
    margin: 0 0 4px 0;
    padding-bottom: 4px;
    border-bottom: 1px solid #444;
  }
  .rec-tipo {
    font-weight: bold;
    font-size: 11px;
    text-transform: uppercase;
    line-height: 1.1;
    margin: 4px 0 4px 0;
  }
  .rec-serie {
    font-weight: bold;
    font-size: 13px;
    margin: 0;
  }

  /* ========== TABLA DATOS PROVEEDOR ========== */
  table.proveedor {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #000;
    margin-top: 12px;
  }
  table.proveedor td {
    border: 0.5px solid #555;
    padding: 3px 4px;
    font-size: 8px;
  }
  table.proveedor td.lbl {
    width: 160px;
    font-weight: bold;
    text-transform: uppercase;
    background: #ffffff;
  }
  table.proveedor td.val { font-weight: normal; }

  /* ========== TABLA DE MOVIMIENTOS ========== */
  table.mov {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }
  table.mov th, table.mov td {
    border: 0.5px solid #888;
    padding: 4px;
  }
  table.mov thead th {
    background: #333333;
    color: #ffffff;
    font-weight: bold;
    font-size: 7px;
    text-align: center;
    text-transform: uppercase;
  }
  table.mov tbody td {
    font-size: 7.5px;
    background: #ffffff;
  }
  table.mov tbody td.num     { text-align: center; }
  table.mov tbody td.monto   { text-align: right; }
  table.mov tfoot td {
    font-weight: bold;
    font-size: 8px;
    background: #ffffff;
  }
  table.mov tfoot td.tot-lbl   { text-align: center; }
  table.mov tfoot td.tot-monto { text-align: right; }

  /* ========== PIE ========== */
  .pie { margin-top: 10px; }
  .pie .repr {
    font-size: 7px;
    font-style: italic;
  }
  .pie .letras {
    margin-top: 4px;
    font-size: 8px;
  }
  .pie .letras .lbl {
    font-weight: bold;
  }
  .pie .letras .monto {
    text-transform: uppercase;
  }

  /* SUNAT footer */
  .sunat-block {
    margin-top: 8px;
    border-top: 1px solid #aaa;
    padding-top: 6px;
    font-size: 7px;
  }
  .sunat-block .lbl-mini {
    text-transform: uppercase;
    font-weight: bold;
    color: #333;
    font-size: 6.5px;
    letter-spacing: 0.04em;
  }
  .sunat-block .codigo {
    font-family: "Courier New", monospace;
    word-break: break-all;
    line-height: 1.2;
  }
  .sunat-block table { width: 100%; border-collapse: collapse; }
  .sunat-block table td { vertical-align: top; padding: 4px; }
  .sunat-block .qr-box { width: 130px; padding-right: 8px !important; }
  .sunat-block .qr-img { width: 120px; height: 120px; }
  .sunat-block .pdf417-img { width: 100%; height: 38px; }

  /* helpers */
  .nowrap { white-space: nowrap; }
</style>
</head>
<body>

@php
  $tiposDocRel = [
    '01' => 'FACTURA',
    '03' => 'BOLETA',
    '07' => 'NOTA CREDITO',
    '08' => 'NOTA DEBITO',
    '12' => 'RECIBO HONORARIOS',
    '14' => 'LIQ. COMPRA',
    '99' => 'OTROS',
  ];
  $simMon = function ($m) { return $m === 'USD' ? 'US$' : 'S/'; };
  $fmt2   = function ($v) { return number_format((float) $v, 2, '.', ','); };

  $simbolo = $simMon($cabecera->monedaimportetotalretenido ?? 'PEN');
  $tasaTxt = rtrim(rtrim($cabecera->tasaretencion ?? '0', '0'), '.');
  $logoPath = public_path('img/logo.png');
@endphp

{{-- ============= CABECERA ============= --}}
<table class="cabecera">
  <tr>
    <td class="cab-izq">
      @if (file_exists($logoPath))
        <img src="{{ $logoPath }}" class="logo" alt="logo">
      @endif
      <div class="empresa-nombre">{{ $empresa->razonSocial }}</div>
      <div class="empresa-direccion">
        {!! nl2br(e($empresa->direccion ?: '')) !!}
      </div>
    </td>
    <td class="cab-der">
      <div class="recuadro">
        <div class="rec-ruc">RUC {{ $empresa->ruc }}</div>
        <div class="rec-tipo">COMPROBANTE DE<br>RETENCION ELECTRONICA</div>
        <div class="rec-serie">{{ $cabecera->serienumero }}</div>
      </div>
    </td>
  </tr>
</table>

{{-- ============= DATOS PROVEEDOR ============= --}}
<table class="proveedor">
  <tr>
    <td class="lbl" style="width:160px;">RUC</td>
    <td class="val" style="width:50%;">{{ $cabecera->numdocproveedor }}</td>
    <td class="lbl" style="width:160px;">FECHA EMISION</td>
    <td class="val">{{ $cabecera->fechaemision }}</td>
  </tr>
  <tr>
    <td class="lbl">SEÑOR(ES)</td>
    <td class="val" colspan="3">{{ $cabecera->razonsocialproveedor }}</td>
  </tr>
  <tr>
    <td class="lbl">DIRECCION</td>
    <td class="val" colspan="3">{{ $cabecera->direccionproveedor ?: '—' }}</td>
  </tr>
  <tr>
    <td class="lbl">REGIMEN DE RETENCION</td>
    <td class="val">{{ $cabecera->regimenretencion }} - TASA {{ $tasaTxt }}%</td>
    <td class="lbl">MONEDA</td>
    <td class="val">{{ $cabecera->monedaimportetotalretenido }}</td>
  </tr>
  @if (!empty($cabecera->observacion))
    <tr>
      <td class="lbl">OBSERVACION</td>
      <td class="val" colspan="3">{{ $cabecera->observacion }}</td>
    </tr>
  @endif
</table>

{{-- ============= TABLA DE MOVIMIENTOS ============= --}}
<table class="mov">
  <thead>
    <tr>
      <th style="width:25px;">N°</th>
      <th style="width:55px;">TIPO DOC.</th>
      <th>SERIE-NUMERO</th>
      <th style="width:60px;">F. EMISION</th>
      <th style="width:65px;">IMP. DOC.</th>
      <th style="width:35px;">MON.</th>
      <th style="width:60px;">F. PAGO</th>
      <th style="width:35px;">N° PAGO</th>
      <th style="width:70px;">IMP. PAGO</th>
      <th style="width:70px;">RETENIDO</th>
      <th style="width:70px;">NETO</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($detalles as $i => $d)
      <tr>
        <td class="num">{{ $i + 1 }}</td>
        <td class="num">{{ $tiposDocRel[$d->tipodocrelacionado] ?? $d->tipodocrelacionado }}</td>
        <td class="num">{{ $d->serienumerorelacionado }}</td>
        <td class="num">{{ $d->fechaemisiondocrelacionado }}</td>
        <td class="monto">{{ $fmt2($d->importetotaldocrela) }}</td>
        <td class="num">{{ $d->monedaimportedocrela }}</td>
        <td class="num">{{ $d->fechapago }}</td>
        <td class="num">{{ $d->numeropago }}</td>
        <td class="monto">{{ $fmt2($d->importepagosinretencion) }}</td>
        <td class="monto">{{ $fmt2($d->importeretenido) }}</td>
        <td class="monto">{{ $fmt2($d->montonetopagar) }}</td>
      </tr>
    @endforeach
  </tbody>
  <tfoot>
    @php
      // importetotalpagado guarda el NETO (lo que se paga al proveedor); el bruto = neto + retenido
      $totalGross = (float) $cabecera->importetotalpagado + (float) $cabecera->importetotalretenido;
    @endphp
    <tr>
      <td class="tot-lbl" colspan="8">TOTALES</td>
      <td class="tot-monto">{{ $simbolo }} {{ $fmt2($totalGross) }}</td>
      <td class="tot-monto">{{ $simbolo }} {{ $fmt2($cabecera->importetotalretenido) }}</td>
      <td class="tot-monto">{{ $simbolo }} {{ $fmt2($cabecera->importetotalpagado) }}</td>
    </tr>
  </tfoot>
</table>

{{-- ============= PIE ============= --}}
<div class="pie">
  <div class="repr">Representación Impresa del Comprobante de Retención</div>
  <div class="letras">
    <span class="lbl">Importe en Letras:</span>
    <span class="monto">{{ $importeLetras }}</span>
  </div>
</div>

{{-- ============= BLOQUE SUNAT (CodigoHash, QR, pdf417) ============= --}}
@php
  $hasSunat = !empty($cabecera->codigohash) || !empty($cabecera->codigoqr) || !empty($cabecera->pdf417);
@endphp

@if ($hasSunat)
  @php
    $bc2D = new \Milon\Barcode\DNS2D();

    $qrPng = null;
    if (!empty($cabecera->codigoqr)) {
      try {
        $qrPng = $bc2D->getBarcodePNG($cabecera->codigoqr, 'QRCODE,M', 4, 4);
      } catch (\Throwable $e) { $qrPng = null; }
    }

    $pdf417Png = null;
    if (!empty($cabecera->pdf417)) {
      try {
        $pdf417Png = $bc2D->getBarcodePNG($cabecera->pdf417, 'PDF417', 2, 1);
      } catch (\Throwable $e) { $pdf417Png = null; }
    }
  @endphp

  <div class="sunat-block">
    <table>
      <tr>
        <td class="qr-box">
          @if ($qrPng)
            <img class="qr-img" src="data:image/png;base64,{{ $qrPng }}" alt="QR">
          @endif
        </td>
        <td>
          <div class="lbl-mini">Resumen / Código de Hash</div>
          <div class="codigo" style="font-size:8px; margin-bottom:6px;">{{ $cabecera->codigohash ?? '—' }}</div>

          @if ($pdf417Png)
            <div class="lbl-mini" style="margin-top:4px;">Representación PDF417</div>
            <img class="pdf417-img" src="data:image/png;base64,{{ $pdf417Png }}" alt="pdf417">
          @endif
        </td>
      </tr>
    </table>
  </div>
@elseif ($cabecera->estadoproceso === 'F')
  <div class="sunat-block">
    <div class="lbl-mini" style="color:#b91c1c;">No enviado a SUNAT</div>
    @if (!empty($cabecera->mensaje_error))
      <div class="codigo" style="color:#b91c1c;">{{ $cabecera->mensaje_error }}</div>
    @endif
  </div>
@endif

</body>
</html>
