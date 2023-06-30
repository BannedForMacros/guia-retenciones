<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Faker\Factory as Faker;

class SimulacionController extends Controller
{
    public function ObtenerProveedores(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/proveedores.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerAlmacenes(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/almacenes.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerChoferes(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/choferes.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerOperaciones(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/operaciones.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerListaPrecios(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/lista_precios.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerFormasPago(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/formas_pago.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerArticulos(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/articulos.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerTrabajadores(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/trabajadores.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerTransportistas(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/transportistas.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerVehiculos(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/vehiculos.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerClientes(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/clientes.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerTiposCambio(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/tipo_cambio.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }

    public function ObtenerPedidoClientes(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/pedido_clientes.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }

    public function ObtenerDetalleOrden(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/detalle_orden.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }
    public function ObtenerSeries(Request $request)
    {
        try {
            $data = json_decode(Storage::get('json/series.json'), true);
            return response()->json($data);
        } catch (Exception $e) {
            //throw $th;
            return response()->json([]);
        }
    }

    public function generateData()
    {
        $faker = Faker::create();

        $data = [];

        for ($i = 0; $i < 60; $i++) {
            $item = [
                "AnioOrdenCompra" => $faker->randomNumber(1),
                "NumeroOrden" => $faker->randomNumber(5),
                "CodTransaccion" => $faker->randomNumber(5),
                "CodArticulo" => $faker->randomNumber(5),
                "Descripcion" => $faker->sentence,
                "Cantidad" => $faker->randomFloat(2, 0, 100),
                "Precio" => $faker->randomFloat(2, 0, 100),
                "Descuento" => $faker->randomFloat(2, 0, 10),
                "Condicion" => $faker->randomNumber(1),
                "Estado" => $faker->word,
                "UsuarioCreador" => $faker->userName,
                "FechaCreacion" => $faker->dateTimeThisYear()->format('Y-m-d\TH:i:s.u'),
                "UsuarioModificador" => $faker->userName,
                "FechaModificacion" => $faker->dateTimeThisYear()->format('Y-m-d\TH:i:s.u'),
                "Propiedad1" => $faker->randomNumber(1),
                "Propiedad2" => $faker->randomNumber(1),
                "Propiedad3" => $faker->randomNumber(1),
                "CodUnidad" => $faker->randomNumber(5),
                "CodArtNue" => $faker->word,
                "TipoOrdenCompra" => $faker->word,
                "Igv" => $faker->randomFloat(2, 0, 100),
                "Isc" => $faker->randomFloat(2, 0, 100),
                "cantdesp" => $faker->randomFloat(2, 0, 100),
                "Perecible" => $faker->randomNumber(1),
                "Caducidad" => $faker->date(),
                "CodBarra" => $faker->ean13,
                "CantEntre" => $faker->randomNumber(5),
                "PrecioSinIgv" => $faker->randomFloat(2, 0, 100),
                "Item" => $faker->randomNumber(5),
                "MontoDescuento" => $faker->randomFloat(2, 0, 100),
            ];

            $data[] = $item;
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
