<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\FormAnswer;
use App\Models\StateForm;
use Helpers\MiosHelper;

date_default_timezone_set('America/Bogota');

class StateFormController extends Controller
{
    public function save(Request $request, MiosHelper $miosHelper) {
        // Recoger los datos por post 
        $json_body = json_decode($request->getContent());
        if (!empty($json_body)) {
            //Guardar una bandeja
            $tray = new StateForm();
            $tray->name             = $json_body->name;
            $tray->permissions      = json_encode($json_body->permissions);
            $tray->filters          = json_encode($json_body->filters);
            $tray->approval         = $json_body->approval;
            $tray->observation      = $json_body->observation;
            $tray->status           = true;
            $tray->form_id          = strval($json_body->form_id);
            $tray->save();
            $data = $miosHelper->jsonResponse(true, 200, 'trys', $tray);

        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message','Faltan campos por diligenciarse');
        }
        return response()->json($data, $data['code']);
    }

    public function list(MiosHelper $miosHelper, $form_id) {
        
        $where = [ 'form_id' => $form_id, 'status' => true];
        $stateForm = StateForm::where($where)->paginate(10);
        if (empty($stateForm)) {
            $data = $miosHelper->jsonResponse(false, 404, 'message','No se en contraron registros');
        } else {
            $data = $miosHelper->jsonResponse(true, 200, 'trys', $stateForm);
        }
        return response()->json($data, $data['code']);
    }

    public function get (MiosHelper $miosHelper, $id) {
        $stateForm = StateForm::where('id', $id)->first()->load('form');
        $data = $miosHelper->jsonResponse(true, 200, 'try', $stateForm);
        return response()->json($data, $data['code']);
    }

    public function update(Request $request, MiosHelper $miosHelper, $id) {
        // Recoger los datos por post 
        $json_body = json_decode($request->getContent(), true);
        if (!empty($json_body)) {
            //Eliminar lo que no queremos acrualizar 
            unset($json_body['id']);
            unset($json_body['created_at']);

            // Conseguir la bandeja
            $stateForm = StateForm::where('id', $id)->first();
            if (!empty($stateForm) && is_object($stateForm)) {
                $stateForm = StateForm::where('id', $id)->update($json_body);
                $data = $miosHelper->jsonResponse(true, 200, 'try', $json_body);
            } else {
                $data = $miosHelper->jsonResponse(false, 404, 'message','No se encontro la bandeja');
            }

        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message','Faltan campos por diligenciarse');
        }
        return response()->json($data, $data['code']);
    }

    public function delete(MiosHelper $miosHelper, $id) {
        // Conseguir la bandeja
        $tray = new StateForm();
        $stateForm = StateForm::where('id', $id)->first();
        $tray = json_decode($stateForm, true);
        $tray['status'] = false;
        //Eliminar lo que no queremos acrualizar 
        unset($tray['id']);
        unset($tray['permissions']);
        unset($tray['approval']);
        unset($tray['observation']);
        unset($tray['created_at']);
        unset($tray['updated_at']);
        unset($tray['form_id']);

        if (!empty($stateForm) && is_object($stateForm)) {
            $stateForm = StateForm::where('id', $id)->update($tray);
            $data = $miosHelper->jsonResponse(true, 200, 'try', $tray);
        } else {
            $data = $miosHelper->jsonResponse(false, 404, 'message','No se encontro la bandeja');
        }

        return response()->json($data, $data['code']);
    }

    public function trayQuery(MiosHelper $miosHelper, $id){
        $stateForm = StateForm::where('id', $id)->first();
        $filterFilds = $stateForm->filters;
        var_dump($filterFilds);
    }
}
