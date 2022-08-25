<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Circuits;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CircuitsImport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

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
            $response = $this->validateKey($request->name);
            if($response !== false) {
                $circuit = new Circuits;
                $circuit->name = $request->name;
                $circuit->key = $response;
                $circuit->campaign_id = $request->campaign_id;
                $circuit->save();
            }
            return $this->successResponse("Circuito guardado con exito");
        } catch (\Throwable $th) {
            return $this->errorResponse("Error al guardar la información.".$th,500);
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
                if($response !== false) {
                    $circuit = new Circuits;
                    $circuit->name = $item[0];
                    $circuit->key = $response;
                    $circuit->campaign_id = $request->campaign_id;
                    $circuit->save();
                }
            }
            return $this->successResponse(['msg' => "Proceso realizado correctamente"]);
        } catch (\Throwable $th) {
            return $this->errorResponse("Error al importar la información.".$th, 500);
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
            return false;
        } else {
            return Str::lower($key);
        }

    }

         /**
     * Update the specified resource in storage.
     * @author:  Javier Castañeda
     * Fecha creación:  2022/08/25
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $Room
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try{
            $rules= [
                'id' => 'required'
            ];
            $json_body = json_decode($request->getContent(), true);
            $validator = Validator::make($request->all(), $rules);        
            
            if ($validator->fails()){
                return $this->errorResponse($validator->errors()->first(), 400);
            } else{
                $resp = Circuits::where('id', $request->id)->update($request->all());
                return $this->successResponse("Recurso editado satisfactoriamente", 200);
            }

        } catch (\Throwable $th) {
            return $this->errorResponse('Ocurrio un error al intentar crear el recurso. Detalle: '.$th, 500);
        }
    }


    public function index (Request $request) {
        try {
            if(!empty($request->campaign_id)){
                $circuit = Circuits::where('campaign_id', $request->campaign_id);
                if(!empty($request->name)) {
                    $circuit = $circuit->where('name', 'like',"%$request->name%");
                }
                if(!empty($request->state)) {
                    $circuit = $circuit->where('state', $request->state);
                } 
                $circuit = $circuit->get();

                return $this->successResponse($circuit, 200);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse('Ocurrio un error al intentar consultar el recurso. Detalle: '.$th, 500);
        }
    }

}
