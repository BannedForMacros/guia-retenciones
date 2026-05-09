<?php

namespace App\Http\Controllers\Retencion;

use App\Http\Controllers\Controller;
use App\Models\Parametro;
use App\Models\Retencion;
use App\Services\DatamarketRetencionService;
use App\Services\DbPeruSunatService;
use App\Services\RetencionPayloadMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Luecano\NumeroALetras\NumeroALetras;

class RetencionController extends Controller
{
    /**
     * TipoDocumento (interno) que corresponde a "RETENCION ELECTRONICO" en
     * dbo.MaestroDocumento. La fila la inserta el usuario; aqui solo lo
     * reusamos para resolver series y siguiente correlativo.
     */
    private const TIPO_DOC_RETENCION = 50;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista del listado.
     */
    public function index()
    {
        $rucempresa = $this->getRucEmpresa();
        $tasa       = $this->getTasaRetencion();

        return view('retencion.index', [
            'rucempresa' => $rucempresa,
            'tasa'       => $tasa,
            'anioActual' => (int) date('Y'),
            'mesActual'  => (int) date('m'),
        ]);
    }

    /**
     * Listado parcial (HTML) para AJAX. Sigue el patron de guiasalida.listar.
     */
    public function listar(Request $request)
    {
        $rucempresa = $this->getRucEmpresa();

        $fechaInicio = $request->post('fecha_inicio');
        $fechaFin    = $request->post('fecha_fin');
        $serie       = $request->post('serie');
        $numero      = $request->post('numero');
        $rucProv     = $request->post('ruc_proveedor');
        $estadoSunat = $request->post('estado_sunat');
        $busqueda    = $request->post('busqueda');

        $q = DB::table('retenciones')->where('rucempresa', $rucempresa);

        if ($fechaInicio) $q->where('fechaemision', '>=', $fechaInicio);
        if ($fechaFin)    $q->where('fechaemision', '<=', $fechaFin);

        if ($serie) {
            $q->where('serienumero', 'like', "{$serie}-%");
        }
        if ($numero) {
            $q->where('serienumero', 'like', "%-%{$numero}");
        }
        if ($rucProv) {
            $q->where('numdocproveedor', $rucProv);
        }
        if ($estadoSunat !== null && $estadoSunat !== '' && $estadoSunat !== 'all') {
            if ($estadoSunat === 'null') {
                $q->where(function ($s) {
                    $s->whereNull('estadosunat')->orWhere('estadosunat', '');
                });
            } else {
                $q->where('estadosunat', $estadoSunat);
            }
        }
        if ($busqueda) {
            $q->where(function ($s) use ($busqueda) {
                $s->where('serienumero',          'like', "%{$busqueda}%")
                  ->orWhere('razonsocialproveedor','like', "%{$busqueda}%")
                  ->orWhere('numdocproveedor',     'like', "%{$busqueda}%");
            });
        }

        $list = $q->orderBy('fechaemision', 'desc')
                  ->orderBy('serienumero',  'desc')
                  ->get();

        // KPIs via SP
        $totales = DB::select('CALL SP_RETENCION_TOTALES_DASHBOARD(?, ?, ?)', [
            $rucempresa,
            $fechaInicio ?: '',
            $fechaFin    ?: '',
        ]);
        $totales = $totales[0] ?? null;

        return view('retencion.tabla', [
            'list'    => $list,
            'totales' => $totales,
        ]);
    }

