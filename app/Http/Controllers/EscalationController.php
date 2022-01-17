<?php

namespace App\Http\Controllers;

use App\Models\Escalation;
use App\Models\Client;
use App\Models\ClientNew;
use App\Models\Tray;
use Illuminate\Http\Request;
use App\Services\PqrsService;
use Log;
use App\Http\Controllers\ClientNewController;

class EscalationController extends Controller
{
    private $pqrsService;

    public function __construct(){
        $this->middleware('auth');
        $this->pqrsService = new PqrsService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $trays = Escalation::all();

        if(!$trays) {
            return $this->errorResponse('No se encontraron escalamientos',404);
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

        $tray = Escalation::create($data);
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
        $tray = Escalation::whereId($tray)->first();
        if(!$tray) return $this->errorResponse('Bandeja no encontrada', 404);

        $data = $request->all();

        Escalation::whereId($tray)->update($data);
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
        $tray = Escalation::findOrFail($tray);
        $tray->state = 0;
        $tray->update();

        return $this->successResponse('Bandeja eliminada con exito');
    }

    public function validateScalation(Request $request)
    {
        $form_id = $request->form_id;
        $form = json_decode($request->form, true);

        $scalations = Escalation::where('form_id', $form_id)->get();

        foreach($scalations as $scalation){
            if($scalation){
                //campos validados
                $validated_fields = 0;
                //iterar cada uno de los campos a validar
                foreach ($scalation->fields as $compare) {
                    //iterar secciones de formulario
                    foreach ($form as $form_section) {
                        //iterar campos del formulario
                        foreach ($form_section['fields'] as $form_field) {
                            //hacer interseccion de campos de formulario con los campos a validar
                            $compare_values = count(array_intersect_assoc($compare, $form_field));
                            // si hay interseccion de tanto el id como el value en campo a validar y en campo de formularion entonces esta validado
                            if ($compare_values == 2) {
                                $validated_fields +=1;
                            }
                        }
                    }
                }
                //dd($form['sections'][0]['fields']);
                // revisar que todos los campos se hayan validado correctamente
                if($validated_fields == count($scalation->fields)){

                    if(json_decode($request->client_id)){
                        // si se envia el id del cliente en el request usar esa info
                        $client_json = json_encode(ClientNew::findOrFail($request->client_id));
                    } else {
                        //si no se envia en el request buscar en el formulario la informacion del cliente
                        $uniqueClient = new \stdClass();
                        foreach ($form[0]['fields'] as $value) {
                            if (isset($value['client_unique'])){
                                $uniqueClient->id = $value['id'];
                                $uniqueClient->value = $value['value'];
                            }
                        }
                        $clientNewRequest = new Request();
                        $clientNewRequest->replace([
                            "form_id" => $form_id,
                            "unique_indentificator" => json_encode($uniqueClient),
                        ]);
                        $clientNewController = new ClientNewController();
                        $client_json = json_encode($clientNewController->index($clientNewRequest));
                    }

                    $form_data = (object) ['sections' => json_decode($request->form)];

                    $this->pqrsService->createEscalation($scalation->asunto_id, $scalation->estado_id, $client_json, 1, json_encode($form_data), null, 'hola');
                    // return $this->successResponse('Peticion escalada');
                }
            }
        }


        return $this->successResponse('Peticion procesada');
    }
}
