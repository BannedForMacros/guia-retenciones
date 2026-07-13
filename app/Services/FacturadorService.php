<?php

namespace App\Services;

use App\Models\Parametro;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente GENERICO del facturador electronico (SUNAT via PSE).
 *
 * No esta atado a ningun proveedor concreto: la URL base, la credencial y el
 * RUC emisor se leen de la tabla `parametros`, de modo que sirve para
 * CUALQUIER entidad/documento sin tocar codigo ni .env:
 *
 *   base       -> Parametro #7 (api_facturacion)   ej. http://e-dbfact.dbperu.com:81
 *   credencial -> Parametro #1 (credencial)
 *   RUC emisor -> Parametro #2 (ruc_entidad)
 *
 * Endpoints (todos sobre la misma base):
 *   PUT  {base}/api/Rentations           -> emision del CRE
 *   POST {base}/api/ResumenReversionCRE  -> baja/reversion del CRE
 *   POST {base}/api/consulta/estado      -> consulta de estado (cualquier TipoComprobante)
 */
class FacturadorService
{
    /** Segundos de timeout para las llamadas HTTP. */
    private const TIMEOUT = 60;

    private function baseUrl(): string
    {
        return rtrim((string) optional(Parametro::find(7))->valor, '/');
    }

    private function credencial(): string
    {
        return (string) optional(Parametro::find(1))->valor;
    }

    private function rucEmisor(): string
    {
        return (string) optional(Parametro::find(2))->valor;
    }

    private function configurado(): bool
    {
        return $this->baseUrl() !== '' && $this->credencial() !== '';
    }

    /**
     * 'R011-00000006' -> 'R011-6'. El facturador rechaza ceros a la izquierda.
     */
    private function stripCeros(string $serienumero): string
    {
        if (strpos($serienumero, '-') === false) return $serienumero;
        [$serie, $numero] = explode('-', $serienumero, 2);
        $clean = ltrim($numero, '0');
        return $serie.'-'.($clean === '' ? '0' : $clean);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Emision del CRE:  PUT {base}/api/Rentations
    // ─────────────────────────────────────────────────────────────────────

    public function enviar(array $payload): array
    {
        if (!$this->configurado()) {
            Log::error('Facturador sin configurar (parametros 1/7): no se puede enviar a SUNAT');
            return $this->emptyResult('facturador no configurado (parametros)');
        }

        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders(['credencial' => $this->credencial()])
                ->acceptJson()
                ->asJson()
                ->timeout(self::TIMEOUT)
                ->put('/api/Rentations', $payload);

            $json = $resp->json();
            $body = $resp->body();

            if (!$resp->successful()) {
                Log::warning('Facturador envio fallo HTTP', [
                    'status' => $resp->status(),
                    'serie'  => $payload['globales']['numeracion'] ?? null,
                    'body'   => substr($body, 0, 500),
                ]);
                return [
                    'ok'            => false,
                    'http'          => $resp->status(),
                    'codigohash'    => null,
                    'codigoqr'      => null,
                    'pdf417'        => null,
                    'mensaje_error' => 'http '.$resp->status().': '.substr($body, 0, 380),
                    'respuesta_raw' => $body,
                ];
            }

            $exito = (bool) ($json['Exito'] ?? false);
            $err   = $json['MensajeError'] ?? null;

            if (!$exito) {
                Log::warning('Facturador reporto Exito=false', [
                    'serie'   => $payload['globales']['numeracion'] ?? null,
                    'message' => $err,
                ]);
            }

            return [
                'ok'            => $exito,
                'http'          => $resp->status(),
                'codigohash'    => $json['CodigoHash'] ?? null,
                'codigoqr'      => $json['CodigoQr']   ?? null,
                'pdf417'        => $json['pdf417']     ?? null,
                'mensaje_error' => $err ? substr((string) $err, 0, 1000) : null,
                'respuesta_raw' => $body,
            ];
        } catch (Throwable $e) {
            Log::error('Facturador envio exception', [
                'serie'   => $payload['globales']['numeracion'] ?? null,
                'message' => $e->getMessage(),
            ]);
            return $this->emptyResult('conn: '.substr($e->getMessage(), 0, 800));
        }
    }