    /**
     * Vista del formulario de creacion.
     */
    public function create()
    {
        $rucempresa  = $this->getRucEmpresa();
        $razonSocial = $this->getRazonSocialEmpresa();
        $serieParam  = $this->getSerieRetencion(); // ej. "R001" del Parametro 12

        // Series disponibles desde el FastAPI (SQL Server). El form ya no
        // permite elegir serie — solo mostramos la resuelta. Para cambiar de
        // serie el usuario va al modal "Series" del listado.
        $svc    = app(DatamarketRetencionService::class);
        $resSer = $svc->listarSeries(self::TIPO_DOC_RETENCION);
        $series = $resSer['ok'] ? $resSer['items'] : [];

        // Pickeamos la serie por defecto: la del Parametro 12 si existe en la
        // lista, si no la primera disponible. Si no hay ninguna, el form va a
        // mostrar un aviso para configurar.
        $serieDefault    = null;
        $numSerieDefault = 0;
        $match = collect($series)->firstWhere('serie_formateada', $serieParam);
        if ($match) {
            $serieDefault    = $match['serie_formateada'];
            $numSerieDefault = (int) $match['num_serie'];
        } elseif (!empty($series)) {
            $serieDefault    = $series[0]['serie_formateada'];
            $numSerieDefault = (int) $series[0]['num_serie'];
        }

        // Siguiente número (zero-padded a 8) — solo si hay serie.
        $numeroSugerido = null;
        if ($numSerieDefault > 0) {
            $sig = $svc->siguienteNumeroSerie(self::TIPO_DOC_RETENCION, $numSerieDefault);
            if ($sig['ok']) $numeroSugerido = $sig['siguiente_numero'];
        }

        return view('retencion.create', [
            'rucempresa'     => $rucempresa,
            'razonSocial'    => $razonSocial,
            'serieDefault'   => $serieDefault,
            'numeroSugerido' => $numeroSugerido,
            'fechaHoy'       => date('Y-m-d'),
        ]);
    }

    /**
     * Devuelve el siguiente correlativo de una serie (AJAX).
     * Acepta num_serie directo o serie formateada (ej. "R011") que se
     * descompone en TipoDocumento+NumSerie (TipoDocumento = 50).
     * Fuente: FastAPI /maestros/series/siguiente-numero (SQL Server).
     */
    public function siguienteNumero(Request $request)
    {
        $numSerie = $request->input('num_serie');
        if ($numSerie === null || $numSerie === '') {
            $serie    = (string) $request->input('serie', '');
            $numSerie = (int) preg_replace('/\D/', '', $serie); // "R011" -> 11
        } else {
            $numSerie = (int) $numSerie;
        }
        if ($numSerie <= 0) {
            return response()->json(['siguiente_numero' => '00000001'], 200);
        }

        $r = app(DatamarketRetencionService::class)
            ->siguienteNumeroSerie(self::TIPO_DOC_RETENCION, $numSerie);

        if (!$r['ok']) {
            return response()->json([
                'siguiente_numero' => '00000001',
                'error'            => $r['error'] ?? null,
            ], 200);
        }

        return response()->json([
            'siguiente_numero' => $r['siguiente_numero'],
            'serie_formateada' => $r['serie_formateada'] ?? null,
            'ultimo_valor'     => $r['ultimo_valor'] ?? 0,
        ]);
    }

    /**
     * Lista las series habilitadas para retencion (TipoDocumento = 50).
     * Usado por el dropdown del form y por la pagina de config.
     */
    public function listarSeries()
    {
        $r = app(DatamarketRetencionService::class)
            ->listarSeries(self::TIPO_DOC_RETENCION);

        return response()->json([
            'procede' => $r['ok'],
            'items'   => $r['items'],
            'error'   => $r['ok'] ? null : ($r['error'] ?? 'error'),
        ], $r['ok'] ? 200 : 502);
    }

    /**
     * Crea una serie nueva para retencion. Body: {num_serie: 1..9999}.
     */
    public function crearSerie(Request $request)
    {
        $request->validate([
            'num_serie' => 'required|integer|min:1|max:9999',
            'ctr_resp'  => 'nullable|string|max:10',
        ]);

        $r = app(DatamarketRetencionService::class)->crearSerie(
            self::TIPO_DOC_RETENCION,
            (int) $request->input('num_serie'),
            $request->input('ctr_resp'),
        );

        if (!$r['ok']) {
            $http = $r['http'] === 409 ? 409 : 502;
            return response()->json([
                'procede'  => false,
                'msj'      => $r['error'],
                'msj_tipo' => $http === 409 ? 'warning' : 'error',
            ], $http);
        }

        return response()->json([
            'procede' => true,
            'serie'   => $r['serie'],
            'msj'     => 'Serie '.$r['serie']['serie_formateada'].' creada.',
            'msj_tipo'=> 'success',
        ], 201);
    }

