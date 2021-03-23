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
        $data = $request->all();
        $data['state'] = 1;

        $tray = Tray::create($data);
        $tray->save();

        return $this->successResponse('Bandeja creada con exito');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function show(Tray $tray)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tray $tray)
    {
        $tray = Tray::whereId($tray)->first();
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
    public function destroy(Tray $tray)
    {
        tray = Tray::findOrFail($tray);
        tray->state = 0;
        tray->update();

        return $this->successResponse('Bandeja eliminada con exito');
    }
}
