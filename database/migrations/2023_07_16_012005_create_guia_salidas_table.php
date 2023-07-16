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
            $table->integer('proveedor_id')->nullable();
            $table->integer('cliente_id');
            $table->integer('divisa_id');
            $table->integer('forma_pago_id');
            $table->integer('codlistaprecio');
            $table->integer('tipo_operacion_id');
            $table->string('codalmacen');
            $table->decimal('monto_descuento');
            $table->decimal('importe_sin_igv');
            $table->decimal('monto_igv');
            $table->decimal('total_venta');
            $table->string('comentario');
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
