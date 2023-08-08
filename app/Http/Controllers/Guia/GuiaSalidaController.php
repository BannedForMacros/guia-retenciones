<?php

namespace App\Http\Controllers\Guia;

use App\Http\Controllers\Controller;
use App\Models\FacturacionEnvio;
use App\Models\GuiaEstado;
use App\Models\GuiaSalida;
use App\Models\GuiaSalidaDetalle;
use App\Models\Parametro;
use Exception;
use Faker\Provider\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Luecano\NumeroALetras\NumeroALetras;
use Illuminate\Support\Str;

class GuiaSalidaController extends Controller
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
        // $list = GuiaSalida::where('activo',1)->get();
        // dd($list);
        return view('guia.salida.index');
    }

    public function listar(Request $request)
    {
        $fechaInicio = $request->post('fecha_inicio');
        $fechaFin = $request->post('fecha_fin');
        $serie = $request->post('serie');
        $numero = $request->post('numero');

        $consulta = DB::table('guia_salidas')->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])->where('activo',1);

        if ($serie != '') {
            $consulta = $consulta->where('serie', $serie);
        }
        if ($numero != '') {
            $consulta = $consulta->where('numero', $numero);
        }

        $list = $consulta->get();
        
        foreach ($list as $key => $value) {
            if ($value->envio_id != null) {
                $getEnvio = FacturacionEnvio::find($value->envio_id);
                // dd($getEnvio->pdf417);
            }
            $list[$key]->estado_nombre = GuiaEstado::find($value->guia_estado_id)->nombre;

            $texto_razon_social = $value->proveedor_nombre;

            if ($value->indicar_proveedor == 0) {
                $texto_razon_social = $value->cliente_razon_social;
            }
            $list[$key]->texto_razon_social = $texto_razon_social;
        }
        return view('guia.salida.tabla', compact('list'));
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

        $getSerie->nuevo_numero = str_pad(($getSerie->ultimoValormarket + 1), 4, "0", STR_PAD_LEFT);

        return response()->json(['getSerie' => $getSerie]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $api_datos = Parametro::find(6)->valor;

        $listProveedores = [];
        // dd(count($listProveedores));
        // $listFormasPago = Http::post(route('simulacion.ObtenerFormasPago'), [])->object();
        // $listFormasPago = Http::get("http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerFormasPago")->object()->formasdePago;
        $listFormasPago = Http::get("{$api_datos}/ObtenerFormasPago")->object()->formasdePago;
        // $listTipoOperacion = Http::post(route('simulacion.ObtenerOperaciones'), [])->object();
        $listTipoOperacion = Http::get("{$api_datos}/ObtenerOperacion")->object()->operaciones;
        // $listTipoOperacion = [];
        // dd($listTipoOperacion);
        // $listAlmacenes = Http::post(route('simulacion.ObtenerAlmacenes'), [])->object();
        $listAlmacenes = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        $listAlmacenOrigen = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        $listAlmacenDestino = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        $listPrecios = Http::get("{$api_datos}/ObtenerSucursalPrecio")->object()->listasPrecio;
        $listVendedores = Http::get("{$api_datos}/ObtenerTrabajador?CodigoTrabajador=-1")->object()->trabajador;
        // $getVendedor = $listVendedores[0];
        // dd($getVendedor);
        // dd($getVendedor);
        // dd($listAlmacenes);
        // $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();
        $listArticulos = array();
        // $listClientes = Http::post(route('simulacion.ObtenerClientes'), [])->object();
        $listClientes = [];
        // dd($listClientes);
        $listVehiculos = Http::post("{$api_datos}/ObtenerVehiculo", ['valor' => '', 'tipo' => 4])->object()->vehiculos;
        $listChoferes = Http::post("{$api_datos}/ObtenerChoferes", ['nombrechofer' => ''])->object()->choferes;
        // dd($listChoferes);

        $listSeries = Http::get("{$api_datos}/obtenerSeriesNumerosGuia")->object()->serienumeros;
        // dd($listSeries);
        $listUbigeos = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => '', 'tipoConsulta' => 1 ])->object()->ubigeos;
        // dd($listUbigeos);

        // $listUbigeosDepartamentoPartida = $listUbigeos;
        $listUbigeosDepartamentoPartida = $listUbigeos;
        $listUbigeosProvinciaPartida = [];
        $listUbigeosDistritoPartida = [];

        $listUbigeosDepartamentoLlegada = $listUbigeos;
        $listUbigeosProvinciaLlegada = [];
        $listUbigeosDistritoLlegada = [];
        


        return view('guia.salida.create', compact('listSeries','listProveedores', 'listFormasPago', 'listTipoOperacion', 'listPrecios', 'listAlmacenes', 'listArticulos', 'listClientes', 'listVendedores', 'listVehiculos', 'listChoferes', 'listUbigeosDepartamentoPartida', 'listUbigeosProvinciaPartida', 'listUbigeosDistritoPartida', 'listUbigeosDepartamentoLlegada', 'listUbigeosProvinciaLlegada', 'listUbigeosDistritoLlegada', 'listAlmacenOrigen', 'listAlmacenDestino'));
    }

    public function continuar(GuiaSalida $guia)
    {
        $api_datos = Parametro::find(6)->valor;

        $listProveedores = [];
        if ($guia->proveedor_id != null) {
            $listProveedores = Http::post("{$api_datos}/ObtenerProveedores", ['valor' => $guia->proveedor_id, 'tipo' => 1])->object()->proveedores;

        }

        $listFormasPago = Http::get("{$api_datos}/ObtenerFormasPago")->object()->formasdePago;

        $listTipoOperacion = Http::get("{$api_datos}/ObtenerOperacion")->object()->operaciones;

        if (count($listTipoOperacion) > 0 ) {
            foreach ($listTipoOperacion as $key => $item) {
                $selected = "";
                if ($item->tipoOperacion == $guia->tipo_operacion_id) {
                    $selected = "selected";
                }

                $listTipoOperacion[$key]->selected = $selected;
            }
        }
        // dd($listTipoOperacion);

        $listAlmacenes = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        $listAlmacenOrigen = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;
        $listAlmacenDestino = Http::get("{$api_datos}/ObtenerAlmacenes")->object()->almacenes;

        foreach ($listAlmacenes as $key => $value) {
            $selected = "";
            if ($value->codAlmacen == $guia->codalmacen) {
                $selected = "selected";
            }
            $listAlmacenes[$key]->selected = $selected;
        }
        foreach ($listAlmacenOrigen as $key => $value) {
            $selected = "";
            if ($value->codAlmacen == $guia->cod_almacen_origen) {
                $selected = "selected";
            }
            $listAlmacenOrigen[$key]->selected = $selected;
        }
        
        foreach ($listAlmacenDestino as $key => $value) {
            $selected = "";
            if ($value->codAlmacen == $guia->cod_almacen_destino) {
                $selected = "selected";
            }
            $listAlmacenDestino[$key]->selected = $selected;
        }

        $listPrecios = Http::get("{$api_datos}/ObtenerSucursalPrecio")->object()->listasPrecio;
        $listVendedores = Http::get("{$api_datos}/ObtenerTrabajador?CodigoTrabajador=-1")->object()->trabajador;

        $listArticulos = array();

        $listClientes = Http::post("{$api_datos}/obtenerCliente", ['valor' => $guia->cliente_id, 'tipo' => 1])->object()->cliente;
        // dd($listClientes);

        foreach ($listClientes as $key => $item) {
            $tipo_documento = $item->tipoDocumentoIdentidad;
            $nro_documento = $item->dni;
            if ($tipo_documento == '') {
                $nro_documento = trim($item->rucCliente);
            }
            $listClientes[$key]->texto_cliente = "[{$nro_documento}] {$item->razonSocial}";
        }


        $listVehiculos = Http::post("{$api_datos}/ObtenerVehiculo", ['valor' => '', 'tipo' => 4])->object()->vehiculos;
        $listChoferes = Http::post("{$api_datos}/ObtenerChoferes", ['nombrechofer' => ''])->object()->choferes;

        $listSeries = Http::get("{$api_datos}/obtenerSeriesNumerosGuia")->object()->serienumeros;
        
        foreach ($listVendedores as $key => $value) {
            $selected = "";
            if ($value->codTrabajador == $guia->vendedor_id) {
                $selected = "selected";
            }

            $listVendedores[$key]->selected = $selected;
        }

        
        $listTransportistas = Http::post("{$api_datos}/ObtenerTransportista", ['valor' => $guia->transportista_ruc, 'tipo' => 3])->object()->transportistas;
        foreach ($listTransportistas as $key => $value) {
            $listTransportistas[$key]->texto_transportista = "[{$value->rucTransportista}] {$value->nombreTransportista}";
        }
        // dd($listTransportistas);
        
        // dd($guia);
        
        // ubigeos de partida
        $listUbigeosDepartamentoPartida = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => '', 'tipoConsulta' => 1 ])->object()->ubigeos;
        
        foreach ($listUbigeosDepartamentoPartida as $key => $value) {
            $selected = '';
            if (trim($value->codUbigeo) == $guia->ubigeo_partida_departamento) {
                $selected = "selected";
            }
            $listUbigeosDepartamentoPartida[$key]->selected = $selected; 
        }
        // dd($guia->ubigeo_partida_provincia);
        $listUbigeosProvinciaPartida = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => $guia->ubigeo_partida_departamento, 'tipoConsulta' => 2 ])->object()->ubigeos;
        // dd($listUbigeosProvinciaPartida);
        foreach ($listUbigeosProvinciaPartida as $key => $value) {
            $selected = "";
            if (trim($value->codUbigeo) == $guia->ubigeo_partida_provincia) {
                $selected = "selected";
            }
            $listUbigeosProvinciaPartida[$key]->selected = $selected;
        }
        $listUbigeosDistritoPartida = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => $guia->ubigeo_partida_provincia, 'tipoConsulta' => 3 ])->object()->ubigeos;
        // dd($listUbigeosDistritoPartida);
        foreach ($listUbigeosDistritoPartida as $key => $value) {
            $selected = "";
            if (trim($value->codUbigeo) == $guia->ubigeo_partida_distrito) {
                $selected = "selected";
            }
            $listUbigeosDistritoPartida[$key]->selected = $selected;
        }

        // dd($listUbigeosDepartamentoPartida);
        // ubigeos de legada
        $listUbigeosDepartamentoLlegada = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => '', 'tipoConsulta' => 1 ])->object()->ubigeos;
        
        foreach ($listUbigeosDepartamentoLlegada as $key => $value) {
            $selected = '';
            if (trim($value->codUbigeo) == $guia->ubigeo_llegada_departamento) {
                $selected = "selected";
            }
            $listUbigeosDepartamentoLlegada[$key]->selected = $selected; 
        }


        $listUbigeosProvinciaLlegada = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => $guia->ubigeo_llegada_departamento, 'tipoConsulta' => 2 ])->object()->ubigeos;
        // dd($listUbigeosProvinciaLlegada);
        foreach ($listUbigeosProvinciaLlegada as $key => $value) {
            $selected = "";
            if (trim($value->codUbigeo) == $guia->ubigeo_llegada_provincia) {
                $selected = "selected";
            }
            $listUbigeosProvinciaLlegada[$key]->selected = $selected;
        }

        $listUbigeosDistritoLlegada = Http::post("{$api_datos}/ObtieneUbigeos", ['codigoUbigeo' => $guia->ubigeo_llegada_provincia, 'tipoConsulta' => 3 ])->object()->ubigeos;
        // dd($listUbigeosDistritoLlegada);
        foreach ($listUbigeosDistritoLlegada as $key => $value) {
            $selected = "";
            if (trim($value->codUbigeo) == $guia->ubigeo_llegada_distrito) {
                $selected = "selected";
            }
            $listUbigeosDistritoLlegada[$key]->selected = $selected;
        }


        $detalle = GuiaSalidaDetalle::where('guia_salida_id', $guia->id)->get();

        return view('guia.salida.create', compact('guia', 'detalle','listSeries','listProveedores', 'listFormasPago', 'listTipoOperacion', 'listPrecios', 'listAlmacenes', 'listArticulos', 'listClientes', 'listVendedores', 'listVehiculos', 'listChoferes', 'listTransportistas', 'listUbigeosDepartamentoPartida','listUbigeosProvinciaPartida', 'listUbigeosDistritoPartida', 'listUbigeosDepartamentoLlegada', 'listUbigeosProvinciaLlegada', 'listUbigeosDistritoLlegada', 'listAlmacenOrigen', 'listAlmacenDestino'));
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
        
        if (strlen($valor) > $maximo) {
            $listArticulos = Http::post("{$api_datos}/ObtenerArticulo", 
                ['valor' => $valor, 'tipoconsulta' => $tipoconsulta, 'codestacion' => $codestacion, 'codalmacen' => $codalmacen, 'codlistaprecio' => $codlistaprecio]
            )->object()->articulos;
            
        }

        // dd($listArticulos);
        $items = array();
        foreach ($listArticulos as $item) {
            $items[] = (object) array('id' => $item->codArticulo, 'text' => "[{$item->codBarra}] {$item->nombreArticulo}", 'codigo_barra' => $item->codBarra, 'descripcion' => $item->nombreArticulo, 'precio_publico' => $item->precioPublico, 'precio_sin_igv' => $item->precioSinIGV );
        }

        return response()->json(['items' => $items]);
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

    public function listarClientes(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $valor = trim($request->get('term'));
        $tipo = $request->get('tipo_busqueda_cliente');//busqueda por razon social
        // dd($request->all());
        if (strlen($valor) > 2) {
            $listClientes = Http::post("{$api_datos}/obtenerCliente", 
                ['valor' => $valor, 'tipo' => $tipo]
            )->object()->cliente;
            
        }

        // dd($listClientes);
        $items = array();
        foreach ($listClientes as $item) {
            $tipo_documento = $item->tipoDocumentoIdentidad;
            $nro_documento = $item->dni;
            $documento_tipo_nombre = 'DNI';
            if ($tipo_documento == '') {
                $documento_tipo_nombre = 'RUC';
                $nro_documento = trim($item->rucCliente);
            }
            $items[] = (object) array('id' => $item->codCliente, 'text' => "[{$nro_documento}] {$item->razonSocial}", 'direccion' => $item->direccion, 'razon_social' => $item->razonSocial, 'nro_documento' => $nro_documento, 'documento_tipo_nombre' => $documento_tipo_nombre );
        }

        return response()->json(['items' => $items]);
    }

    public function listarTransportistas(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $valor = trim($request->get('term'));
        $tipo = 1;//busqueda por nombre
        // dd($request->all());
        if (strlen($valor) >= 0) {
            $listItems = Http::post("{$api_datos}/ObtenerTransportista", 
                ['valor' => $valor, 'tipo' => $tipo]
            )->object()->transportistas;
            
        }

        // dd($listItems);
        $items = array();
        foreach ($listItems as $item) {
            $items[] = (object) array('id' => $item->codTransportista, 'text' => "[{$item->rucTransportista}] {$item->nombreTransportista}", 'transportista_direccion' => $item->direccionTransportista, 'ruc' => $item->rucTransportista, 'nombre' => $item->nombreTransportista );
        }

        return response()->json(['items' => $items]);
    }

    public function listarUbigeos(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $codigoUbigeo = $request->post('codUbigeo');
        $tipoConsulta = $request->post('tipo_busqueda');
        $tipo_ubigeo = $request->post('tipo_ubigeo');
        $next = false;
        $procede = true;
        $listUbigeos = Http::post("{$api_datos}/ObtieneUbigeos", 
            ['codigoUbigeo' => $codigoUbigeo, 'tipoConsulta' => $tipoConsulta ]
        )->object()->ubigeos;

        $tag_id = "{$tipo_ubigeo}_departamento";
        if ($tipoConsulta == 2) {
            $next = true;
            $tag_id = "{$tipo_ubigeo}_provincia";
        }
        if ($tipoConsulta == 3) {
            $tag_id = "{$tipo_ubigeo}_distrito";
        }

        // dd($listUbigeos);
        $options = "";
        foreach ($listUbigeos as $item) {
            $codigo = trim($item->codUbigeo);
            $options .= "<option value='{$codigo}'>{$item->descripcion}</option>";
        }

        return response()->json(['options' => $options, 'tag_id' => $tag_id, 'next' => $next, 'procede' => $procede]);
    }

    public function agregarItem(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        $producto_id = $request->post('producto_id');
        $codigo_barra = $request->post('codigo_barra');
        $cod_plu = $request->post('cod_plu');
        $descripcion = $request->post('descripcion');
        $precio_publico = $request->post('precio_publico');
        $precio_sin_igv = $request->post('precio_sin_igv');
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
            $inputCantidad = "<input class='form-control form-control-sm input_cantidad_tr' name='cantidad' value='{$cantidad}'></input>";
            $inputPorcentajeDescuento = "<input class='form-control form-control-sm input_porcentaje_descuento_tr' name='porcentaje_descuento' value='0'></input>";
            $inputDescuento = "<input type='hidden' name='monto_descuento' value='0'></input>";
            $span_precio = $precio_publico;
            $importe = $cantidad * $precio_publico;
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
                    data-codigo_barra = '{$codigo_barra}'
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
                    <td class='align-middle text-center'>
                        <button class='btn btn-danger btn-sm delete_item'><i class='fa fa-times-circle'></i></button>
                    </td>
                </tr>
            ";
        }

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'tr' => $tr]);
    }

    public function modalStore(Request $request)
    {
        $envio_sunat = $request->post('envio_sunat');
        return view('guia.salida.modal-store', compact('envio_sunat'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $api_datos = Parametro::find(6)->valor;

        // dd($request->post());
        $datos = $request->post();
        $id_continuar = $request->post('id_continua');

        $detalle = json_decode($request->post('detalle'));
        $guardar_avance = ($datos['guardar_avance'] == 'true') ? true : false ;
        $datos['indicar_proveedor'] = ($datos['indicar_proveedor'] ?? '' == 'on') ? true : false ;
        // dd($guardar_avance);
        if ($datos['tipo_operacion_id'] == 12) {
            $datos['codalmacen'] = '';
        }else{
            $datos['codAlmacenOrigen'] = '';
            $datos['codAlmacenDestino'] = '';
        }
        if ($datos['peso_bruto_total'] == '') {
            $datos['peso_bruto_total'] = 0;
        }
        unset($datos['detalle']);
        // dd($datos);
        $procede = true;

        $msj_tipo = "success";
        $log = "";
        $datos['fecha_emision'] = date('Y-m-d');
        $datos['hora_emision'] = date('H:i:s');
        $url_redirect = route('guiasalida.index');
        $guia_estado_id = 1;
        if ($guardar_avance == true) {
            $guia_estado_id = 4;
        }
        $datos['guia_estado_id'] = $guia_estado_id;
        
        $getLast = GuiaSalida::orderBy('id', 'desc')->first();
        $numero = 1;
        $id = null;

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
        
        $anio_actual = date('Y');
        

        $proveedor_id = '';
        if ($datos['indicar_proveedor'] == true) {
            $proveedor_id = ($datos['proveedor_id'] ?? null) ? $datos['proveedor_id'] : null ;
        }
        
        if ($datos['tipo_operacion_id'] != 12) {//diferente a trasnsferencia
            $datos['cod_almacen_origen'] = null;
            $datos['almacen_origen_nombre'] = null;
            $datos['cod_almacen_destino'] = null;
            $datos['almacen_destino_nombre'] = null;
        }

        if ($datos['tipo_operacion_id'] == 12) {//transferencia
            $datos['codalmacen'] = null;
            $datos['almacen_nombre'] = null;
        }

        // dd(json_encode($body));
        // dd($body);
        // dd($guardar_avance);

        if ($id_continuar != null) {
            // dd('desactivamos el activo anterior');
            $guia_avance = GuiaSalida::find($id_continuar);
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
                        "tipoGuia" => "A",
                        "unidadMedida" => 1
                    );
                }

                $body = [
                    "anioGuiaRemision" => $anio_actual,
                    "breveteChofer" => $datos['brevete'],
                    "codAlmacen" => $datos['codalmacen'],
                    "codAlmacenOrigen" => $datos['cod_almacen_origen'],
                    "codAlmacenDestino" => $datos['cod_almacen_destino'],
                    "codCliente" => $datos['cliente_id'] ?? '',
                    "codEstacion" => $datos['codestacion'],
                    "codListaPrecio" => $datos['codlistaprecio'],
                    "codProveedor" => $proveedor_id,
                    "codtrabajador" => $datos['vendedor_id'],
                    "comentario" => $datos['comentario'],
                    "descuento" => $datos['monto_descuento'],
                    "detalle" => $body_detalle,
                    "direccionllegada" => $datos['direccion_llegada'],
                    "direccionpartida" => $datos['direccion_partida'],
                    "dnichofer" => $datos['chofer_dni'],
                    "estadoProceso" => "0",
                    "fechaEmision" => $datos['fecha_emision'],
                    "formapago" => $datos['forma_pago_id'],
                    "igv" => $datos['monto_igv'],
                    "modalidadTransporte" => "18",
                    "nombreTransportista" => $datos['transportista_nombre'],
                    "nombrechofer" => $datos['transportista_nombre'],
                    "numSerie" => $datos['serie'],
                    "seriefactura" => $datos['pedido_serie'],
                    "numeroFactura" => 159,
                    "numeroGuia" => $datos['numero'],
                    "placavehiculo" => $datos['vehiculo_placa'],
                    "rucTransportista" => $datos['transportista_ruc'],
                    "tipoGuia" => "A", //N->ingreso; A->Salida
                    "tipoOperacion" => $datos['tipo_operacion_id'],
                    "tipomonda" => 1,
                    "totalVenta" => $datos['total_venta'],
                    "ubigeollegada" => $datos['ubigeo_llegada'],
                    "ubigeopartida" => $datos['ubigeo_partida'],
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
            $msj = "<b>Guia de Salida registrada Nº: {$datos['serie']}-{$datos['numero']}</b>";
            
        }

        if ($guardar_avance == true) {
            // dd('holap');
            $datos['numero'] = null;
            $datos['serie'] = null;
            $msj = "<b>Avance de Guia de Salida registrada </b>";
        }
        // dd($datos);
        
        if ($procede == true) {
            
            try {
                $guia = GuiaSalida::create($datos);
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
            $id = $guia->id;
            foreach ($detalle as $item) {

                if ($procede == true) {
                    $guiaDetalle = new GuiaSalidaDetalle();
                    $guiaDetalle->guia_salida_id = $guia->id;
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

        if ($procede == true) {
            if ($datos['envio_sunat'] == 1) {
                $msj = "{$msj} <button class='btn btn-sm btn-success'><i class='fa fa-external-link'></i> Ver</button>";
            }
        }

        // dd($msj);

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'url_redirect' => $url_redirect, 'id' => $id]);
    }

    public function facturacionElectronica(Request $request)
    {
        $api_facturacion = Parametro::find(7)->valor;

        $id = $request->post('id');
        $guia = GuiaSalida::find($id);

        $ruc_emisor = Parametro::find(2)->valor;
        $razon_social_emisor = Parametro::find(3)->valor;

        $cliente_documento_tipo = 6;
        if ($guia->cliente_documento_tipo_nombre == 'DNI') {
            $cliente_documento_tipo = 1;
        }

        $detalle = GuiaSalidaDetalle::where('guia_salida_id', $guia->id)->get();
        // dd($detalle);
        $nro = 1;
        foreach ($detalle as $item) {
            $body_detalle[] = array(
                'Correlativo' => $nro++,
                "CodigoItem" => "{$item->codarticulo}",
                "Descripcion" => "{$item->descripcion}",
                "UnidadMedida" => "NIU",
                "Cantidad" => $item->cantidad,
                "LineaReferencia" => 1
            );
        }

        // [
        //     [
        //         "Correlativo" => 1,
        //         "CodigoItem" => "ENTREGA DE EQUIPO",
        //         "Descripcion" => "ENTREGA DE EQUIPO",
        //         "UnidadMedida" => "NIU",
        //         "Cantidad" => 1,
        //         "LineaReferencia" => 1
        //     ]
        // ]

        $body = [
            // "IdDocumento" => "T001-00000070",
            "IdDocumento" => "T00{$guia->serie}-{$guia->numero}",
            "FechaEmision" => "{$guia->fecha_emision}",
            "HoraEmision" => "{$guia->hora_emision}",
            "TipoDocumento" => "09",
            "Glosa" => $guia->comentario,
            "Remitente" => [
                // "NroDocumento" => "20369872274",
                "NroDocumento" => $ruc_emisor,
                "TipoDocumento" => "6",
                // "NombreRazonSocial" => "Franco Supermercado E.I.R.L."
                "NombreRazonSocial" => $razon_social_emisor
            ],
            "Destinatario" => [
                // "NroDocumento" => "20369872274",
                "NroDocumento" => $guia->cliente_nro_documento,
                // "TipoDocumento" => "6",
                "TipoDocumento" => "{$cliente_documento_tipo}",
                // "NombreRazonSocial" => "Luis Ordoñez Villacorta"
                "NombreRazonSocial" => $guia->cliente_razon_social
            ],
            "Proveedor" => [
                "NroDocumento" =>  $guia->proveedor_ruc ?? '',
                "TipoDocumento" => 6,
                "NombreRazonSocial" => $guia->proveedor_nombre ?? ''
            ],
            "DocumentoRelacionado" => [
                // "descripcion" => "Factura",
                "descripcion" => "",
                // "nrorucemisor" => "20117332714",
                "nrorucemisor" => "",
                "NroDocumento" => "",
                // "TipoDocumento" => "01"
                "TipoDocumento" => ""
            ],
            // "CodigoMotivoTraslado" => "01",
            "CodigoMotivoTraslado" => "{$guia->motivo_traslado_id}",
            // "DescripcionMotivoTraslado" => "VENTA",
            "DescripcionMotivoTraslado" => $guia->descripcion_motivo_traslado,
            // "PesoBrutoTotal" => 1,
            "PesoBrutoTotal" => $guia->peso_bruto_total,
            "UnidadPesobrutototal" => "KGM",
            // "UnidadPesobrutototal" => "",
            "NroPallets" => 0,
            "ModalidadTraslado" => "01",
            // "FechaInicioTraslado" => "2023-06-02",
            "FechaInicioTraslado" => $guia->fecha_emision,
            "RucTransportista" => "{$guia->transportista_ruc}",
            "RazonSocialTransportista" => "{$guia->transportista_nombre}",
            "NroPlacaVehiculo" => $guia->vehiculo_placa,
            "NroDocumentoConductor" => "{$guia->chofer_dni}",
            "NombresdelConductor" => "{$guia->chofer_nombre}",
            "NrolicenciaConductor" => "{$guia->chofer_brevete}",
            "DireccionPartida" => [
                "Ubigeo" => "{$guia->ubigeo_partida}",
                "DireccionCompleta" => "{$guia->direccion_partida}",
                "codigoanexo" => ""
            ],
            "DireccionLlegada" => [
                "Ubigeo" => "{$guia->ubigeo_llegada}",
                "DireccionCompleta" => "{$guia->direccion_llegada}",
                "codigoanexo" => ""
            ],
            "NumeroContenedor" => "",
            "Nropresintocontenedor" => "",
            "CodigoPuerto" => "",
            "VehiculoM1L" => 0,
            "BienesATransportar" => $body_detalle
        ]; 
        
        // dd($body);
        
        $url_button = route('guiasalida.pdf', ['guia'=> $guia->id]);
        
        $procede = true;
        $msj = "Guia electronica emitida correctamente  <br><a class='btn btn-success' href='{$url_button}' target='_blank'><i class='fa fa-external-link'></i> ver</a>";
        $msj_tipo = "";
        $log = "";

        $credencial = Parametro::find(1)->valor;
        try {
            $send = Http::withHeaders(['Credencial' => $credencial])
                        ->put("{$api_facturacion}", $body)->object();
                        // ->put('http://161.132.192.240:8180/api/Guia21', $body)->object();
            // dd($send);
        } catch (Exception $e) {
            //throw $th;
            // dd($e);
            $procede = false;
            $msj = "Ocurrio un error en el envio a sunat";
            $msj_tipo = "error";
            $log = "{$e}";
        }

        if ($procede == true) {
            try {
                $store = new FacturacionEnvio();
                $store->tabla = 'guia_salidas';
                $store->registro_id = $id;
                $store->trama_json = json_encode($body);
                $store->codigo_hash = $send->CodigoHash;
                $store->codigo_qr = $send->CodigoQr;
                $store->pdf417 = $send->pdf417;
                $store->exito = $send->Exito;
                $store->mensaje_error = $send->MensajeError;
                $store->pila = $send->Pila;
                // dd($store);
                $store->save();
            } catch (Exception $e) {
                //throw $th;
                // dd($e);
                $procede = false;
                $msj = "Ocurrio un error al guardar la respuesta del envio";
                $msj_tipo = "error";
                $log = "{$e}";
            }
        }

        if ($procede == true) {//actulizamos el id del envio en la tabla original

            $guia->envio_id = $store->id;
            
            try {

                $guia->save();

            } catch (Exception $e) {
                //throw $th;
                dd($e);
                $procede = false;
                $msj = "Ocurrio un error al actualizar el envio en la guia";
                $msj_tipo = "error";
                $log = "{$e}";
            }
        }

        if ($procede == true) {//obtener PDF y XML
            $api_facturacion_consultas = Parametro::find(8)->valor;
            $serie_format = str_pad($guia->serie, 3, "0", STR_PAD_LEFT);
            $bodyConsulta = array(
                // 'token' => 'W6quxyHjJnAF268qPLXd16VdBVJvVAcQxpzP1Uek0j5/6IPkpk6yqyPB9sQRN+Ks',
                'token' => $credencial,
                'serie' => "T{$serie_format}-{$guia->numero}",
                'tipodocumentoconsulta' => '09',
                'fecha' => $guia->fecha_emision,
                'tipodocumentorespuesta' => 'PDF'
            );
            // dd($bodyConsulta);
            $getPdf = Http::withHeaders(['Credencial' => $credencial])->post($api_facturacion_consultas, $bodyConsulta)->object();
            // dd($getPdf->data);
            if ($getPdf->success == true) {
                $storePdf = FacturacionEnvio::find($store->id);
                $storePdf->pdf = $getPdf->data;
                try {
                    $storePdf->save();
                    
                } catch (Exception $e) {
                    //throw $th;
                    // dd($e)
                    
                }
            }
        }

        


        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log]);
    }

    public function pdf(GuiaSalida $guia)
    {

        // dd($guia);
        $data = array();
        $cabecera = (object) array(
            'nombre_entidad' => 'MILKA SUPERMERCADOS E.I.R.L',
            'direccion_entidad' => 'jr. jose sagobal 1200 BR San Sebastian',
            'telefono_entidad' => '--',
            'ruc_entidad' => '20491576902',
        );
        $data['cabecera'] = $cabecera;

        $data['documento'] = $guia;
        // dd($guia);


        $formatter = new NumeroALetras();
        $texto_moneda = 'soles';
        $total_letras = $formatter->toInvoice($guia->total_venta, 2, $texto_moneda);
        $total_letras = Str::upper($total_letras);

        $data['guia'] = (object) array(
            'texto_moneda' => $texto_moneda, 
            'concepto' => '-', 
            'monto' => '0.00',
            'total_letras' => $total_letras, 
            'nombre_cajero' => 'demo', 
            'total_venta_gravada' => $guia->importe_sin_igv,
            'total_igv' => $guia->monto_igv,
            'total' => $guia->total_venta,
        );

        $detalle = GuiaSalidaDetalle::where('guia_salida_id', $guia->id)->get();
        // dd($detalle);
        $data['detalle'] = $detalle;


        $pdf = Pdf::loadView('guia.salida.pdf', $data);
        // $('formato', $data);
        $pdf->setPaper('A4', 'portrait');
        $font = $pdf->getFontMetrics()->get_font("helvetica", "bold");
        // $pdf->getCanvas()->page_text(520, 810, "Pag. {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0, 0, 0));
        return $pdf->stream();
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
}
