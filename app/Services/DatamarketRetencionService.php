<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente HTTP del datamarket (FastAPI). Replicacion best-effort:
 * los errores se reportan estructurados y NUNCA se relanzan, para no
 * romper la operacion local en MySQL.
 *
 * Forma de la respuesta (siempre):
 *  [
 *    'ok'    => bool,        // true si la replicacion quedo consistente
 *    'http'  => int|null,    // codigo HTTP (null si no hubo conexion)
 *    'error' => string|null, // mensaje corto para guardar en error_replicacion
 *  ]
 */
class DatamarketRetencionService
{
    private function isEnabled(): bool
    {
        return (bool) config('services.datamarket.enabled', true);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.datamarket.base_url'), '/');
    }

    private function client()
    {
        $req = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.datamarket.timeout', 5))
            ->withOptions([
                // connectTimeout() recien existe en Laravel 9+. Aqui usamos la opcion Guzzle.
                'connect_timeout' => (int) config('services.datamarket.connect_timeout', 2),
            ]);

        $token = config('services.datamarket.token');
        if (!empty($token)) {
            $req = $req->withToken($token);
        }
        return $req;
    }

    /**
     * Replica una retencion (cabecera + detalles) al datamarket.
     */
    public function crear(array $payload): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => null, 'error' => 'datamarket disabled'];
        }

        try {
            $resp = $this->client()->post('/retenciones', $payload);

            // 201 Created -> OK normal
            // 409 Conflict -> ya existia en datamarket (idempotente, lo tratamos como OK)
            if ($resp->status() === 201 || $resp->status() === 409) {
                return ['ok' => true, 'http' => $resp->status(), 'error' => null];
            }

            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            Log::warning('Datamarket crear fallo', [
                'status'  => $resp->status(),
                'serie'   => $payload['serienumero'] ?? null,
                'message' => $msg,
            ]);
            return ['ok' => false, 'http' => $resp->status(), 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket crear exception', [
                'serie'   => $payload['serienumero'] ?? null,
                'message' => $e->getMessage(),
            ]);
            return [
                'ok'    => false,
                'http'  => null,
                'error' => 'conn: '.substr($e->getMessage(), 0, 400),
            ];
        }
    }

    /**
     * Replica la anulacion al datamarket.
     */
    public function anular(string $rucempresa, string $serienumero, string $motivo, string $usuario): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => null, 'error' => 'datamarket disabled'];
        }

        $url = '/retenciones/'.rawurlencode($rucempresa).'/'.rawurlencode($serienumero).'/anular';
        $body = [
            'motivo'             => $motivo,
            'usuariomodificador' => $usuario,
        ];

        try {
            $resp = $this->client()->post($url, $body);

            // 204 No Content -> OK
            // 404 -> la retencion no existe en datamarket (nunca se replico): no es error fatal
            // 409 -> ya estaba anulada en datamarket: idempotente
            if (in_array($resp->status(), [204, 404, 409], true)) {
                return ['ok' => true, 'http' => $resp->status(), 'error' => null];
            }

            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            Log::warning('Datamarket anular fallo', [
                'status'  => $resp->status(),
                'serie'   => $serienumero,
                'message' => $msg,
            ]);
            return ['ok' => false, 'http' => $resp->status(), 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket anular exception', [
                'serie'   => $serienumero,
                'message' => $e->getMessage(),
            ]);
            return [
                'ok'    => false,
                'http'  => null,
                'error' => 'conn: '.substr($e->getMessage(), 0, 400),
            ];
        }
    }

    /**
     * Replica el resultado del envio a SUNAT (CodigoHash, QR, pdf417, estados...)
     * al datamarket. Endpoint: PATCH /retenciones/{ruc}/{serie}/envio-sunat
     */
    public function actualizarEnvioSunat(
        string $rucempresa,
        string $serienumero,
        array $envio
    ): array {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => null, 'error' => 'datamarket disabled'];
        }

        $url = '/retenciones/'.rawurlencode($rucempresa).'/'.rawurlencode($serienumero).'/envio-sunat';

        try {
            $resp = $this->client()->patch($url, $envio);

            if ($resp->status() === 204) {
                return ['ok' => true, 'http' => 204, 'error' => null];
            }
            // 404 = no esta replicada en datamarket todavia (no se replico al crear).
            // No es fatal: dejamos el dato en local; al reintentar crear se sincroniza.
            if ($resp->status() === 404) {
                return ['ok' => true, 'http' => 404, 'error' => 'no replicada en datamarket'];
            }

            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            Log::warning('Datamarket envio-sunat fallo', [
                'status'  => $resp->status(),
                'serie'   => $serienumero,
                'message' => $msg,
            ]);
            return ['ok' => false, 'http' => $resp->status(), 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket envio-sunat exception', [
                'serie'   => $serienumero,
                'message' => $e->getMessage(),
            ]);
            return [
                'ok'    => false,
                'http'  => null,
                'error' => 'conn: '.substr($e->getMessage(), 0, 400),
            ];
        }
    }

    // ─── Series (MaestroDocumentoSerie) ─────────────────────────────────

    /**
     * Lista las series habilitadas para un TipoDocumento.
     */
    public function listarSeries(int $tipoDocumento): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'items' => [], 'error' => 'datamarket disabled'];
        }
        try {
            $resp = $this->client()->get('/maestros/series', ['tipo_documento' => $tipoDocumento]);
            if ($resp->successful()) {
                return [
                    'ok'    => true,
                    'items' => $resp->json('items') ?? [],
                    'error' => null,
                ];
            }
            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            return ['ok' => false, 'items' => [], 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket series listar exception', ['msg' => $e->getMessage()]);
            return ['ok' => false, 'items' => [], 'error' => 'conn: '.substr($e->getMessage(), 0, 400)];
        }
    }

    /**
     * Crea una serie nueva (UltimoValor = 0).
     * Devuelve ['ok','serie','error','http']. http=409 si ya existia.
     */
    public function crearSerie(int $tipoDocumento, int $numSerie, ?string $ctrResp = null): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'serie' => null, 'http' => null, 'error' => 'datamarket disabled'];
        }
        $body = [
            'tipo_documento' => $tipoDocumento,
            'num_serie'      => $numSerie,
            'ctr_resp'       => $ctrResp,
        ];
        try {
            $resp = $this->client()->post('/maestros/series', $body);
            if ($resp->status() === 201) {
                return ['ok' => true, 'serie' => $resp->json(), 'http' => 201, 'error' => null];
            }
            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            return ['ok' => false, 'serie' => null, 'http' => $resp->status(), 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket series crear exception', ['msg' => $e->getMessage()]);
            return ['ok' => false, 'serie' => null, 'http' => null, 'error' => 'conn: '.substr($e->getMessage(), 0, 400)];
        }
    }

    /**
     * Devuelve el siguiente correlativo para una (tipo_documento, num_serie).
     * Resp: ['ok','siguiente_numero','serie_formateada','ultimo_valor','error','http'].
     */
    public function siguienteNumeroSerie(int $tipoDocumento, int $numSerie): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => null, 'error' => 'datamarket disabled'];
        }
        try {
            $resp = $this->client()->get('/maestros/series/siguiente-numero', [
                'tipo_documento' => $tipoDocumento,
                'num_serie'      => $numSerie,
            ]);
            if ($resp->successful()) {
                $b = $resp->json();
                return [
                    'ok'                => true,
                    'http'              => 200,
                    'siguiente_numero'  => $b['siguiente_numero']  ?? '00000001',
                    'serie_formateada'  => $b['serie_formateada']  ?? null,
                    'ultimo_valor'      => $b['ultimo_valor']      ?? 0,
                    'error'             => null,
                ];
            }
            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            return ['ok' => false, 'http' => $resp->status(), 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket siguiente-numero exception', ['msg' => $e->getMessage()]);
            return ['ok' => false, 'http' => null, 'error' => 'conn: '.substr($e->getMessage(), 0, 400)];
        }
    }

    /**
     * Lista proveedores activos de MaestroProveedores con busqueda por
     * RUC o razon social. Delega al FastAPI /proveedores.
     *
     * Devuelve:
     *  ['ok' => bool, 'items' => array, 'total' => int, 'error' => ?string]
     */
    public function listarProveedores(?string $search = null, int $limit = 25, int $offset = 0): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'items' => [], 'total' => 0, 'error' => 'datamarket disabled'];
        }

        $query = array_filter([
            'search' => $search,
            'limit'  => $limit,
            'offset' => $offset,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $resp = $this->client()->get('/proveedores', $query);

            if ($resp->successful()) {
                $body = $resp->json() ?? [];
                return [
                    'ok'    => true,
                    'items' => $body['items'] ?? [],
                    'total' => (int) ($body['total'] ?? 0),
                    'error' => null,
                ];
            }

            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            Log::warning('Datamarket proveedores fallo', [
                'status'  => $resp->status(),
                'search'  => $search,
                'message' => $msg,
            ]);
            return ['ok' => false, 'items' => [], 'total' => 0, 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket proveedores exception', [
                'search'  => $search,
                'message' => $e->getMessage(),
            ]);
            return [
                'ok'    => false,
                'items' => [],
                'total' => 0,
                'error' => 'conn: '.substr($e->getMessage(), 0, 400),
            ];
        }
    }

    /**
     * Lista las facturas / NC / ND pendientes de pago de un proveedor, con
     * flag ya_retenida para las que ya estan en una retencion no anulada del
     * datamarket (cruce contra dbo.detalleretenciones).
     *
     * Devuelve:
     *  ['ok' => bool, 'items' => array, 'total' => int, 'error' => ?string]
     */
    public function listarFacturasProveedor(
        string $rucProveedor,
        string $rucEmpresa,
        ?string $search = null,
        bool $soloDisponibles = false,
        int $limit = 50,
        int $offset = 0
    ): array {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'items' => [], 'total' => 0, 'error' => 'datamarket disabled'];
        }

        $url = '/proveedores/'.rawurlencode($rucProveedor).'/facturas';
        $query = array_filter([
            'rucempresa'       => $rucEmpresa,
            'search'           => $search,
            'solo_disponibles' => $soloDisponibles ? 'true' : 'false',
            'limit'            => $limit,
            'offset'           => $offset,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $resp = $this->client()->get($url, $query);

            if ($resp->successful()) {
                $body = $resp->json() ?? [];
                return [
                    'ok'    => true,
                    'items' => $body['items'] ?? [],
                    'total' => (int) ($body['total'] ?? 0),
                    'error' => null,
                ];
            }

            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            Log::warning('Datamarket facturas-proveedor fallo', [
                'status'  => $resp->status(),
                'ruc'     => $rucProveedor,
                'message' => $msg,
            ]);
            return ['ok' => false, 'items' => [], 'total' => 0, 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket facturas-proveedor exception', [
                'ruc'     => $rucProveedor,
                'message' => $e->getMessage(),
            ]);
            return [
                'ok'    => false,
                'items' => [],
                'total' => 0,
                'error' => 'conn: '.substr($e->getMessage(), 0, 400),
            ];
        }
    }

    /**
     * Marca / desmarca a un proveedor como afecto a retencion. Endpoint:
     * PATCH /proveedores/{ruc}/afecto-retencion
     *
     * Devuelve:
     *  ['ok' => bool, 'afecto_retencion' => bool, 'http' => int|null, 'error' => ?string]
     */
    public function setAfectoRetencion(string $ruc, bool $afecto, string $usuario): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'afecto_retencion' => false, 'http' => null, 'error' => 'datamarket disabled'];
        }

        $url  = '/proveedores/'.rawurlencode($ruc).'/afecto-retencion';
        $body = [
            'afecto'             => $afecto,
            'usuariomodificador' => $usuario,
        ];

        try {
            $resp = $this->client()->patch($url, $body);

            if ($resp->successful()) {
                $j = $resp->json() ?? [];
                return [
                    'ok'                => true,
                    'afecto_retencion'  => (bool) ($j['afecto_retencion'] ?? $afecto),
                    'http'              => $resp->status(),
                    'error'             => null,
                ];
            }

            $msg = $this->extractError($resp->json(), $resp->body(), $resp->status());
            Log::warning('Datamarket afecto-retencion fallo', [
                'status'  => $resp->status(),
                'ruc'     => $ruc,
                'message' => $msg,
            ]);
            return ['ok' => false, 'afecto_retencion' => false, 'http' => $resp->status(), 'error' => $msg];
        } catch (Throwable $e) {
            Log::warning('Datamarket afecto-retencion exception', [
                'ruc'     => $ruc,
                'message' => $e->getMessage(),
            ]);
            return [
                'ok'                => false,
                'afecto_retencion'  => false,
                'http'              => null,
                'error'             => 'conn: '.substr($e->getMessage(), 0, 400),
            ];
        }
    }

    private function extractError($json, string $body, int $status): string
    {
        if (is_array($json)) {
            if (!empty($json['detail'])) {
                $detail = $json['detail'];
                if (is_string($detail)) return substr($detail, 0, 400);
                if (is_array($detail))  return substr(json_encode($detail), 0, 400);
            }
            if (!empty($json['message'])) {
                return substr((string) $json['message'], 0, 400);
            }
        }
        return 'http '.$status.': '.substr($body, 0, 380);
    }
}
