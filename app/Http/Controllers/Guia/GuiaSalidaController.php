<?php

namespace App\Http\Controllers\Guia;

use App\Http\Controllers\Controller;
use App\Models\GuiaSalida;
use App\Models\GuiaSalidaDetalle;
use Exception;
use Faker\Provider\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        $list = GuiaSalida::where('activo',1)->get();
        // dd($list);
        return view('guia.salida.index', compact('list'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $listProveedores = Http::post(route('simulacion.ObtenerProveedores'), [])->object();
        // $listFormasPago = Http::post(route('simulacion.ObtenerFormasPago'), [])->object();
        $listFormasPago = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerFormasPago')->object()->formasdePago;
        // $listTipoOperacion = Http::post(route('simulacion.ObtenerOperaciones'), [])->object();
        $listTipoOperacion = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerOperacion')->object()->operaciones;
        // dd($listTipoOperacion);
        // $listAlmacenes = Http::post(route('simulacion.ObtenerAlmacenes'), [])->object();
        $listAlmacenes = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerAlmacenes')->object()->almacenes;
        $listPrecios = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerSucursalPrecio')->object()->listasPrecio;
        $getVendedor = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerTrabajador?CodigoTrabajador=1')->object()->trabajador;
        // dd($listAlmacenes);
        // $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();
        $listArticulos = array();
        $listClientes = Http::post(route('simulacion.ObtenerClientes'), [])->object();
        // dd($listClientes);

        return view('guia.salida.create', compact('listProveedores', 'listFormasPago', 'listTipoOperacion', 'listPrecios', 'listAlmacenes', 'listArticulos', 'listClientes', 'getVendedor'));
    }

    public function listarArticulos(Request $request)
    {
        $valor = trim($request->get('term'));
        $tipoconsulta = 4;
        $codestacion = $request->get('codestacion');
        $codalmacen = $request->get('codalmacen');
        $codlistaprecio = $request->get('codlistaprecio');
        // dd($request->all());
        if (strlen($valor) > 2) {
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
            $tr = "
                <tr
                    data-producto_id = '{$producto_id}'
                    data-precio_unitario = {$precio_publico}
                    data-descripcion = '{$descripcion}'
                    data-codigo = '{$cod_plu}'
                >
                    <td class='align-middle'>{$codigo_barra}</td>
                    <td class='align-middle'>{$producto_id}</td>
                    <td class='align-middle'>{$cod_plu}</td>
                    <td class='align-middle'>{$descripcion}</td>
                    <td class='align-middle'><span name='span_precio'>{$precio_publico}</span></td>
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
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->post());
        $datos = $request->post();
        $detalle = json_decode($request->post('detalle'));
        unset($datos['detalle']);
        // dd($datos);
        $procede = true;
        $msj = "Guia de Salida registrada";
        $msj_tipo = "success";
        $log = "";
        $datos['numero'] = '0001';
        $datos['fecha_emision'] = date('Y-m-d');
        $datos['hora_emision'] = date('H:i');
        $url_redirect = route('guiasalida.index');

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

        // dd($guia);
        if ($procede == true) {
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