    /**
     * Persiste cabecera + N detalles invocando los SPs en una transaccion.
     */
    public function store(Request $request)
    {
        $request->validate([
            'serie'                    => 'required|string|max:4',
            'numero'                   => 'required|string|max:8',
            'fecha_emision'            => 'required|date',
            'numdocproveedor'          => 'required|string|size:11',
            'razonsocialproveedor'     => 'required|string|max:150',
            // tasa y régimen ya no se editan en el form (siempre 3 % / "01"),
            // pero los aceptamos opcionales por compatibilidad si algún caller
            // viejo los envía.
            'tasa'                     => 'nullable|numeric|min:0|max:99',
            'regimenretencion'         => 'nullable|string|max:2',
            'detalles'                 => 'required|array|min:1',
            'detalles.*.tipo_doc_rel'  => 'required|string|in:01,03,07,08,12,14,99',
            'detalles.*.serie_num_rel' => 'required|string|max:15',
            'detalles.*.fecha_doc_rel' => 'nullable|date',
            'detalles.*.importe_doc'   => 'nullable|numeric',
            'detalles.*.moneda_doc'    => 'nullable|string|max:3',
            'detalles.*.fecha_pago'    => 'required|date',
            'detalles.*.importe_pago'  => 'required|numeric|min:0.01',
            'detalles.*.moneda_pago'   => 'nullable|string|in:PEN,USD',
            // factor_cambio obligatorio si la linea esta en USD; default 1.0 para PEN.
            'detalles.*.factor_cambio' => 'nullable|numeric|min:0.01|required_if:detalles.*.moneda_pago,USD',
        ]);

        $rucempresa  = $this->getRucEmpresa();
        $razonEmp    = $this->getRazonSocialEmpresa();
        $direccionEmp= $this->getDireccionEmpresa();
        $usuario     = (string) (Auth::user()->username ?? Auth::user()->name ?? 'sistema');

        $serie       = strtoupper(substr($request->input('serie'), 0, 4));
        $numero      = str_pad(preg_replace('/\D/', '', $request->input('numero')), 8, '0', STR_PAD_LEFT);
        $serienumero = "{$serie}-{$numero}";

        // Tasa fija 3 % (régimen general SUNAT). Si llega del request, se respeta.
        $tasaInput   = $request->input('tasa');
        $tasaFloat   = ($tasaInput !== null && $tasaInput !== '') ? (float) $tasaInput : 3.00;
        $tasa        = number_format($tasaFloat, 2, '.', '');
        $tasaDec     = $tasaFloat / 100;

        // ── Calculos en PEN (cabecera siempre en PEN aunque las lineas sean USD) ──
        $detallesIn       = $request->input('detalles');
        $totalPagadoPEN   = 0.0;  // suma de los netos a pagar
        $totalRetenidoPEN = 0.0;  // suma de los importes retenidos
        $detallesNorm     = [];   // detalles normalizados con valores en PEN

        foreach ($detallesIn as $idx => $d) {
            $monedaPago     = strtoupper($d['moneda_pago'] ?? 'PEN');
            $factor         = $monedaPago === 'USD'
                                ? (float) ($d['factor_cambio'] ?? 1.0)
                                : 1.0;
            $importeOriginal= (float) $d['importe_pago'];
            $importePEN     = round($importeOriginal * $factor, 2);
            $retenidoPEN    = round($importePEN * $tasaDec, 2);
            $netoPEN        = round($importePEN - $retenidoPEN, 2);

            $detallesNorm[$idx] = [
                'tipo_doc_rel'         => $d['tipo_doc_rel'],
                'serie_num_rel'        => $d['serie_num_rel'],
                'fecha_doc_rel'        => $d['fecha_doc_rel'] ?? null,
                'importe_doc'          => isset($d['importe_doc']) ? (float) $d['importe_doc'] : $importeOriginal,
                'fecha_pago'           => $d['fecha_pago'],
                'numero_pago'          => $idx + 1,
                'importe_pago'         => $importeOriginal,
                'moneda'               => $monedaPago,
                'factor_cambio'        => $factor,
                'importe_retenido_pen' => $retenidoPEN,
                'monto_neto_pen'       => $netoPEN,
            ];

            $totalPagadoPEN   += $netoPEN;
            $totalRetenidoPEN += $retenidoPEN;
        }

        // ── 1. INSERT en MySQL (cabecera + detalles) en transaccion ──
        try {
            DB::beginTransaction();

            DB::statement('CALL SP_RETENCION_INSERT(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $rucempresa,
                $serienumero,
                '2.0',
                '1.0',
                $request->input('fecha_emision'),
                $request->input('numdocproveedor'),
                $request->input('tipodocproveedor', '06'),
                $request->input('direccionproveedor'),
                strtoupper($request->input('razonsocialproveedor')),
                $request->input('regimenretencion', '01'),
                $tasa,
                $request->input('observacion'),
                number_format($totalRetenidoPEN, 2, '.', ''),
                'PEN',
                number_format($totalPagadoPEN,   2, '.', ''),
                'PEN',
                $usuario,
            ]);

            foreach ($detallesNorm as $d) {
                DB::statement('CALL SP_DETALLERETENCION_INSERT(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $rucempresa,
                    $serienumero,
                    $d['tipo_doc_rel'],
                    $d['serie_num_rel'],
                    $d['fecha_doc_rel'],
                    number_format($d['importe_doc'], 2, '.', ''),
                    $d['moneda'],
                    $d['fecha_pago'],
                    str_pad((string) $d['numero_pago'], 3, '0', STR_PAD_LEFT),
                    number_format($d['importe_pago'], 2, '.', ''),
                    $d['moneda'],
                    number_format($d['importe_retenido_pen'], 2, '.', ''),
                    'PEN',
                    $request->input('fecha_emision'),
                    number_format($d['monto_neto_pen'], 2, '.', ''),
                    'PEN',
                    $usuario,
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Retencion store error: '.$e->getMessage());
            return response()->json([
                'procede'  => false,
                'msj'      => 'No se pudo registrar la retencion: '.$e->getMessage(),
                'msj_tipo' => 'error',
            ], 422);
        }

        // ── 2. Replicar al datamarket (SQL Server) ──
        $datamarketPayload = $this->buildDatamarketPayloadV2(
            $rucempresa, $serienumero, $request, $usuario,
            $tasa, $totalRetenidoPEN, $totalPagadoPEN, $detallesNorm
        );
        $rep = app(DatamarketRetencionService::class)->crear($datamarketPayload);
        $this->markReplicacion($rucempresa, $serienumero, $rep);

        // ── 3. Enviar a DB Peru / SUNAT (sync) ──
        $sunatPayload = app(RetencionPayloadMapper::class)->buildSunatPayload([
            'rucempresa'           => $rucempresa,
            'serienumero'          => $serienumero,
            'fechaemision'         => $request->input('fecha_emision'),
            'numdocproveedor'      => $request->input('numdocproveedor'),
            'tipodocproveedor'     => $request->input('tipodocproveedor', '06'),
            'razonsocialproveedor' => strtoupper($request->input('razonsocialproveedor')),
            'direccionproveedor'   => $request->input('direccionproveedor'),
            'razonsocialempresa'   => $razonEmp,
            'direccionempresa'     => $direccionEmp,
            'tasaretencion'        => $tasa,
            'observacion'          => $request->input('observacion'),
            'totalRetenidoPEN'     => $totalRetenidoPEN,
            'totalPagadoPEN'       => $totalPagadoPEN,
            'detalles'             => $detallesNorm,
        ]);

        $envio = app(DbPeruSunatService::class)->enviar($sunatPayload);

        // ── 4. Persistir resultado del envio en MySQL y replicar al datamarket ──
        $estadoProceso   = $envio['ok'] ? 'C' : 'F';
        $estadoSunat     = $envio['ok'] ? 'A' : null;
        $estadoDocumento = $envio['ok'] ? '1' : null;

        $this->actualizarEnvioLocal($rucempresa, $serienumero, [
            'estadosunat'    => $estadoSunat,
            'estadoproceso'  => $estadoProceso,
            'estadodocumento'=> $estadoDocumento,
            'codigohash'     => $envio['codigohash'],
            'codigoqr'       => $envio['codigoqr'],
            'pdf417'         => $envio['pdf417'],
            'mensaje_error'  => $envio['mensaje_error'],
            'respuesta_envio'=> $envio['respuesta_raw'],
            'usuario'        => $usuario,
        ]);

        // Replicacion del resultado al datamarket (best-effort)
        $repEnvio = app(DatamarketRetencionService::class)->actualizarEnvioSunat($rucempresa, $serienumero, [
            'estadosunat'        => $estadoSunat,
            'estadoproceso'      => $estadoProceso,
            'estadodocumento'    => $estadoDocumento,
            'codigohash'         => $envio['codigohash'],
            'codigoqr'           => $envio['codigoqr'],
            'pdf417'             => $envio['pdf417'],
            'mensaje_error'      => $envio['mensaje_error'],
            'respuesta_envio'    => $envio['respuesta_raw'],
            'usuariomodificador' => $usuario,
        ]);

        // ── Respuesta ──
        if ($envio['ok']) {
            $msj = "Retencion <b>{$serienumero}</b> aceptada por SUNAT.";
            $tipo = 'success';
        } else {
            $msj = "Retencion <b>{$serienumero}</b> registrada localmente, pero <b>fallo el envio a SUNAT</b>.";
            if (!empty($envio['mensaje_error'])) {
                $msj .= '<br><small>'.e($envio['mensaje_error']).'</small>';
            }
            $tipo = 'warning';
        }
        if (!$rep['ok']) {
            $msj .= '<br><small class="text-muted">No se replico al datamarket. Reintento posterior.</small>';
        } elseif (!$repEnvio['ok']) {
            $msj .= '<br><small class="text-muted">El estado de envio no se replico al datamarket.</small>';
        }

        return response()->json([
            'procede'              => true,
            'msj'                  => $msj,
            'msj_tipo'             => $tipo,
            'serienumero'          => $serienumero,
            'rucempresa'           => $rucempresa,
            'sunat'                => [
                'aceptado'      => $envio['ok'],
                'codigohash'    => $envio['codigohash'],
                'codigoqr'      => $envio['codigoqr'],
                'mensaje_error' => $envio['mensaje_error'],
            ],
            'replicado_datamarket' => $rep['ok'],
        ]);
    }

    /**
     * Arma el payload para POST /retenciones del FastAPI (datamarket).
     * Trabaja con detalles ya normalizados (moneda original + valores en PEN).
     */
    private function buildDatamarketPayloadV2(
        string $rucempresa,
        string $serienumero,
        Request $request,
        string $usuario,
        string $tasa,
        float $totalRetenidoPEN,
        float $totalPagadoPEN,
        array $detallesNorm
    ): array {
        $detalles = [];
        foreach ($detallesNorm as $d) {
            $detalles[] = [
                'tipodocrelacionado'         => $d['tipo_doc_rel'],
                'serienumerorelacionado'     => $d['serie_num_rel'],
                'fechaemisiondocrelacionado' => $d['fecha_doc_rel'] ?? $d['fecha_pago'],
                'importetotaldocrela'        => number_format($d['importe_doc'], 2, '.', ''),
                'monedaimportedocrela'       => $d['moneda'],
                'fechapago'                  => $d['fecha_pago'],
                'numeropago'                 => str_pad((string) $d['numero_pago'], 3, '0', STR_PAD_LEFT),
                'importepagosinretencion'    => number_format($d['importe_pago'], 2, '.', ''),
                'monedapago'                 => $d['moneda'],
                'importeretenido'            => number_format($d['importe_retenido_pen'], 2, '.', ''),
                'monedaimporteretenido'      => 'PEN',
                'fecharetencion'             => $request->input('fecha_emision'),
                'montonetopagar'             => number_format($d['monto_neto_pen'], 2, '.', ''),
                'monedamontonetopagar'       => 'PEN',
            ];
        }

        return [
            'rucempresa'                 => $rucempresa,
            'serienumero'                => $serienumero,
            'versionubl'                 => '2.0',
            'versionestructura'          => '1.0',
            'fechaemision'               => $request->input('fecha_emision'),
            'numdocproveedor'            => $request->input('numdocproveedor'),
            'tipodocproveedor'           => $request->input('tipodocproveedor', '06'),
            'direccionproveedor'         => $request->input('direccionproveedor'),
            'razonsocialproveedor'       => strtoupper($request->input('razonsocialproveedor')),
            'regimenretencion'           => $request->input('regimenretencion', '01'),
            'tasaretencion'              => $tasa,
            'observacion'                => $request->input('observacion'),
            'importetotalretenido'       => number_format($totalRetenidoPEN, 2, '.', ''),
            'monedaimportetotalretenido' => 'PEN',
            'importetotalpagado'         => number_format($totalPagadoPEN, 2, '.', ''),
            'monedaimportetotalpagado'   => 'PEN',
            'usuariocreador'             => $usuario,
            'detalles'                   => $detalles,
        ];
    }

    /**
     * Persiste el resultado del envio (CodigoHash, QR, pdf417, estados) en MySQL.
     */
    private function actualizarEnvioLocal(string $rucempresa, string $serienumero, array $envio): void
    {
        try {
            DB::statement('CALL SP_RETENCION_ACTUALIZAR_ENVIO_SUNAT(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $rucempresa,
                $serienumero,
                $envio['estadosunat'],
                $envio['estadoproceso'],
                $envio['estadodocumento'],
                $envio['codigohash'],
                $envio['codigoqr'],
                $envio['pdf417'],
                $envio['mensaje_error'],
                $envio['respuesta_envio'],
                $envio['usuario'],
            ]);
        } catch (Exception $e) {
            Log::error('No se pudo actualizar envio SUNAT en MySQL: '.$e->getMessage());
        }
    }

