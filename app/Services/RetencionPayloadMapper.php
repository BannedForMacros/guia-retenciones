<?php

namespace App\Services;

/**
 * Construye los payloads JSON para los servicios externos:
 *  - buildSunatPayload(): formato exacto del PUT a DB Peru e-dbfact
 *  - buildDatamarketPayload(): formato del POST a nuestro FastAPI
 *
 * Mapeo critico:
 *  - tipodocidentidad DB ('06') -> JSON SUNAT ("6") (un solo digito)
 *  - tasaretencion string '3.00' -> JSON SUNAT 3.0 (float)
 *  - Importes en USD: convertir a PEN con factor_cambio antes de calcular
 *  - Cabecera de SUNAT siempre en PEN, aunque las lineas sean USD
 */
class RetencionPayloadMapper
{
    /**
     * Payload para DB Peru.
     *
     * @param array $local  Datos validados/normalizados:
     *  [
     *    'rucempresa', 'serienumero', 'fechaemision',
     *    'numdocproveedor', 'tipodocproveedor' ('06'), 'razonsocialproveedor',
     *    'direccionproveedor' (opcional),
     *    'tasaretencion' ('3.00'),
     *    'observacion' (opcional),
     *    'totalRetenidoPEN', 'totalPagadoPEN',  // ya calculados, en PEN
     *    'detalles' => [
     *      [
     *        'tipo_doc_rel' ('01'), 'serie_num_rel' ('F003-654'),
     *        'fecha_doc_rel', 'importe_doc' (en moneda original),
     *        'fecha_pago', 'numero_pago' (1..N int),
     *        'importe_pago' (en moneda original),
     *        'moneda' ('PEN' | 'USD'),
     *        'factor_cambio' (1.0 si PEN, X.X si USD),
     *        'importe_retenido_pen', 'monto_neto_pen',
     *      ], ...
     *    ]
     *  ]
     */
    public function buildSunatPayload(array $local): array
    {
        $tipoDocEmisor    = $this->shortTipoDoc('6');                // emisor siempre RUC
        $tipoDocProveedor = $this->shortTipoDoc($local['tipodocproveedor'] ?? '06');

        $detalles = [];
        foreach ($local['detalles'] as $d) {
            $monedaPago = strtoupper($d['moneda'] ?? 'PEN');
            $factor     = (float) ($d['factor_cambio'] ?? 1.0);
            $isUSD      = $monedaPago === 'USD';

            $detalles[] = [
                'pago' => [
                    'fechaPago'               => $d['fecha_pago'],
                    'numeroPago'              => (int) $d['numero_pago'],
                    'importePagoSinRetencion' => (float) $d['importe_pago'],
                    'monedaPago'              => $monedaPago,
                ],
                'retencion' => [
                    'montoTotalPagar'       => (float) $d['monto_neto_pen'],
                    'importeRetenido'       => (float) $d['importe_retenido_pen'],
                    'monedaImporteRetenido' => 'PEN',
                    'fechaRetencion'        => $local['fechaemision'],
                    'monedaMontoTotalPagar' => 'PEN',
                ],
                'tipoCambio' => [
                    'monedaReferenciaTipoCambio'    => $isUSD ? 'USD' : 'PEN',
                    'monedaObjetivoTipoCambio'      => 'PEN',
                    'factorAplicadoAMonedaOrigen'   => (float) ($isUSD ? $factor : 1.0),
                    'fechaCambio'                   => $local['fechaemision'],
                ],
                'tipoDocumentoRelacionado'         => $d['tipo_doc_rel'],
                'numeroDocumentoRelacionado'       => $d['serie_num_rel'],
                'fechaEmisionDocumentoRelacionado' => $d['fecha_doc_rel'] ?? $d['fecha_pago'],
                'importeTotalDocumentoRelacionado' => (float) ($d['importe_doc'] ?? $d['importe_pago']),
                'tipoMonedaDocumentoRelacionado'   => $monedaPago,
            ];
        }

        return [
            'globales' => [
                'versionUBL'        => '2.0',
                'versionEstructura' => '1.0',
                'firmaDigital'      => '',
                // DB Peru exige el numero SIN ceros a la izquierda: R011-6, no R011-00000006
                'numeracion'        => $this->stripLeadingZeros($local['serienumero']),
                'fechaEmision'      => $local['fechaemision'],
            ],
            'emisor' => [
                'numeroDocumentoIdentidad'   => $local['rucempresa'],
                'tipoDocumentoIdentidad'     => $tipoDocEmisor,
                'nombreComercial'            => '',
                'ubigeo'                     => '',
                'direcionCompleta'           => $local['direccionempresa'] ?? '',
                'urbanizacion'               => '',
                'provincia'                  => '',
                'departamento'               => '',
                'distrito'                   => '',
                'codigoPais'                 => '',
                'nombreCompletoRazonSocial'  => $local['razonsocialempresa'] ?? '',
            ],
            'proveedor' => [
                'numeroDocumentoIdentidad'   => $local['numdocproveedor'],
                'tipoDocumentoIdentidad'     => $tipoDocProveedor,
                'nombreComercial'            => '',
                'ubigeo'                     => '',
                'direcionCompleta'           => $local['direccionproveedor'] ?? '',
                'urbanizacion'               => '',
                'provincia'                  => '',
                'departamento'               => '',
                'distrito'                   => '',
                'codigoPais'                 => '',
                'nombreCompletoRazonSocial'  => $local['razonsocialproveedor'],
            ],
            'retencion_CRE' => [
                'regimenRetencion'           => '01',
                'tasaRetencion'              => (float) $local['tasaretencion'],
                'observaciones'              => (string) ($local['observacion'] ?? ''),
                'importeTotalRetenido'       => (float) $local['totalRetenidoPEN'],
                'monedaImporteTotalRetenido' => 'PEN',
                'importeTotalPagado'         => (float) $local['totalPagadoPEN'],
                'monedaImporteTotalPagado'   => 'PEN',
            ],
            'detalleRetenciones' => $detalles,
        ];
    }

    /**
     * Convierte tipodocidentidad de DB ('06', '01', etc.) al formato corto
     * que pide DB Peru ("6", "1"). Quita el cero a la izquierda.
     */
    private function shortTipoDoc(string $code): string
    {
        $code = trim($code);
        return ltrim($code, '0') ?: $code;
    }

    /**
     * 'R011-00000006' -> 'R011-6'. DB Peru rechaza ceros a la izquierda.
     */
    private function stripLeadingZeros(string $serienumero): string
    {
        if (strpos($serienumero, '-') === false) return $serienumero;
        [$serie, $numero] = explode('-', $serienumero, 2);
        $clean = ltrim($numero, '0');
        return $serie.'-'.($clean === '' ? '0' : $clean);
    }
}
