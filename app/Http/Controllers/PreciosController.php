<?php

namespace App\Http\Controllers;

use App\Models\Precios;
use App\Models\Productos;
use App\Models\Unidades;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Auth;

class PreciosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:precios.listar')->only('index');
        $this->middleware('can:precios.guardar')->only('guardarPrecios');
        $this->middleware('can:precios.actualizar')->only('actualizarPrecios');
        $this->middleware('can:precios.eliminar')->only('eliminarPrecios');
    }

    public function index(Request $request)
    {
        $productos = Productos::all();
        $unidades = Unidades::all();
        if ($request->ajax()) {
            return DataTables::of(Precios::with('productos', 'unidades')->where('estado', 1)->get())->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $btn = "";
                    if (Auth::user()->can('precios.actualizar')) {
                        $btn = '<button type="button"  class="editbutton btn btn-success" style="color:white" onclick="buscarId(' . $data->id . ',1)" data-bs-toggle="modal"
                        data-bs-target="#modalGuardarForm"><i class="fa-solid fa-pencil"></i></button>';
                    }
                    if (Auth::user()->can('precios.eliminar')) {
                        $btn .= "&nbsp";
                        $btn .= '<button type="button"  class="deletebutton btn btn-danger" onclick="buscarId(' . $data->id . ',2)"><i class="fas fa-trash"></i></button>';
                    }
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('vistas.backend.precios.precios', compact('productos', 'unidades'));
    }

    public function peticionesAction(Request $request)
    {
        $GUARDAR_PRECIOS = 1;
        $ACTUALIZAR_PRECIOS = 2;
        $ELIMINAR_PRECIOS = 3;
        try {
            // buscar 001
            // crear 002
            // editar 003
            // eliminar 004
            switch ($request->accion) {
                case $GUARDAR_PRECIOS:
                    $respuesta = $this->guardarPrecios($request->all());
                    return $respuesta;
                    break;
                case $ACTUALIZAR_PRECIOS:
                    $respuesta = $this->actualizarPrecios($request->all());
                    return $respuesta;
                    break;
                case $ELIMINAR_PRECIOS:
                    $respuesta = $this->eliminarPrecios($request->all());
                    return $respuesta;
                    break;
            }
        } catch (\Exception $e) {
            $respuesta = array(
                'mensaje'      => $e->getMessage(),
                'estado'      => 0,
            );
            return $respuesta;
        }
    }

    public function guardarPrecios($datos)
    {
        // dd($datos);
        $aErrores = array();
        DB::beginTransaction();
        if ($datos['idunidades'] == "") {
            $aErrores[] = '- Escoja la unidad';
        }
        if ($datos['idproductos'] == "") {
            $aErrores[] = '- Escoja el producto';
        }
        if ($datos['precio'] == "") {
            $aErrores[] = '- Digite el precio del producto según la unidad';
        }
        if (count($aErrores) > 0) {
            throw new \Exception(join('</br>', $aErrores));
        }
        try {

            $validacion = Precios::where([
                // ['precio', $datos['precio']],
                ['producto_id', $datos['idproductos']],
                ['unidades_id', $datos['idunidades']],
                ['estado', 0]
            ])->first();
            if ($validacion) {
                $validacion->update(['precio' => $datos['precio']]);
                $validacion->update(['estado' => 1]);
            }
            $validacionProducto = Precios::where([
                ['producto_id', $datos['idproductos']],
            ])->get();
            $validacionUnidad = Precios::where([
                ['unidades_id', $datos['idunidades']],
            ])->get();
            if (count($validacionProducto) > 0 && count($validacionUnidad) > 0) {
                $aErrores[] = '- El precio de este producto ya está asignado a esta unidad';
            } else {
                $nuevoPrecio = new Precios();
                $nuevoPrecio->precio = $datos['precio'];
                $nuevoPrecio->producto_id = $datos['idproductos'];
                $nuevoPrecio->unidades_id = $datos['idunidades'];
                $nuevoPrecio->estado = 1;
                $nuevoPrecio->created_at = \Carbon\Carbon::now();
                $nuevoPrecio->updated_at = \Carbon\Carbon::now();
                $nuevoPrecio->save();
            }

            if (count($aErrores) > 0) {
                $respuesta = array(
                    'mensaje'      => $aErrores,
                    'estado'      => 0,
                );
                return response()->json($respuesta);
            } else {
                DB::commit();
                $respuesta = array(
                    'mensaje'      => "",
                    'estado'      => 1,
                );
                return response()->json($respuesta);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw  $e;
        }
    }

    public function actualizarPrecios($datos)
    {
        $aErrores = array();
        DB::beginTransaction();
        if ($datos['idunidades'] == "") {
            $aErrores[] = '- Escoja la unidad';
        }
        if ($datos['idproductos'] == "") {
            $aErrores[] = '- Escoja el producto';
        }
        if ($datos['precio'] == "") {
            $aErrores[] = '- Digite el precio del producto según la unidad';
        }
        if (count($aErrores) > 0) {
            throw new \Exception(join('</br>', $aErrores));
        }
        try {
            $actualizarPrecio = Precios::findOrFail($datos['id']);;
            $actualizarPrecio->precio = $datos['precio'];
            $actualizarPrecio->producto_id = $datos['idproductos'];
            $actualizarPrecio->unidades_id = $datos['idunidades'];
            $actualizarPrecio->save();

            if (count($aErrores) > 0) {
                $respuesta = array(
                    'mensaje'      => $aErrores,
                    'estado'      => 0,
                );
                return response()->json($respuesta);
            } else {
                DB::commit();
                $respuesta = array(
                    'mensaje'      => "",
                    'estado'      => 1,
                );
                return response()->json($respuesta);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw  $e;
        }
    }

    public function eliminarPrecios($datos)
    {
        //dd($datos['id']);
        $aErrores = array();
        DB::beginTransaction();
        if ($datos['id'] == "") {
            $aErrores[] = '- No existe el precio a eliminar';
        }
        if (count($aErrores) > 0) {
            throw new \Exception(join('</br>', $aErrores));
        }
        try {
            $eliminarPrecio = Precios::findOrFail($datos['id']);
            $eliminarPrecio->update(['estado' => 0]);
            DB::commit();
            $respuesta = array(
                'mensaje'      => "",
                'estado'      => 1,
            );
            return response()->json($respuesta);
        } catch (\Exception $e) {
            DB::rollback();
            throw  $e;
        }
    }
}
