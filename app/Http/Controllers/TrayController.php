<?php

namespace App\Http\Controllers;

use App\Models\Tray;
use Illuminate\Http\Request;

class TrayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $trays = Tray::all();

        if(!$trays) {
            return $this->errorResponse('No se encontraron bandejas',404);
        }

        return $this->successResponse($trays);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return $request;
        $data = $request['entries'];
        // $data['state'] = 1;
        // dd($request['entries']);

        $tray = new Tray;
        $tray->name = $data['name'];
        $tray->form_id = $data['form_id'];
        $tray->fields = json_encode($data['fields']);
        $tray->state = 1;
        $tray->save();

        return $this->successResponse('Bandeja creada con exito');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $trays = Tray::where('form_id', $id)->get();
        // dd($trays);

        if(count($trays)==0) {
            return $this->errorResponse('No se encontraron bandejas',404);
        }

        return $this->successResponse($trays);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $tray = Tray::whereId($id)->first();
        if(!$tray) return $this->errorResponse('Bandeja no encontrada', 404);

        $data = $request->all();

        Tray::whereId($tray)->update($data);
        return $this->successResponse('Bandeja actualizada con exito');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tray = Tray::findOrFail($id);
        $tray->state = 0;
        $tray->update();

        return $this->successResponse('Bandeja eliminada con exito');
    }
}
