<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

class ParametroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $list = array(
            0 => array('id' => '1', 'nombre' => 'credencial', 'valor' => 'Dhu9pzf/dAqGR59wfDNZGBq+e7GmHzq//0xioh4WU4sGXABdPPg3seetajngMEED8p25eYiiFoprSSFeJJRdlg=='),
            1 => array('id' => '2', 'nombre' => 'ruc_entiedad', 'valor' => '20369872274'),
            2 => array('id' => '3', 'nombre' => 'razon_social_entidad', 'valor' => 'Franco Supermercado E.I.R.L.'),
            3 => array('id' => '4', 'nombre' => 'direccion_entiedad', 'valor' => 'jr. jose sagobal 1200 BR San Sebastian'),
            4 => array('id' => '5', 'nombre' => 'telefonos', 'valor' => '-'),
            5 => array('id' => '6', 'nombre' => 'api_datos', 'valor' => 'http://161.132.192.240:88/ApiDMK/GREDMK'),
            6 => array('id' => '7', 'nombre' => 'api_facturacion', 'valor' => 'http://161.132.192.240:8180/api/Guia21'),
        );

        foreach ($list as $item) {
            Parametro::create($item);
        }
    }
}