    /**
     * Persiste el resultado de la replicacion en la fila local.
     */
    private function markReplicacion(string $rucempresa, string $serienumero, array $rep): void
    {
        try {
            DB::table('retenciones')
                ->where('rucempresa',  $rucempresa)
                ->where('serienumero', $serienumero)
                ->update([
                    'replicado_datamarket' => $rep['ok'] ? 1 : 0,
                    'fecha_replicacion'    => $rep['ok'] ? now() : null,
                    'error_replicacion'    => $rep['ok'] ? null : $rep['error'],
                ]);
        } catch (Exception $e) {
            Log::warning('No se pudo marcar replicacion en retenciones: '.$e->getMessage());
        }
    }

    /**
     * Detalle (cabecera + lineas) para el modal de "Ver".
     */
    public function show(Request $request)
    {
        $request->validate([
            'serienumero' => 'required|string|max:15',
        ]);

        $rucempresa  = $this->getRucEmpresa();
        $serienumero = $request->post('serienumero');

        $cab = DB::select('CALL SP_RETENCION_OBTENER(?, ?)',          [$rucempresa, $serienumero]);
        $det = DB::select('CALL SP_RETENCION_OBTENER_DETALLES(?, ?)', [$rucempresa, $serienumero]);

        if (empty($cab)) {
            return response()->json([
                'procede'  => false,
                'msj'      => 'Retencion no encontrada.',
                'msj_tipo' => 'error',
            ], 404);
        }

        return response()->json([
            'procede'  => true,
            'cabecera' => $cab[0],
            'detalles' => $det,
        ]);
    }

