<?php

namespace App\Http\Controllers\Guia;

use App\Http\Controllers\Controller;
use App\Models\GuiaEstado;
use App\Models\GuiaIngreso;
use App\Models\GuiaIngresoDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
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


        foreach ($list as $key => $value) {
            $list[$key]->estado_nombre = GuiaEstado::find($value->guia_estado_id)->nombre;
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
        $listProveedores = [];
        $listFormasPago = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerFormasPago')->object()->formasdePago;
        $listTipoOperacion = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerOperacion')->object()->operaciones;
        $listAlmacenes = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerAlmacenes')->object()->almacenes;
        // dd($listAlmacenes);
        // $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();
        $listArticulos = [];
        // dd($listArticulos);
        $listVendedores = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerTrabajador?CodigoTrabajador=-1')->object()->trabajador;

        // foreach ($listVendedores as $key => $value) {
        //     $listVendedores[$key]->selected = '';
        // }
        // $getVendedor = $listVendedores[0];
        return view('guia.ingreso.create', compact('listProveedores', 'listFormasPago', 'listTipoOperacion', 'listAlmacenes', 'listArticulos', 'listVendedores'));
    }

    public function continuar(GuiaIngreso $guia)
    {
        $listProveedores = [];
        // dd($guia);
        // dd($listProveedores);
        if ($guia->proveedor_id != null) {
            $listProveedores = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerProveedores', ['valor' => $guia->proveedor_id, 'tipo' => 1])->object()->proveedores;
        }

        $listFormasPago = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerFormasPago')->object()->formasdePago;
        $listTipoOperacion = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerOperacion')->object()->operaciones;
        // dd($listTipoOperacion);
        foreach ($listTipoOperacion as $key => $value) {
            $selected = "";
            if ($guia->tipo_operacion_id == $value->tipoOperacion) {
                $selected = "selected";
            }
            $listTipoOperacion[$key]->selected = $selected;
        }
        $listAlmacenes = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerAlmacenes')->object()->almacenes;
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
        $listVendedores = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerTrabajador?CodigoTrabajador=-1')->object()->trabajador;
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


    public function agregarItem(Request $request)
    {
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
        $valor = trim($request->get('term'));
        $tipo = $request->get('tipo');//busqueda por razon social
        // dd($request->all());
        $maximo = 0;
        if ($tipo == 3) {
            $maximo = 2;
        }
        if (strlen($valor) > $maximo) {
            $listItems = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerProveedores', 
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

    public function listarArticulos(Request $request)
    {
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
            $listArticulos = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerArticulo', 
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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
        $listSeries = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/obtenerSeriesNumerosGuia')->object()->serienumeros;
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
                    $storeRemoto = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/InsertGuiaDMK', $body)->object();
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
    public function pdf(GuiaIngreso $guia)
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
            'total_igv' => $guia->monto_igv,
            'total' => $guia->total_venta,
        );

        $detalle = GuiaIngresoDetalle::where('guia_ingreso_id', $guia->id)->get();
        // dd($detalle);
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
}
