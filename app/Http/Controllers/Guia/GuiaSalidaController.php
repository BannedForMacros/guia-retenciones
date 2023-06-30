<?php

namespace App\Http\Controllers\Guia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GuiaSalidaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $listProveedores = Http::post(route('simulacion.ObtenerProveedores'), [])->object();
        $listFormasPago = Http::post(route('simulacion.ObtenerFormasPago'), [])->object();
        $listTipoOperacion = Http::post(route('simulacion.ObtenerOperaciones'), [])->object();
        $listAlmacenes = Http::post(route('simulacion.ObtenerAlmacenes'), [])->object();
        $listArticulos = Http::post(route('simulacion.ObtenerArticulos'), [])->object();

        $listClientes = Http::post(route('simulacion.ObtenerClientes'), [])->object();
        // dd($listClientes);

        return view('guia.salida.create', compact('listProveedores', 'listFormasPago', 'listTipoOperacion', 'listAlmacenes', 'listArticulos', 'listClientes'));
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
// dd(count($items));
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
            $tr = "
                <tr
                    data-producto_id = '{$producto_id}'
                >
                    <td>{$codigo_barra}</td>
                    <td>{$producto_id}</td>
                    <td>{$cod_plu}</td>
                    <td>{$descripcion}</td>
                    <td>{$cantidad}</td>
                    <td>{$unidad}</td>
                    <td>
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
        //
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