    /**
     * Anula la retencion (baja logica via SP_RETENCION_ANULAR).
     */
    public function anular(Request $request)
    {
        $request->validate([
            'serienumero' => 'required|string|max:15',
            'motivo'      => 'nullable|string|max:250',
        ]);

        $rucempresa  = $this->getRucEmpresa();
        $serienumero = $request->post('serienumero');
        $motivo      = $request->post('motivo', 'Anulacion solicitada por el usuario');
        $usuario     = (string) (Auth::user()->username ?? Auth::user()->name ?? 'sistema');

        try {
            DB::statement('CALL SP_RETENCION_ANULAR(?, ?, ?, ?)', [
                $rucempresa, $serienumero, $motivo, $usuario,
            ]);
        } catch (Exception $e) {
            Log::error('Retencion anular error: '.$e->getMessage());
            return response()->json([
                'procede'  => false,
                'msj'      => 'No se pudo anular: '.$e->getMessage(),
                'msj_tipo' => 'error',
            ], 422);
        }

        // ── Replicacion best-effort de la anulacion al datamarket ──
        $rep = app(DatamarketRetencionService::class)->anular(
            $rucempresa, $serienumero, $motivo, $usuario
        );

        $msj = "Retencion <b>{$serienumero}</b> anulada correctamente.";
        if (!$rep['ok']) {
            $msj .= '<br><small class="text-warning">Aviso: la anulacion no se replico al datamarket.</small>';
            // Marcamos error_replicacion para visibilidad operativa.
            try {
                DB::table('retenciones')
                    ->where('rucempresa',  $rucempresa)
                    ->where('serienumero', $serienumero)
                    ->update(['error_replicacion' => 'anular: '.$rep['error']]);
            } catch (Exception $e) {
                Log::warning('No se pudo marcar error_replicacion (anular): '.$e->getMessage());
            }
        }

        return response()->json([
            'procede'              => true,
            'msj'                  => $msj,
            'msj_tipo'             => 'success',
            'replicado_datamarket' => $rep['ok'],
        ]);
    }

