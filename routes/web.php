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
    // return view('welcome');
    if (Auth::check()) {
        // dd('hola');
        return view('home');
        // return route('home');
    }
    return view('auth.login');
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
    Route::post('guiasalida/getSerie', 'getSerie')->name('guiasalida.getSerie');
    Route::post('guiasalida/listar', 'listar')->name('guiasalida.listar');
    Route::post('guiasalida/listarUbigeos', 'listarUbigeos')->name('guiasalida.listarUbigeos');
    Route::get('guiasalida/pdf/{guia}', 'pdf')->name('guiasalida.pdf');
    Route::get('guiasalida/continuar/{guia}', 'continuar')->name('guiasalida.continuar');
    Route::post('guiasalida/modalStore', 'modalStore')->name('guiasalida.modalStore');
    Route::post('guiasalida/store', 'store')->name('guiasalida.store');
    Route::post('guiasalida/facturacionElectronica', 'facturacionElectronica')->name('guiasalida.facturacionElectronica');
    
    Route::resource('guiasalida', GuiaSalidaController::class)->parameter('guiasalida', 'guia')->except('update');
});
Route::controller(GuiaIngresoController::class)->group(function (){
    
    Route::post('guiaingreso/agregarItem', 'agregarItem')->name('guiaingreso.agregarItem');
    Route::get('guiaingreso/listarProveedores', 'listarProveedores')->name('guiaingreso.listarProveedores');
    Route::get('guiaingreso/listarArticulos', 'listarArticulos')->name('guiaingreso.listarArticulos');
    Route::post('guiaingreso/listar', 'listar')->name('guiaingreso.listar');
    Route::get('guiaingreso/pdf/{guia}', 'pdf')->name('guiaingreso.pdf');
    Route::get('guiaingreso/continuar/{guia}', 'continuar')->name('guiaingreso.continuar');

    Route::resource('guiaingreso', GuiaIngresoController::class)->parameter('guiaingreso', 'guia')->except('update');
});
