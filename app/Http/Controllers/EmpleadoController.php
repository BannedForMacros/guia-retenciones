<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Perfil;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $list = Empleado::where('activo', 1)->get();

        return view('empleados.index', compact('list'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // dd('create empleado');
        return view('empleados.create');
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

        $procede = true;
        $msj = "Empleado registrado";
        $msj_tipo = "success";
        $log = "";
        $url_redirect = "";
        try {
            $empleado = Empleado::create($datos);
            // dd($empleado);
            $url_redirect = route('empleados.edit', ['empleado' => $empleado->id]);

        } catch (Exception $e) {
            //throw $th;
            // dd($e);
            $procede = false;
            $msj = "No se pudo registrar empleado";
            $msj_tipo = "error";
            $log = "{$e}";

        }

        if ($procede == true) {
            $user = new User();
            $user->name = "{$empleado->ape_paterno} {$empleado->ape_materno}";
            // $user->email = "{$empleado->nro_documento}@mail.com";
            $user->empleado_id = $empleado->id;
            $user->perfil_id = $datos['perfil_id'];
            $user->username = $datos['usuario'];
            $user->password_alt = base64_encode($datos['password']);
            $user->password = Hash::make($datos['password']);

            try {
                $user->save();
            } catch (Exception $e) {
                // dd($e);
                $procede = false;
                $msj = "No se pudo registrar Usuario";
                $msj_tipo = "error";
                $log = "{$e}";
            }
        }

        if ($procede == false) {
            $del_empleado = Empleado::find($empleado->id);
            
            $del_empleado->delete();
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
    public function edit(Empleado $empleado)
    {
        // dd($empleado);
        $listPerfiles = Perfil::where('activo', 1)->get();

        $user = User::where('empleado_id', $empleado->id)->first();

        return  view('empleados.edit', compact('empleado', 'listPerfiles', 'user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // dd($request->post());

        $datos = $request->post();
        $id = $request->post('id');
        $procede = true;
        $msj = "Empleado actualizado";
        $msj_tipo = "success";
        $log = "";
        $url_redirect = "";

        $empleado = Empleado::find($id);

        try {
            unset($datos['id']);
            // dd($datos);
            $empleado->update($datos);
            // dd($empleado);
            $url_redirect = route('empleados.edit', ['empleado' => $empleado->id]);

        } catch (Exception $e) {
            //throw $th;
            // dd($e);
            $procede = false;
            $msj = "No se pudo actualizar empleado";
            $msj_tipo = "error";
            $log = "{$e}";

        }

        if ($procede == true) {
            $user = User::where('empleado_id', $empleado->id)->first();
            $user->name = "{$empleado->ape_paterno} {$empleado->ape_materno}";
            // $user->email = "{$empleado->nro_documento}@mail.com";
            $user->empleado_id = $empleado->id;
            $user->perfil_id = $datos['perfil_id'];
            $user->username = $datos['usuario'];
            $user->password_alt = base64_encode($datos['password']);
            $user->password = Hash::make($datos['password']);

            try {
                $user->save();
            } catch (Exception $e) {
                // dd($e);
                $procede = false;
                $msj = "No se pudo registrar Usuario";
                $msj_tipo = "error";
                $log = "{$e}";
            }
        }

        return response()->json(['procede' => $procede, 'msj' => $msj, 'msj_tipo' => $msj_tipo, 'log' => $log, 'url_redirect' => $url_redirect]);
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
