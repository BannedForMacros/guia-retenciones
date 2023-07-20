<?php

namespace App\Http\Controllers\Guia;

use App\Http\Controllers\Controller;
use App\Models\GuiaSalida;
use App\Models\GuiaSalidaDetalle;
use Exception;
use Faker\Provider\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $list = GuiaSalida::where('activo',1)->get();
        // dd($list);
        return view('guia.salida.index', compact('list'));
    }

    public function listarGuias(Request $request)
    {
        $fechaInicio = $request->post('fecha_inicio');
        $fechaFin = $request->post('fecha_fin');

        
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
        $listVendedores = Http::get('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerTrabajador?CodigoTrabajador=1')->object()->trabajador;
        $getVendedor = $listVendedores[0];
        // dd($getVendedor);
        // dd($getVendedor);
        // dd($listAlmacenes);
        // $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();
        $listArticulos = array();
        // $listClientes = Http::post(route('simulacion.ObtenerClientes'), [])->object();
        $listClientes = [];
        // dd($listClientes);
        $listVehiculos = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerVehiculo', ['valor' => '', 'tipo' => 4])->object()->vehiculos;
        $listChoferes = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerChoferes', ['nombrechofer' => ''])->object()->choferes;
        // dd($listChoferes);

        return view('guia.salida.create', compact('listProveedores', 'listFormasPago', 'listTipoOperacion', 'listPrecios', 'listAlmacenes', 'listArticulos', 'listClientes', 'getVendedor', 'listVehiculos', 'listChoferes'));
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

    public function listarProveedores(Request $request)
    {
        $valor = trim($request->get('term'));
        $tipo = 3;//busqueda por razon social
        // dd($request->all());
        if (strlen($valor) > 2) {
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
    public function listarClientes(Request $request)
    {
        $valor = trim($request->get('term'));
        $tipo = 4;//busqueda por razon social
        // dd($request->all());
        if (strlen($valor) > 2) {
            $listClientes = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/obtenerCliente', 
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
        $valor = trim($request->get('term'));
        $tipo = 1;//busqueda por nombre
        // dd($request->all());
        if (strlen($valor) >= 1) {
            $listItems = Http::post('http://161.132.192.240:88/ApiDMK/GREDMK/ObtenerTransportista', 
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
