<?php

namespace App\Http\Controllers;

use App\Models\FormAnswer;
use App\Models\Tray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return $this->successResponse([]);
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
        $data = $request['entries'];

        $tray = new Tray;
        $tray->name = $data['name'];
        $tray->form_id = $data['form_id'];
        $tray->fields = json_encode($data['fields']);
        $tray->fields_exit = json_encode($data['field_exit']);
        $tray->fields_table = json_encode($data['field_table']);
        $tray->rols = json_encode($data['rols']);
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
    public function show(Request $request, $id)
    {
        $trays = Tray::where('form_id', $id);
        
        if($request->query('showall', 0) == 0)
        {
            $trays = $trays->having(DB::raw('count(tray_id)'), '>', 0)
                ->join('form_answers_trays', 'trays.id', '=', 'form_answers_trays.tray_id')
                ->selectRaw('trays.*, count(tray_id) as count')
                ->groupBy('trays.id');
        }

        $trays = $trays->get();
        
        if(count($trays)==0) {
            return $this->successResponse([]);
        }

        // validar si el usuario actual puede visualizar trays dependiendo de su rol.
        $trays = $trays->filter(function($x){
            return count(array_intersect(auth()->user()->roles, json_decode($x->rols)));
        });

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
        $data = $request['entries'];
        $tray = Tray::whereId($id)->first();
        if(!$tray) return $this->errorResponse('Bandeja no encontrada', 404);

        $tray->name = $data['name'];
        $tray->rols = $data['rols'];
        $tray->update();

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

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function getTray(Request $request, $id)
    {
        $tray = Tray::where('id',$id)->with('form')->first();
        // dd($trays);

        if($tray==null) {
            return $this->errorResponse('No se encontro la bandeja',404);
        }

        return $this->successResponse($tray);
    }

    public function formAnswersByTray(Request $request, $id) {

        $tray = Tray::where('id',$id)
            ->firstOrFail();

        return $tray->formAnswers()->paginate($request->query('n', 5))->withQueryString();

    }

}
