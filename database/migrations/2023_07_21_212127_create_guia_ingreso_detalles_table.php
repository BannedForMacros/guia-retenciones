<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuiaIngresoDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guia_ingreso_detalles', function (Blueprint $table) {
            $table->id();
            $table->integer('guia_ingreso_id');
            $table->string('codarticulo');
            $table->string('descripcion');
            $table->decimal('precio');
            $table->integer('cantidad');
            $table->decimal('importe');
            $table->decimal('porcentaje_descuento');
            $table->decimal('monto_descuento');
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
        Schema::dropIfExists('guia_ingreso_detalles');
    }
}
