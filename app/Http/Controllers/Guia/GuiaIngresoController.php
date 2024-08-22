<?php

namespace App\Http\Controllers\Guia;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\GuiaEstado;
use App\Models\GuiaIngreso;
use App\Models\GuiaIngresoDetalle;
use App\Models\Parametro;
use App\Models\Serie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Luecano\NumeroALetras\NumeroALetras;
use Illuminate\Support\Str;

class GuiaIngresoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('guia.ingreso.index');
    }
    public function listar(Request $request)
    {
        $fechaInicio = $request->post('fecha_inicio');
        $fechaFin = $request->post('fecha_fin');
        $serie = $request->post('serie');
        $numero = $request->post('numero');

        $consulta = DB::table('guia_ingresos')->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])->where('activo', 1);
        
        if ($serie != '') {
            $consulta = $consulta->where('serie', $serie);
        }
        if ($numero != '') {
            $consulta = $consulta->where('numero', $numero);
        }
        
        $list = $consulta->get();
        // dd($list);

        foreach ($list as $key => $value) {
            $list[$key]->estado_nombre = GuiaEstado::find($value->guia_estado_id)->nombre;
            $mostrar_eliminar = false;
            if ($value->guia_estado_id == 1) {
                $mostrar_eliminar = true;
                
            }
            
            $list[$key]->mostrar_eliminar = $mostrar_eliminar;
            
            $mostrarGuardarDatamarket = true;
            if ($value->enviado_datamarket == 1) {
                $mostrarGuardarDatamarket = false;
            }
            
            $list[$key]->mostrarGuardarDatamarket = $mostrarGuardarDatamarket;

        }
        // dd($list);
        return view('guia.ingreso.tabla', compact('list'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $api_datos = Parametro::find(6)->valor;
        // dd($api_datos);

        $listProveedores = [];
        // $listFormasPago = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerFormasPago')->object()->formasdePago;
        $listFormasPago = Http::get("{$api_datos}/ObtenerFormasPago")->object()->formasdePago;
        $listTipoOperacion = Http::get("{$api_datos}/ObtenerOperacion")->object()->operaciones;
        $listAlmacenes = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        // dd($listAlmacenes);
        // $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();
        $listArticulos = [];
        // dd($listArticulos);
        // $listVendedores = Http::get("{$api_datos}/ObtenerTrabajador?CodigoTrabajador=-1")->object()->trabajador;
        $listVendedores = [];

        // foreach ($listVendedores as $key => $value) {
        //     $listVendedores[$key]->selected = '';
        // }
        // $getVendedor = $listVendedores[0];

        $listSeries = Http::get("{$api_datos}/obtenerSeriesNumerosGuia")->object()->serienumeros;

        return view('guia.ingreso.create', compact('listProveedores', 'listFormasPago', 'listTipoOperacion', 'listAlmacenes', 'listArticulos', 'listVendedores', 'listSeries'));
    }

    public function getSerie(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;
        $serie = $request->post('serie');
        // dd($request->post());
        $listSeries = Http::get("{$api_datos}/obtenerSeriesNumerosGuia")->object()->serienumeros;

        foreach ($listSeries as $item) {
            if ($serie == $item->numserie) {
                $getSerie = $item;
            }
        }

        
        // dd($getSerie);
        $serieLocal = Serie::where('serie', $getSerie->numserie)->first();
        // dd($serieLocal);
        
        if ($serieLocal == null) {
            $getSerie->nuevo_numero = str_pad(($getSerie->ultimoValormarket + 1), 4, "0", STR_PAD_LEFT);
        }
        
        if ($serieLocal != null) {
            $getSerie->nuevo_numero = str_pad(($serieLocal->numero + 1), 4, "0", STR_PAD_LEFT);
            
        }

        return response()->json(['getSerie' => $getSerie]);
    }

    public function continuar(GuiaIngreso $guia)
    {
        $listProveedores = [];
        $api_datos = Parametro::find(6)->valor;
        // dd($guia);
        // dd($listProveedores);
        if ($guia->proveedor_id != null) {
            $listProveedores = Http::post("{$api_datos}/ObtenerProveedores", ['valor' => $guia->proveedor_id, 'tipo' => 1])->object()->proveedores;
        }

        $listFormasPago = Http::get("{$api_datos}/ObtenerFormasPago")->object()->formasdePago;
        $listTipoOperacion = Http::get("{$api_datos}/ObtenerOperacion")->object()->operaciones;
        // dd($listTipoOperacion);
        foreach ($listTipoOperacion as $key => $value) {
            $selected = "";
            if ($guia->tipo_operacion_id == $value->tipoOperacion) {
                $selected = "selected";
            }
            $listTipoOperacion[$key]->selected = $selected;
        }
        $listAlmacenes = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        // dd($listAlmacenes);
        foreach ($listAlmacenes as $key => $value) {
            $selected = "";
            if ($guia->codalmacen == $value->codAlmacen) {
                $selected = "selected";
            }
            $listAlmacenes[$key]->selected = $selected;
        }
        // dd($listAlmacenes);
        // $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();
        $listArticulos = [];
        // dd($listArticulos);
        $listVendedores = Http::get("{$api_datos}/ObtenerTrabajador?CodigoTrabajador={$guia->vendedor_id}")->object()->trabajador;
        // dd($listVendedores);

        foreach ($listVendedores as $key => $value) {
            $selected = "";
            if ($value->codTrabajador == $guia->vendedor_id) {
                $selected = "selected";
            }

            $listVendedores[$key]->selected = $selected;
        }

        $detalle = GuiaIngresoDetalle::where('guia_ingreso_id', $guia->id)->get();
        // dd($detalle);
        
        return view('guia.ingreso.create', compact('guia','listProveedores', 'listFormasPago', 'listTipoOperacion', 'listAlmacenes', 'listArticulos', 'listVendedores', 'detalle'));
    }

    public function getVendedor(Request $request)
    {
        // dd($request->post());
        $api_datos = Parametro::find(6)->valor;

        $vendedor_codigo = $request->post('vendedor_codigo');

        $getVendedor = Http::get("{$api_datos}/ObtenerTrabajador?CodigoTrabajador={$vendedor_codigo}")->object()->trabajador;

        // $getVendedor = $getVendedor[0];
        // dd($getVendedor);
        $options = "";
        foreach ($getVendedor as $item) {
            $options .= "<option value='{$item->codTrabajador}'
            
                data-vendedor_nombre = '{$item->apellidos} {$item->nombres}'
            >{$item->apellidos} {$item->nombres}</option>";
        }

        return response()->json(['options' => $options]);
    }

    public function agregarItem(Request $request)
    {
        $producto_id = $request->post('producto_id');
        $codigo_barra = $request->post('codigo_barra');
        $cod_plu = $request->post('cod_plu');
        $descripcion = $request->post('descripcion');
        $precio_publico = $request->post('precio_publico');
        $precio_sin_igv = $request->post('precio_sin_igv');
        $peso = $request->post('peso') ?? 0;
        $cod_unidad = $request->post('cod_unidad') ?? 9;
        $desc_unidad_medida = $request->post('desc_unidad_medida') ?? '';
        $sigla_umfe = $request->post('sigla_umfe') ?? '';
        // $cantidad = $request->post('cantidad');
        $cantidad = 1;
        $base_clalculo = $request->post('base_calculo');

        $items = json_decode($request->post('items'));

        $procede = true;
        $msj = "Datos obtenidos";
        $msj_tipo = "success";
        $log = "";
        $tr = "";

        if (count($items) > 0) {
            foreach ($items as $item) {
                if ($procede == true) {
                    if ($item->producto_id == $producto_id) {
                        $procede = false;
                        $msj = "<b>No puede repetir el producto</b>";
                        $msj_tipo = "error";
                    }
                }
            }
        }

        if ($procede == true) {

            $unidad = "UNI";
            $inputCantidad = "<input type='number' class='form-control form-control-sm input_cantidad_tr' name='cantidad' value='{$cantidad}'></input>";
            $inputPorcentajeDescuento = "<input class='form-control form-control-sm input_porcentaje_descuento_tr' name='porcentaje_descuento' value='0'></input>";
            $inputDescuento = "<input type='hidden' name='monto_descuento' value='0'></input>";
            $importe = $cantidad * $precio_publico;
            $span_precio = $precio_publico;

            if ($base_clalculo == 1) {
                $span_precio = $precio_sin_igv;
                $importe = $cantidad * $precio_sin_igv;
            }


            $tr = "
                <tr
                    data-producto_id = '{$producto_id}'
                    data-precio_unitario = {$precio_publico}
                    data-precio_publico = {$precio_publico}
                    data-precio_sin_igv='{$precio_sin_igv}'
                    data-descripcion = '{$descripcion}'
                    data-codigo = '{$cod_plu}'
                    data-peso = '{$peso}'
                    data-codigo_barra='{$codigo_barra}'
                    data-cod_unidad = '{$cod_unidad}'
                    data-desc_unidad_medida = '{$desc_unidad_medida}'
                    data-sigla_umfe = '{$sigla_umfe}'
                >
                    <td class='align-middle'>{$codigo_barra}</td>
                    <td class='align-middle'>{$producto_id}</td>
                    <td class='align-middle'>{$cod_plu}</td>
                    <td class='align-middle'>{$descripcion}</td>
                    <td class='align-middle'><span name='span_precio'>{$span_precio}</span></td>
                    <td class='align-middle'>{$inputCantidad}</td>
                    <td class='align-middle'>{$unidad}</td>
                    <td class='align-middle'><span name='span_importe'>{$importe}</span></td>
                    <td class='align-middle'>{$inputPorcentajeDescuento} {$inputDescuento}</td>
                    <td class='align-middle' style='text-align:center'>
                        <input class='bonificacion' type='checkbox' name='bonificacion' >
                    </td>
                    <td class='align-middle text-center'>
                        <button class='btn btn-danger btn-sm delete_item'><i class='fa fa-times-circle'></i></button>
                    </td>
                </tr>
            ";
        }

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'tr' => $tr]);
    }

    public function listarProveedores(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;
        $valor = trim($request->get('term'));
        $tipo = $request->get('tipo');//busqueda por razon social
        // dd($request->all());
        $maximo = 0;
        if ($tipo == 3) {
            $maximo = 2;
        }
        if (strlen($valor) > $maximo) {
            $listItems = Http::post("{$api_datos}/ObtenerProveedores", 
                ['valor' => $valor, 'tipo' => $tipo]
            )->object()->proveedores;
        }

        // dd($listItems);
        $items = array();
        foreach ($listItems as $item) {

            $items[] = (object) array('id' => $item->codProveedor, 'text' => "[{$item->ruc}] {$item->nombreproveedor}", 'proveedor_nombre' => $item->nombreproveedor, 'proveedor_ruc' => $item->ruc);
        }

        return response()->json(['items' => $items]);
    }

    public function formBusquedaArticulo(Request $request)
    {
        $tipoBusqueda = $request->post('tipo_busqueda_articulo');
        $callSelect = true;
        if ($tipoBusqueda == 1) {
            $callSelect = false;
            $form = " <input class='form-control' id='producto_valor' name='producto_valor' placeholder='Escanea un producto' autocomplete='off' autofocus>
            ";
        }else{
            $form =  " <select class='form-select select_2' name='producto_select' id='producto_select' style='width: 100%' data-placeholder='Indicar un Articulo'></select>";
        }

        return response()->json(['form' => $form, 'callSelect' => $callSelect]);
    }

    public function listarArticulos(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $valor = trim($request->get('term'));
        $tipoconsulta = $request->post('tipo');
        $codestacion = $request->get('codestacion');
        $codalmacen = $request->get('codalmacen');
        $codlistaprecio = $request->get('codlistaprecio');
        $maximo = 0;
        if ($tipoconsulta == 4) {
            $maximo = 2;
        }
        // dd($request->all());
        if (strlen($valor) > $maximo) {
            $listArticulos = Http::post("{$api_datos}/ObtenerArticulo", 
                ['valor' => $valor, 'tipoconsulta' => $tipoconsulta, 'codestacion' => $codestacion, 'codalmacen' => $codalmacen, 'codlistaprecio' => $codlistaprecio]
            )->object()->articulos;
            
        }

        // dd($listArticulos);
        $items = array();
        foreach ($listArticulos as $item) {
            $items[] = (object) array('id' => $item->codArticulo, 'text' => "[{$item->codBarra}] {$item->nombreArticulo}", 'codigo_barra' => $item->codBarra, 'descripcion' => $item->nombreArticulo, 'precio_publico' => $item->precioPublico, 'precio_sin_igv' => $item->precioSinIGV, 'peso' => $item->peso ?? 0, 'cod_unidad' => $item->codUnidad, 'desc_unidad_medida' => $item->descUnidadMedida ?? '', 'sigla_umfe' => $item->siglaUMFE ?? '' );
        }

        return response()->json(['items' => $items]);
    }

    public function buscarArticuloBarra(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $valor = trim($request->get('producto_valor'));
        $tipoconsulta = $request->post('tipo');
        $codestacion = $request->get('codestacion');
        $codalmacen = $request->get('codalmacen');
        $codlistaprecio = $request->get('codlistaprecio');

        $getArticulo = Http::post("{$api_datos}/ObtenerArticulo", 
        ['valor' => $valor, 'tipoconsulta' => 1, 'codestacion' => $codestacion, 'codalmacen' => $codalmacen, 'codlistaprecio' => $codlistaprecio])->object()->articulos;

        // $getArticulo = $listArticulos;
        $getArticulo = $getArticulo[0];
        // dd($getArticulo);

        return response()->json(['getArticulo' => $getArticulo]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store2(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $datos = $request->post();
        $id_continuar = $request->post('id_continuar');
        // dd($request->post());
        if ($id_continuar == '') {
            unset($datos['id']);
        }
        // dd($datos);
        $detalle = json_decode($request->post('detalle'));
        $guardar_avance = ($datos['guardar_avance'] == 'true') ? true : false ;
        unset($datos['detalle']);
        // dd($datos);
        $procede = true;
        $msj = "Guia de Ingreso registrada";
        $msj_tipo = "success";
        $log = "";
        $datos['fecha_emision'] = date('Y-m-d');
        $datos['hora_emision'] = date('H:i');
        $url_redirect = route('guiaingreso.index');
        $datos['serie']= 1;
        $guia_estado_id = 1;
        if ($guardar_avance == true) {
            $guia_estado_id = 4;
        }
        $datos['guia_estado_id'] = $guia_estado_id;
        
        // $getLast = GuiaSalida::orderBy('id', 'desc')->first();
        $listSeries = Http::get("{$api_datos}/obtenerSeriesNumerosGuia")->object()->serienumeros;
        // dd($listSeries);
        foreach ($listSeries as $item) {
            if ($item->numserie == $datos['serie']) {
                $numero = intval($item->ultimoValormarket) + 1;
                $serie = $item->numserie;
            }
        }

        // if ($getLast != null) {
        //     $numero = intval($getLast->numero)+1;
        // }
        $datos['numero'] = $numero;
        $datos['serie'] = $serie;
        // dd([$numero, $serie]);
        $anio_actual = date('Y');


        // dd($body);

        if ($id_continuar != null) {
            // dd('desactivamos el activo anterior');
            $guia_avance = GuiaIngreso::find($id_continuar);
            $guia_avance->activo = 0;
            try {
                $guia_avance->save();
            } catch (Exception $e) {
                //throw $th;
                $procede = false;
                $msj = "No se pudo limpiar la guia guardada";
                $msj_tipo = "error";
                $log = "{$e}";
            }
        }

        if ($procede == true) {
            
            if ($guardar_avance == false) {

                foreach ($detalle as $item) {
                    $body_detalle[] = array(
                        "anioGuia" => $anio_actual,
                        "cantidad" => $item->cantidad,
                        "codArticulo" => $item->codarticulo,
                        "estadoProceso" => "0",
                        "importeDetalle" => $item->importe,
                        "item" => 1,
                        "numSerie" => $datos['serie'],
                        "numeroGuia" => $datos['numero'],
                        "precio" => $item->precio,
                        "tipoGuia" => "N",
                        "unidadMedida" => 1
                    );
                }
                $body = [
                    "anioGuiaRemision" => $anio_actual,
                    "breveteChofer" => null,
                    "codAlmacen" => $datos['codalmacen'],
                    "codAlmacenDestino" => null,
                    "codAlmacenOrigen" => null,
                    "codCliente" => null,
                    "codEstacion" => $datos['codestacion'],
                    "codListaPrecio" => null,
                    "codProveedor" => $datos['proveedor_id'],
                    "codtrabajador" => $datos['vendedor_id'],
                    "comentario" => $datos['comentario'],
                    "descuento" => $datos['monto_descuento'],
                    "detalle" => $body_detalle,
                    "direccionllegada" =>null,
                    "direccionpartida" => null,
                    "dnichofer" => null,
                    "estadoProceso" => "0",
                    "fechaEmision" => $datos['fecha_emision'],
                    "formapago" => $datos['forma_pago_id'],
                    "igv" => $datos['monto_igv'],
                    "modalidadTransporte" => "18",
                    "nombreTransportista" => null,
                    "nombrechofer" => null,
                    "numSerie" => $datos['serie'],
                    "seriefactura" => $datos['pedido_serie'],
                    "numeroFactura" => 159,
                    "numeroGuia" => $datos['numero'],
                    "placavehiculo" => null,
                    "rucTransportista" => null,
                    "tipoGuia" => "N", //N->ingreso; A->Salida
                    "tipoOperacion" => $datos['tipo_operacion_id'],
                    "tipomonda" => 1,
                    "totalVenta" => $datos['total_venta'],
                    "ubigeollegada" => null,
                    "ubigeopartida" => null,
                    "valorVenta" => $datos['importe_sin_igv']
                ];


                try {
                    $storeRemoto = Http::post("{$api_datos}/InsertGuiaDMK", $body)->object();
                    // dd($storeRemoto);
                    if ($storeRemoto->exito == false) {
                        $procede = false;
                        $msj = "No se pudo completar : {$storeRemoto->msgerror}";
                    }
                } catch (Exception $e) {
                    //throw $th;
                    dd($e);
                    $procede = false;
                    $msj = "No se pudo registrar remotamente";
                    $msj_tipo = "error";
                    $log = "{$e}";
                }
                
            }
        }

        if ($guardar_avance == true) {
            $datos['numero'] = null;
            $datos['serie'] = null;
        }

        
        if ($procede == true) {

            try {
                $guia = GuiaIngreso::create($datos);
            } catch (Exception $e) {
                //throw $th;
                dd($e);
                $procede = false;
                $msj = "No se pudo registrar la Guia de Salida";
                $msj_tipo = "error";
                $log = "{$e}";
            }
            
        }

        // dd($guia);
        if ($procede == true) {
            foreach ($detalle as $item) {

                if ($procede == true) {
                    $guiaDetalle = new GuiaIngresoDetalle();
                    $guiaDetalle->guia_ingreso_id = $guia->id;
                    $guiaDetalle->codarticulo = $item->codarticulo;
                    $guiaDetalle->precio = $item->precio;
                    $guiaDetalle->cantidad = $item->cantidad;
                    $guiaDetalle->importe = $item->importe;
                    $guiaDetalle->porcentaje_descuento = $item->porcentaje_descuento;
                    $guiaDetalle->monto_descuento = $item->monto_descuento;
                    $guiaDetalle->descripcion = $item->descripcion;
                    $guiaDetalle->precio_publico = $item->precio_publico;
                    $guiaDetalle->precio_sin_igv = $item->precio_sin_igv;
                    $guiaDetalle->codigo_barra = $item->codigo_barra;

                    try {
                        $guiaDetalle->save();
                    } catch (Exception $e) {
                        //throw $th;
                        dd($e);
                        $procede = false;
                        $msj = "No se pudo registrar el detalle";
                        $msj_tipo = "error";
                        $log = "{$e}";
                    }
                }
            }
        }

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'url_redirect' => $url_redirect]);
    }

    public function modalStore(Request $request)
    {
        return view('guia.ingreso.modal-store');
    }

    public function store(Request $request)
    {
        // dd($request->post());

        $api_datos = Parametro::find(6)->valor;
        $id = "";
        $es_guia_interna = $request->post('es_guia_interna');

        $datos = $request->post();
        $id_continuar = $request->post('id_continua');
        $datos['guardar_avance'] = ($datos['guardar_avance'] == 'true') ? true : false ;
        $guardar_avance = $datos['guardar_avance'];

        $datos['indicar_proveedor'] = ($datos['indicar_proveedor'] ?? '' == 'on') ? true : false ;
        
        $procede = true;
        $msj_tipo = "success";
        $log = "";
        $datos['guia_estado_id'] = 1; //registrado-emitida
        
        $url_redirect = route('guiasalida.index');


        // asignamos existencia de serie en BD
        if ($guardar_avance == false) {
            $msj = "Guia registrada";
            if ($es_guia_interna == 1) {
                // dd($es_guia_interna);
                
                $asignarSerie = $this->asignarSerie($datos['serie']);
                // dd($asignarSerie);
                $procede = $asignarSerie->procede;
            }
        }

        if ($guardar_avance == true) {
            $msj = "Avance de guia registrada";
            $datos['guia_estado_id'] = 4;//estado avance
        }

        // validacion antes del store
        if ($procede == true) {
            // dd($request->post());
            // $datos['fecha_emision'] = date('Y-m-d');
            $datos['fecha_emision'] = $request->post('fecha_emision');
            $datos['hora_emision'] = date('H:i:s');

            if ($id_continuar != null) {
                // dd('desactivamos el activo anterior');
                $guia_avance = GuiaIngreso::find($id_continuar);
                $guia_avance->activo = 0;
                try {
                    $guia_avance->save();
                } catch (Exception $e) {
                    //throw $th;
                    $procede = false;
                    $msj = "No se pudo limpiar la guia guardada";
                    $msj_tipo = "error";
                    $log = "{$e}";
                }
            }


            if ($guardar_avance == false) {
                // dd($asignarSerie);
                if ($es_guia_interna == 0) {
                    $datos['serie_id'] = null;
                    $datos['serie'] = $datos['serie_externa'];
                    
                }
                if ($es_guia_interna == 1) {
                    
                    $datos['serie_id'] = $asignarSerie->serieAsignada->id;
                    $datos['numero'] = intval($asignarSerie->serieAsignada->numero) +1; 
                }
            }

        }

        //registro en store
        if ($procede == true) {
            // dd($datos);
            try {
                $store = GuiaIngreso::create($datos);
            } catch (Exception $e) {
                // dd($e);
                $procede = false;
                $msj = "No se pudo registrar en Nube";
                $msj_tipo = "success";
                $log = "{$e}";
            }

        }

        // actualizar serie nube
        if ($procede == true) {
            if ($es_guia_interna == 1) {
                $updateSerie = Serie::find($asignarSerie->serieAsignada->id);
                // dd($datos);
                $updateSerie->numero = $datos['numero'];
    
                try {
                    $updateSerie->save();
                    
                } catch (Exception $e) {
                    //throw $th;
                    dd($e);
                    $procede = true;
                    $msj = "No se pudo actualizar serie de Nube";
                    $msj_tipo = "error";
                    $log = "{$e}";
                }
                
            }
        }

        //registrar detalle
        if ($procede == true) {
            $detalle = json_decode($request->post('detalle'));
            $id = $store->id;
            foreach ($detalle as $item) {
                
                if ($procede == true) {
                    $guiaDetalle = new GuiaIngresoDetalle();
                    $guiaDetalle->guia_ingreso_id = $store->id;
                    $guiaDetalle->codarticulo = $item->codarticulo;
                    $guiaDetalle->precio = $item->precio;
                    $guiaDetalle->cantidad = $item->cantidad;
                    $guiaDetalle->importe = $item->importe;
                    $guiaDetalle->porcentaje_descuento = $item->porcentaje_descuento;
                    $guiaDetalle->monto_descuento = $item->monto_descuento;

                    $nombreArticulo = $item->descripcion;

                    // $nombreArticuloSinComillas = str_replace('"', '', $nombreArticulo);

                    $nombreArticuloSinComillas = $this->limpiarCaracteres($nombreArticulo);

                    $nombreArticuloLimpio = json_decode('"' . $nombreArticuloSinComillas . '"');

                    // $guiaDetalle->descripcion = $item->descripcion;
                    $guiaDetalle->descripcion = $nombreArticuloLimpio;
                    $guiaDetalle->precio_publico = $item->precio_publico;
                    $guiaDetalle->precio_sin_igv = $item->precio_sin_igv;
                    $guiaDetalle->codigo_barra = $item->codigo_barra;

                    $guiaDetalle->cod_unidad = $item->cod_unidad;
                    $guiaDetalle->desc_unidad_medida = $item->desc_unidad_medida;
                    $guiaDetalle->sigla_umfe = $item->sigla_umfe;

                    try {
                        $guiaDetalle->save();
                    } catch (Exception $e) {
                        //throw $th;
                        // dd($e);
                        $procede = false;
                        $msj = "No se pudo registrar el detalle";
                        $msj_tipo = "error";
                        $log = "{$e}";
                    }
                }

            }
        }

        $this->registrarAuditoria($store->id, 1, 'guia_ingresos', json_encode($datos), strip_tags($msj));

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'id' => $id]);
    }
    
    function limpiarCaracteres($cadena)
    {
        $caracteresEspeciales = ['"', "'"];
        return str_replace($caracteresEspeciales, '', $cadena);
    }

    public function asignarSerie($serie_busqueda)
    {
        $api_datos = Parametro::find(6)->valor;
        
        $getSerieLocal = Serie::where('serie', $serie_busqueda)->first();
        $procede = true;
        $msj = "Serie asignada";
        $msj_tipo = "success";
        $log = "";
        $serieAsignada = "";

        if ($getSerieLocal == null) {

            $serie = null;
            $numero = null;

            try {
                $listSeries = Http::get("{$api_datos}/obtenerSeriesNumerosGuia")->object()->serienumeros;
                // dd($listSeries);
            } catch (Exception $e) {
                //throw $th;
                // dd($e);
                $procede = false;
                $msj = "Ocurrio un problema para obtener el Nº Serie (api)";
                $msj_tipo = "error";
                $log = "{$e}";
            }

            if ($procede == true) {
                // dd($listSeries);
                foreach ($listSeries as $item) {
                    if ($item->numserie == $serie_busqueda) {
                        $numero = $item->ultimoValormarket;
                        $serie = $item->numserie;
                        $documento_tipo_id = $item->tipodocumento;
                    }
                }
                $procede = false;
                // dd($numero);
                // dd([$serie, $numero]);
                if ($serie != null) {
                    // dd($serie);
                    $procede = true;
                    $id = 1;
                    $lastSerie = Serie::orderBy('id', 'desc')->first();
                    // dd($lastSerie);

                    if ($lastSerie != null) {
                        // dd('generamos serie');
                        $id = intval($lastSerie->id) + 1;
                    }
                    // dd($id);
                    $nueva_serie = new Serie();
                    $nueva_serie->id = $id;
                    $nueva_serie->serie = $serie;
                    $nueva_serie->documento_tipo_id = $documento_tipo_id;
                    $nueva_serie->numero = $numero;
                    
                    try {
                        $nueva_serie->save();
                        // dd($nueva_serie);
                        $getSerieLocal = Serie::find($id);
                    } catch (Exception $e) {
                        //throw $th;
                        // dd($e);
                        $procede = false;
                        $msj = "No se pudo generar serie";
                        $msj_tipo = "error";
                        $log = "{$e}";
                    }
                }

            }
            
        }

        if ($procede == true) {
            $serieAsignada = $getSerieLocal;
            // dd($serieAsignada);
        }

        return (object)['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'serieAsignada' => $serieAsignada];

    }
    
    public function storeDataMart(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;
        $panel_origen = $request->post('panel_origen');

        $id = $request->post('id');

        $guia = GuiaIngreso::find($id);
        $fecha = Carbon::parse($guia->fecha_emision);
        $anio = $fecha->year;

        $procede = true;
        $msj = "<b><i class='fa fa-check-double'></i>Guia Nº: {$guia->serie}-{$guia->numero} registrada en DataMart</b>";
        $msj_tipo = "success";
        $log = "";

        $detalle = GuiaIngresoDetalle::where('guia_ingreso_id', $guia->id)->get();

        foreach ($detalle as $item) {
            $body_detalle[] = array(
                "anioGuia" => $anio,
                "cantidad" => $item->cantidad,
                "codArticulo" => $item->codarticulo,
                "estadoProceso" => "0",
                "importeDetalle" => $item->importe,
                "item" => 1,
                "numSerie" => $guia->serie,
                "numeroGuia" => $guia->numero,
                "precio" => $item->precio,
                "tipoGuia" => "N",
                "unidadMedida" => 1
            );
        }


        $body = [
            "anioGuiaRemision" => $anio,
            "breveteChofer" => null,
            "codAlmacen" => $guia->codalmacen,
            "codAlmacenDestino" => null,
            "codAlmacenOrigen" => null,
            "codCliente" => null,
            "codEstacion" => $guia->codestacion,
            "codListaPrecio" => null,
            "codProveedor" => $proveedor_id ?? '',
            "codtrabajador" => $guia->vendedor_id,
            "comentario" => $guia->comentario,
            "descuento" => $guia->monto_descuento,
            "detalle" => $body_detalle,
            "direccionllegada" =>null,
            "direccionpartida" => null,
            "dnichofer" => null,
            "estadoProceso" => "0",
            "fechaEmision" => $guia->fecha_emision,
            "formapago" => $guia->forma_pago_id,
            "igv" => $guia->monto_igv,
            "modalidadTransporte" => "18",
            "nombreTransportista" => null,
            "nombrechofer" => null,
            "numSerie" => $guia->serie,
            "seriefactura" => $guia->pedido_serie,
            "numeroFactura" => $guia->pedido_numero,
            "numeroGuia" => $guia->numero,
            "placavehiculo" => null,
            "rucTransportista" => null,
            "tipoGuia" => "N", //N->ingreso; A->Salida
            "tipoOperacion" => $guia->tipo_operacion_id,
            "tipomonda" => 1,
            "totalVenta" => $guia->total_venta,
            "ubigeollegada" => null,
            "ubigeopartida" => null,
            "valorVenta" => $guia->importe_sin_igv,
        ];

        // dd("{$api_datos}/InsertGuiaDMK");
        // dd(json_encode($body));
        try {
            
            $storeRemoto = Http::post("{$api_datos}/InsertGuiaDMK", $body)->object();
            // dd($storeRemoto);
            if ($storeRemoto->exito == false) {
                $procede = false;
                $msj = "No se pudo completar : {$storeRemoto->msgerror}";
            }
        } catch (Exception $e) {
            //throw $th;
            // dd($e);
            $procede = false;
            $msj = "{$msj} <b>No se pudo registrar en DATAMARK";
            $msj_tipo = "error";
            $log = "{$e}";
        }


        if ($procede == true) {
            $guia_status = GuiaIngreso::find($guia->id);
            try {
                $guia_status->enviado_datamarket = 1;
                $guia_status->save();
            } catch (Exception $e) {
                //throw $th;
                $procede = false;
                $msj = "No se puedo registrar el envio";
                $msj_tipo = "error";
                $log = "{$e}";
            }
        }

        if ($procede == false) {
            if ($panel_origen != 'index') {
                $msj = "{$msj} <br> <button class='btn btn-success btn-sm' id='btnReintentarDataMart' data-id='{$id}' ><i class='fa-regular fa-paper-plane'></i> Reintentar</button>";
                
            }
        }

        $this->registrarAuditoria($guia->id, 1, 'guia_ingresos_datamart', json_encode($body), strip_tags($msj));

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function pdf(GuiaIngreso $guia, $valorada)
    {

        // dd($guia);
        $data = array();
        $ruc_entidad = Parametro::find(2)->valor;
        $nombreEntidad = Parametro::find(3)->valor;
        $direccion_entidad = Parametro::find(4)->valor;
        $telefonos = Parametro::find(4)->valor;
        $cabecera = (object) array(
            'nombre_entidad' => $nombreEntidad,
            'direccion_entidad' => $direccion_entidad,
            'telefono_entidad' => $telefonos,
            'ruc_entidad' => $ruc_entidad,
        );
        $data['cabecera'] = $cabecera;

        $data['documento'] = $guia;
        // dd($guia);


        $formatter = new NumeroALetras();
        $texto_moneda = 'soles';
        // dd($guia->total_venta);
        $total_letras = $formatter->toInvoice($guia->total_venta, 2, $texto_moneda);
        $total_letras = Str::upper($total_letras);

        $data['guia'] = (object) array(
            'texto_moneda' => $texto_moneda, 
            'concepto' => '-', 
            'monto' => '0.00',
            'total_letras' => $total_letras, 
            'nombre_cajero' => 'demo', 
            'total_venta_gravada' => $guia->importe_sin_igv,
            'monto_descuento' => $guia->monto_descuento,
            'total_igv' => $guia->monto_igv,
            'total' => $guia->total_venta,
        );

        $detalle = GuiaIngresoDetalle::where('guia_ingreso_id', $guia->id)->get();
        // dd($detalle);
        $data['valorada'] = $valorada;
        $data['detalle'] = $detalle;


        $pdf = Pdf::loadView('guia.ingreso.pdf', $data);
        // $('formato', $data);
        $pdf->setPaper('A4', 'portrait');
        $font = $pdf->getFontMetrics()->get_font("helvetica", "bold");
        // $pdf->getCanvas()->page_text(520, 810, "Pag. {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0, 0, 0));
        return $pdf->stream();
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function eliminar(Request $request)
    {
        // dd($request->post());
        $id = $request->post('id');
        
        $guia = GuiaIngreso::find($id);
        
        $guia->activo = 0;

        // dd($guia);

        $procede = true;
        $msj = "Guia de Ingreso {$guia->serie}-{$guia->numero} Eliminada";
        $msj_tipo = "success";
        $log = "";

        try {
            $guia->save();

        } catch (Exception $e) {
            //throw $th;
            $procede = false;
            $msj = "No se pudo eliminar la Guia";
            $msj_tipo = "error";
            $log = "{$e}";
        }

        // auditoria eliminar local
        $this->registrarAuditoria($guia->id, 4, 'guia_ingresos', json_encode($guia), strip_tags($msj));


        if ($procede == true) {
            $api_datos = Parametro::find(6)->valor;

            $fecha = Carbon::parse($guia->fecha_emision);
            $anio = $fecha->year;

            $cod_proveedor = $guia->proveedor_id;
            // if ($cod_proveedor == null) {
            //     $cod_proveedor = $guia->cliente_id;
            // }

            $body = [
                "anioGuiaRemision" => $anio,
                "codProveedor" => $cod_proveedor,
                "numSerie" => $guia->serie,
                "numeroGuia" => $guia->numero
            ];
            // dd($body);
            try {
                $anularRemoto = Http::post("{$api_datos}/EliminaGuiaDMK", $body)->object();

            } catch (Exception $e) {
                $procede = false;
                $msj = "No se pudo completar eliminar en DataMark";
                $msj_tipo = "";
                $log = "{$e}";
            }
            // dd($anularRemoto);
        }

        $this->registrarAuditoria($guia->id, 4, 'guia_ingresos_datamart', json_encode($body), strip_tags($msj));

        if ($procede == false) {
            $guia->guia_estado_id = 1;
            $guia->save();
        }


        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log]);
    }

    public function modalOtrasGuias(Request $request)
    {
        // dd('hola ');
        return view('guia.ingreso.modal_otras_guias');
    }


    public function buscarOtrasGuias(Request $request)
    {
        $activo = $request->post('activo');
        $serie = $request->post('serie');
        $numero = $request->post('numero');

        $consulta = GuiaIngreso::where('activo', $activo);
        if ($serie != '') {
            $consulta = $consulta->where('serie', $serie);
        }
        if ($numero != '') {
            $consulta = $consulta->where('numero', $numero);
        }

        $list = $consulta->get();

        foreach ($list as $key => $item) {
            $list[$key]->estado_nombre = GuiaEstado::find($item->guia_estado_id)->nombre;
        }
        // dd($list);
        
        return view('guia.ingreso.tabla_otras_guias', compact('list'));
    }

    public function cargarOtraGuia(Request $request)
    {
        $id = $request->post('id');

        $detalle = GuiaIngresoDetalle::where('guia_ingreso_id', $id)->get();
        // dd($detalle);

        $tabla = "";
        $base_clalculo = $request->post('base_calculo');

        foreach ($detalle as $item) {
            $peso = $item->peso_unitario;
            
            $unidad = "UNI";
            $inputCantidad = "<input type='number' class='form-control form-control-sm input_cantidad_tr' name='cantidad' value='{$item->cantidad}'></input>";
            $inputPorcentajeDescuento = "<input class='form-control form-control-sm input_porcentaje_descuento_tr' name='porcentaje_descuento' value='{$item->porcentaje_descuento}'></input>";
            $inputDescuento = "<input type='hidden' name='monto_descuento' value='{$item->monto_descuento}'></input>";
            $importe = $item->cantidad * $item->precio_publico;
            $span_precio = $item->precio_publico;

            if ($base_clalculo == 1) {
                $span_precio = $item->precio_sin_igv;
                $importe = $item->cantidad * $item->precio_sin_igv;
            }


            $tabla = "
                <tr
                    data-producto_id = '{$item->codarticulo}'
                    data-precio_unitario = {$item->precio}
                    data-precio_publico = {$item->precio_publico}
                    data-precio_sin_igv='{$item->precio_sin_igv}'
                    data-descripcion = '{$item->descripcion}'
                    data-codigo = '{$item->codarticulo}'
                    data-peso = '{$peso}'
                    data-codigo_barra='{$item->codigo_barra}'
                >
                    <td class='align-middle'>{$item->codigo_barra}</td>
                    <td class='align-middle'>{$item->codarticulo}</td>
                    <td class='align-middle'>{$item->codarticulo}</td>
                    <td class='align-middle'>{$item->descripcion}</td>
                    <td class='align-middle'><span name='span_precio'>{$span_precio}</span></td>
                    <td class='align-middle'>{$inputCantidad}</td>
                    <td class='align-middle'>{$unidad}</td>
                    <td class='align-middle'><span name='span_importe'>{$importe}</span></td>
                    <td class='align-middle'>{$inputPorcentajeDescuento} {$inputDescuento}</td>
                    <td class='align-middle' style='text-align:center'>
                        <input class='bonificacion' type='checkbox' name='bonificacion' >
                    </td>
                    <td class='align-middle text-center'>
                        <button class='btn btn-danger btn-sm delete_item'><i class='fa fa-times-circle'></i></button>
                    </td>
                </tr>
            ";
        }

        return response()->json(['tabla' => $tabla]);

    }

    public function registrarAuditoria($registro_id, $accion_id, $tabla, $data_json, $observaciones=null)
    {
        $procede = true;
        $msj = "Auditoria registrada";
        $msj_tipo = "success";
        $log = "";
        
        $empleado_id = User::find(Auth::id())->empleado_id;
        
        try {
            $auditoria = new Auditoria();
            $auditoria->registro_id = $registro_id;
            $auditoria->accion_id = $accion_id;
            $auditoria->tabla = $tabla;
            $auditoria->data_json = $data_json;
            $auditoria->observaciones = $observaciones;
            $auditoria->empleado_id = $empleado_id;

            $auditoria->save();
        } catch (Exception $e) {
            //throw $th;
            $procede = false;
            $msj = "No se pudo registrar la auditoria";
            $msj_tipo = "error";
            $log = "{$e}";
        }

        return (object) array ('procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log);

    }

}
