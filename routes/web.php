<?php

use App\Http\Controllers\Guia\GuiaIngresoController;
use App\Http\Controllers\Guia\GuiaSalidaController;
use App\Http\Controllers\GuiaRemisionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::controller(GuiaRemisionController::class)->group(function(){

    Route::resource('guias', GuiaRemisionController::class)->except('update');
});

Route::controller(GuiaSalidaController::class)->group(function (){

    Route::post('guiasalida/agregarItem', 'agregarItem')->name('guiasalida.agregarItem');
    Route::get('guiasalida/listarArticulos', 'listarArticulos')->name('guiasalida.listarArticulos');
    Route::get('guiasalida/listarClientes', 'listarClientes')->name('guiasalida.listarClientes');
    Route::get('guiasalida/listarTransportistas', 'listarTransportistas')->name('guiasalida.listarTransportistas');
    Route::get('guiasalida/listarProveedores', 'listarProveedores')->name('guiasalida.listarProveedores');
    Route::post('guiasalida/store', 'store')->name('guiasalida.store');

    Route::resource('guiasalida', GuiaSalidaController::class)->parameter('guiasalida', 'guia')->except('update');
});
Route::controller(GuiaIngresoController::class)->group(function (){

    Route::post('guiaingreso/agregarItem', 'agregarItem')->name('guiaingreso.agregarItem');

    Route::resource('guiaingreso', GuiaIngresoController::class)->parameter('guiaingreso', 'guia')->except('update');
});