    /**
     * PDF del comprobante de retencion (A4 vertical).
     */
    public function pdf($serienumero)
    {
        $rucempresa  = $this->getRucEmpresa();
        $razonSocial = $this->getRazonSocialEmpresa();
        $direccion   = $this->getDireccionEmpresa();

        $cab = DB::select('CALL SP_RETENCION_OBTENER(?, ?)',          [$rucempresa, $serienumero]);
        $det = DB::select('CALL SP_RETENCION_OBTENER_DETALLES(?, ?)', [$rucempresa, $serienumero]);

        if (empty($cab)) {
            abort(404, 'Retencion no encontrada');
        }

        $cabecera = $cab[0];

        // Importe en letras
        $formatter   = new NumeroALetras();
        $monedaTxt   = $cabecera->monedaimportetotalretenido === 'USD' ? 'DOLARES AMERICANOS' : 'SOLES';
        $importeNum  = (float) $cabecera->importetotalretenido;
        $importeLetras = strtoupper($formatter->toInvoice($importeNum, 2, $monedaTxt));

        $pdf = Pdf::loadView('retencion.pdf', [
            'empresa' => (object) [
                'ruc'         => $rucempresa,
                'razonSocial' => $razonSocial,
                'direccion'   => $direccion,
            ],
            'cabecera'      => $cabecera,
            'detalles'      => $det,
            'importeLetras' => $importeLetras,
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("Retencion-{$serienumero}.pdf");
    }

    /**
     * Lista proveedores via FastAPI (datamarket). Devuelve un shape
     * compatible con el Select2 ya cableado del form
     * (items[].id, .text, .proveedor_ruc, .proveedor_nombre, .proveedor_direccion).
     *
     * GET /retenciones/listarProveedores?term=&limit=
     */
    public function listarProveedores(Request $request)
    {
        $request->validate([
            'term'  => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $term  = trim((string) $request->query('term', ''));
        $limit = (int) $request->query('limit', 25);

        // El form ya filtra: si no hay 2+ chars devolvemos vacio (mismo
        // contrato que el endpoint anterior basado en ApiDMK).
        if (strlen($term) < 2) {
            return response()->json(['items' => []]);
        }

        $rep = app(DatamarketRetencionService::class)->listarProveedores($term, $limit);

        if (!$rep['ok']) {
            return response()->json([
                'items' => [],
                'error' => 'datamarket: '.$rep['error'],
            ], 502);
        }

        $items = [];
        foreach ($rep['items'] as $p) {
            $ruc    = $p['ruc']           ?? '';
            $nombre = $p['razon_social']  ?? '';
            $items[] = [
                'id'                  => $p['cod_proveedor'] ?? $ruc,
                'text'                => "[{$ruc}] {$nombre}",
                'proveedor_ruc'       => $ruc,
                'proveedor_nombre'    => $nombre,
                'proveedor_direccion' => $p['direccion'] ?? '',
            ];
        }

        return response()->json(['items' => $items]);
    }

    /**
     * Lista las facturas / NC / ND de un proveedor para el selector del form.
     * Delega al FastAPI (datamarket); la respuesta incluye flag ya_retenida
     * por cada fila (cruce contra dbo.detalleretenciones del datamarket).
     *
     * GET /retenciones/facturasProveedor?ruc=20XXXXXXXXX&q=&solo_disponibles=0
     */
    public function facturasProveedor(Request $request)
    {
        $request->validate([
            'ruc'              => 'required|string|size:11|regex:/^\d{11}$/',
            'q'                => 'nullable|string|max:50',
            'solo_disponibles' => 'nullable|boolean',
            'limit'            => 'nullable|integer|min:1|max:200',
            'offset'           => 'nullable|integer|min:0',
        ]);

        $rucempresa = $this->getRucEmpresa();
        if (strlen($rucempresa) !== 11) {
            return response()->json([
                'procede'  => false,
                'msj'      => 'RUC de empresa no esta configurado (parametro 2).',
                'msj_tipo' => 'error',
                'items'    => [],
                'total'    => 0,
            ], 422);
        }

        $rep = app(DatamarketRetencionService::class)->listarFacturasProveedor(
            (string) $request->query('ruc'),
            $rucempresa,
            $request->query('q'),
            $request->boolean('solo_disponibles'),
            (int) $request->query('limit', 50),
            (int) $request->query('offset', 0),
        );

        if (!$rep['ok']) {
            return response()->json([
                'procede'  => false,
                'msj'      => 'No se pudo consultar el datamarket: '.$rep['error'],
                'msj_tipo' => 'warning',
                'items'    => [],
                'total'    => 0,
            ], 502);
        }

        return response()->json([
            'procede' => true,
            'items'   => $rep['items'],
            'total'   => $rep['total'],
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function getRucEmpresa(): string
    {
        return (string) optional(Parametro::find(2))->valor;
    }

    private function getRazonSocialEmpresa(): string
    {
        return (string) (optional(Parametro::find(3))->valor ?? config('app.name', 'EMPRESA'));
    }

    private function getDireccionEmpresa(): string
    {
        return (string) (optional(Parametro::find(4))->valor ?? '');
    }

    private function getTasaRetencion(): string
    {
        $valor = optional(Parametro::find(11))->valor;
        return $valor !== null && $valor !== '' ? (string) $valor : '3.00';
    }

    private function getSerieRetencion(): string
    {
        $valor = optional(Parametro::find(12))->valor;
        return $valor !== null && $valor !== '' ? strtoupper(substr((string) $valor, 0, 4)) : 'R001';
    }
}
