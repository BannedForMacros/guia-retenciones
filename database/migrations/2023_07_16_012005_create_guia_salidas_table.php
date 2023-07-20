<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuiaSalidasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guia_salidas', function (Blueprint $table) {
            $table->id();
            $table->string('serie');
            $table->string('numero');
            $table->date('fecha_emision');
            $table->string('hora_emision');
            $table->string('pedido_serie')->nullable();
            $table->string('pedido_numero')->nullable();
            $table->integer('vendedor_id')->nullable();
            $table->string('vendedor_nombre')->nullable();
            $table->integer('proveedor_id')->nullable();
            $table->string('proveedor_nombre')->nullable();
            $table->string('proveedor_ruc')->nullable();
            $table->integer('cliente_id');
            $table->string('cliente_razon_social')->nullable();
            $table->string('cliente_nro_documento')->nullable();
            $table->string('cliente_documento_tipo_nombre')->nullable();
            $table->string('cliente_direccion')->nullable();
            $table->integer('divisa_id');
            $table->string('divisa_nombre')->nullable();
            $table->integer('forma_pago_id');
            $table->string('forma_pago_nombre')->nullable();
            $table->integer('codlistaprecio');
            $table->integer('tipo_operacion_id');
            $table->string('tipo_operacion_nombre')->nullable();
            $table->string('codalmacen');
            $table->string('almacen_nombre')->nullable();

            // datos transporte
            $table->integer('transportista_id');
            $table->string('transportista_ruc')->nullable();
            $table->string('transportista_nombre')->nullable();
            $table->string('transportista_direccion')->nullable();

            $table->string('chofer_dni')->nullable();
            $table->string('chofer_brevete')->nullable();
            $table->string('chofer_nombre')->nullable();

            $table->string('vehiculo_placa')->nullable();
            $table->string('vehiculo_marca')->nullable();



            $table->decimal('monto_descuento');
            $table->decimal('importe_sin_igv');
            $table->decimal('monto_igv');
            $table->decimal('total_venta');
            $table->string('comentario')->nullable();
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guia_salidas');
    }
}
