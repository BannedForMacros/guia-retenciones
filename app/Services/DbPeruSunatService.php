<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente del endpoint remoto DB Peru e-dbfact.
 *
 *   PUT http://e-dbfact.dbperu.com:8180/api/Rentations
 *   Header: credencial: <token>
 *
 * El servicio firma el XML, lo envia a SUNAT, recibe el CDR y devuelve:
 *   { Exito, CodigoHash, CodigoQr, pdf417, MensajeError, Pila }
 *
 * Convencion de respuesta de este wrapper:
 *  [
 *    'ok'             => bool,            // Exito == true
 *    'http'           => int|null,
 *    'codigohash'     => string|null,
 *    'codigoqr'       => string|null,
 *    'pdf417'         => string|null,
 *    'mensaje_error'  => string|null,     // del JSON o del wrapper
 *    'respuesta_raw'  => string|null,     // JSON completo, para auditoria
 *  ]
 */
class DbPeruSunatService
{
    private function isEnabled(): bool
    {
        return (bool) config('services.dbperu.enabled', true);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.dbperu.base_url'), '/');
    }

    private function credencial(): string
    {
        return (string) config('services.dbperu.credencial', '');
    }

    public function enviar(array $payload): array
    {
        if (!$this->isEnabled()) {
            return $this->emptyResult('dbperu disabled');
        }
        if ($this->credencial() === '') {
            Log::error('DBPERU_CREDENCIAL vacia: no se puede enviar a SUNAT');
            return $this->emptyResult('credencial no configurada');
        }

        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders(['credencial' => $this->credencial()])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.dbperu.timeout', 60))
                ->put('/api/Rentations', $payload);

            $json = $resp->json();
            $body = $resp->body();

            // Casos no-200 (ej. 405 si por alguna razon resuelve POST, 401, etc.)
            if (!$resp->successful()) {
                Log::warning('DB Peru envio fallo HTTP', [
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
                // 200 OK pero el servicio reporta error logico
                Log::warning('DB Peru reporto Exito=false', [
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
            Log::error('DB Peru envio exception', [
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

    /**
     * Envia el Resumen de Reversion (baja) a DB Peru.
     *
     *   POST http://e-dbfact.dbperu.com:8180/api/ResumenReversionCRE
     *   Header: credencial: <token>
     *
     * Respuesta del servicio:
     *   { NroTicket, NombreArchivo, Exito, MensajeError, Pila }
     *
     * Convencion de retorno de este wrapper:
     *   [
     *     'ok'             => bool,           // Exito == true
     *     'http'           => int|null,
     *     'nro_ticket'     => string|null,
     *     'nombre_archivo' => string|null,
     *     'mensaje_error'  => string|null,
     *     'respuesta_raw'  => string|null,
     *   ]
     */
    public function enviarReversion(array $payload): array
    {
        if (!$this->isEnabled()) {
            return $this->emptyReversionResult('dbperu disabled');
        }
        if ($this->credencial() === '') {
            Log::error('DBPERU_CREDENCIAL vacia: no se puede enviar reversion a SUNAT');
            return $this->emptyReversionResult('credencial no configurada');
        }

        try {
            $resp = Http::baseUrl($this->baseUrl())
                ->withHeaders(['credencial' => $this->credencial()])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.dbperu.timeout', 60))
                ->post('/api/ResumenReversionCRE', $payload);

            $json = $resp->json();
            $body = $resp->body();

            if (!$resp->successful()) {
                Log::warning('DB Peru reversion fallo HTTP', [
                    'status'        => $resp->status(),
                    'iddocumento'   => $payload['IdDocumento'] ?? null,
                    'body'          => substr($body, 0, 500),
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
                Log::warning('DB Peru reversion reporto Exito=false', [
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
            Log::error('DB Peru reversion exception', [
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
}
