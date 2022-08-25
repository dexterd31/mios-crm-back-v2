<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Circuits;


class CircuitsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

        /**
     * Display the specified resource.
     * @author:  Javier Castañeda
     * Fecha creación:  2022/08/24
     * @param  \App\Models\Room  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            if (Circuits::find($id)){
                $response= Circuits::find($id);
                return $this->successResponse($response, $id);
            }
            else{
                return $this->errorResponse('El recurso solicitado no existe', 400);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse('Ocurrio un error al mostrar el recurso solicitado. Detalle: '.$th, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @author:  Javier Castañeda
     * Fecha creación:  2022/08/24
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        $key = $request->name;
        $key = str_replace('(', '', $key);
        $key = str_replace(')', '', $key);
        $toReplace = ['#',' '];
        foreach($toReplace as $item) {
            $key = str_replace($item, '-', $key);
        }
        $circuit = new Circuits;
        $circuit->name = $request->name;
        $circuit->key = $key;
        $circuit->campaign_id = $request->campaign_id;
        $circuit->save();
        return $this->successResponse("Circuito guardado con exito");

    }
}