    private function emptyResult(string $error): array
    {
        return [
            'ok'            => false,
            'http'          => null,
            'codigohash'    => null,
            'codigoqr'      => null,
            'pdf417'        => null,
            'mensaje_error' => $error,
            'respuesta_raw' => null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Baja / reversion del CRE:  POST {base}/api/ResumenReversionCRE
    // ─────────────────────────────────────────────────────────────────────

    public function enviarReversion(array $payload): array
    {
        if (!$this->configurado()) {
            Log::error('Facturador sin configurar (parametros 1/7): no se puede enviar reversion a SUNAT');
            return $this->emptyReversionResult('facturador no configurado (parametros)');
        }

        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders(['credencial' => $this->credencial()])
                ->acceptJson()
                ->asJson()
                ->timeout(self::TIMEOUT)
                ->post('/api/ResumenReversionCRE', $payload);

            $json = $resp->json();
            $body = $resp->body();

            if (!$resp->successful()) {
                Log::warning('Facturador reversion fallo HTTP', [
                    'status'      => $resp->status(),
                    'iddocumento' => $payload['IdDocumento'] ?? null,
                    'body'        => substr($body, 0, 500),
                ]);
                return [
                    'ok'             => false,
                    'http'           => $resp->status(),
                    'nro_ticket'     => null,
                    'nombre_archivo' => null,
                    'mensaje_error'  => 'http '.$resp->status().': '.substr($body, 0, 380),
                    'respuesta_raw'  => $body,
                ];
            }

            $exito = (bool) ($json['Exito'] ?? false);
            $err   = $json['MensajeError'] ?? null;

            if (!$exito) {
                Log::warning('Facturador reversion reporto Exito=false', [
                    'iddocumento' => $payload['IdDocumento'] ?? null,
                    'message'     => $err,
                ]);
            }

            return [
                'ok'             => $exito,
                'http'           => $resp->status(),
                'nro_ticket'     => $json['NroTicket']     ?? null,
                'nombre_archivo' => $json['NombreArchivo'] ?? null,
                'mensaje_error'  => $err ? substr((string) $err, 0, 1000) : null,
                'respuesta_raw'  => $body,
            ];
        } catch (Throwable $e) {
            Log::error('Facturador reversion exception', [
                'iddocumento' => $payload['IdDocumento'] ?? null,
                'message'     => $e->getMessage(),
            ]);
            return $this->emptyReversionResult('conn: '.substr($e->getMessage(), 0, 800));
        }
    }

    private function emptyReversionResult(string $error): array
    {
        return [
            'ok'             => false,
            'http'           => null,
            'nro_ticket'     => null,
            'nombre_archivo' => null,
            'mensaje_error'  => $error,
            'respuesta_raw'  => null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Consulta de estado (generica):  POST {base}/api/consulta/estado
    //
    //  Request:  { RucEmisor, TipoComprobante, SerieNumero }
    //  Response: { CodigoRespuesta (A/B/O/P), MensajeRespuesta, DetalleRespuesta,
    //              Exito, MensajeError, Pila }
    //    A=Aceptado  B=Rechazado  O=Observado  P=Pendiente (sin respuesta SUNAT)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param  string $tipoComprobante  Codigo SUNAT del documento ('20' = Retencion).
     * @param  string $serieNumero      'R011-00000006' (se limpian los ceros).
     * @return array{ok:bool, http:int|null, codigo:string|null, mensaje:string|null,
     *                detalle:string|null, mensaje_error:string|null, respuesta_raw:string|null}
     */
    public function consultarEstado(string $tipoComprobante, string $serieNumero): array
    {
        if (!$this->configurado()) {
            return $this->emptyEstadoResult('facturador no configurado (parametros)');
        }

        $payload = [
            'RucEmisor'       => $this->rucEmisor(),
            'TipoComprobante' => $tipoComprobante,
            'SerieNumero'     => $this->stripCeros($serieNumero),
        ];

        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders(['credencial' => $this->credencial()])
                ->acceptJson()
                ->asJson()
                ->timeout(self::TIMEOUT)
                ->post('/api/consulta/estado', $payload);

            $json = $resp->json();
            $body = $resp->body();

            if (!$resp->successful()) {
                Log::warning('Facturador consulta estado fallo HTTP', [
                    'status'      => $resp->status(),
                    'serienumero' => $payload['SerieNumero'],
                    'body'        => substr($body, 0, 500),
                ]);
                return [
                    'ok'            => false,
                    'http'          => $resp->status(),
                    'codigo'        => null,
                    'mensaje'       => null,
                    'detalle'       => null,
                    'mensaje_error' => 'http '.$resp->status().': '.substr($body, 0, 380),
                    'respuesta_raw' => $body,
                ];
            }

            $codigo = $json['CodigoRespuesta'] ?? null;
            $codigo = $codigo !== null ? strtoupper(trim((string) $codigo)) : null;
            $err    = $json['MensajeError'] ?? null;

            return [
                'ok'            => (bool) ($json['Exito'] ?? false),
                'http'          => $resp->status(),
                'codigo'        => $codigo ?: null,
                'mensaje'       => $json['MensajeRespuesta'] ?? null,
                'detalle'       => $json['DetalleRespuesta'] ?? null,
                'mensaje_error' => $err ? substr((string) $err, 0, 1000) : null,
                'respuesta_raw' => $body,
            ];
        } catch (Throwable $e) {
            Log::error('Facturador consulta estado exception', [
                'serienumero' => $payload['SerieNumero'],
                'message'     => $e->getMessage(),
            ]);
            return $this->emptyEstadoResult('conn: '.substr($e->getMessage(), 0, 800));
        }
    }

    private function emptyEstadoResult(string $error): array
    {
        return [
            'ok'            => false,
            'http'          => null,
            'codigo'        => null,
            'mensaje'       => null,
            'detalle'       => null,
            'mensaje_error' => $error,
            'respuesta_raw' => null,
        ];
    }
}
