<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Circuits;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CircuitsImport;
use Illuminate\Support\Str;

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
        try {
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
        } catch (\Throwable $th) {
            return $this->errorResponse("Error al guardar la información.", $th,500);
        }
    }

    /**
     * Method that imports the circuits from an excel
     * @author:  Javier Castañeda
     * Fecha creación:  2022/08/25
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importCircuits(Request $request) {
        try {
            $this->validate($request, [
                'file' => 'required',
                'campaign_id' => 'required'
            ]);

            $excelData = Excel::toArray(new CircuitsImport, $request->file('file'))[0] ?? [];
            foreach ($excelData as $item) {
                $response = $this->validateKey($item[0]);
                //dd($response);
                if($response == false) {
                    $circuit = new Circuits;
                    $circuit->name = $item[0];
                    $circuit->key = $response;
                    $circuit->campaign_id = $request->campaign_id;
                    $circuit->save();
                }
            }
            return $this->successResponse(['msg' => "Proceso realizado correctamente"]);
        } catch (\Throwable $th) {
            return $this->errorResponse("Error al importar la información.", 500);
        }
    }

    public function validateKey ($item){
        $key = $item;
        $key = str_replace('(', '', $key);
        $key = str_replace(')', '', $key);
        $toReplace = ['#',' '];
        foreach($toReplace as $element) {
            $key = str_replace($element, '-', $key);
        }
        $response= Circuits::where('key', Str::lower($key))->get();
        if(!empty($response)){
            return Str::lower($key);
        } else {
            return false;
        }

    }
}
