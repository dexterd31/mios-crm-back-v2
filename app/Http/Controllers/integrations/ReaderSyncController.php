<?php

namespace App\Http\Controllers\integrations;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Section;
use App\Models\TokenReader;
use App\Services\SyncServices;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ReaderSyncController extends Controller
{
    private $user;
    private $enviroment;
    private $origin;
    private $tokenAccess;
    private $syncService;


    public function __construct(SyncServices $syncService){
        $this->syncService = $syncService;
        $this->enviroment = Config::get('constant.enviroments');
        $this->middleware('auth');
    }

    private function checkReaderToken($tk){
        $token = TokenReader::where('token',$tk)->first();
        if(!$token)  throw new Exception("Token Invalido", 1);

        $expireTime = Carbon::parse($token->expire_time);

        $now = Carbon::now();
        if($expireTime->timestamp < $now->timestamp ){
             throw new Exception("Token Vencido", 1);
        }

        $this->tokenAccess = $token->token_access;
    }


    private function processModuleForm($formId,$campaignId,$groupId){


        $form = $this->syncService->getFormById( $this->origin, $formId,$this->tokenAccess );
        $myForm = Form::create( [
            'name_form'=>$form->name_form,
            'filters'=> json_encode($form->filters,true) ,
            'state'=>$form->state,
            'seeRoles'=>json_encode($form->seeRoles,true),
            'group_id'=>$groupId,
            'campaign_id'=>$campaignId,
            'form_type_id'=>$form->form_type_id,
        ]);

        foreach ($form->section as $key => $section) {
            Section::create( [
                'name_section'=>$section->name_section,
                'type_section'=>$section->type_section,
                'fields'=>json_encode($section->fields ,true),
                'form_id'=>$myForm->id,
                'collapse'=>$section->collapse,
            ] );
        }



    }

    public function syncForms(Request $request){
        if( !isset( $request->token ) || empty($request->token) ) throw new Exception('El token de lectura (token) es un valor requerido');

         $this->checkReaderToken($request->token);

        /**Section para validaciones */
        if( !isset( $request->origin ) || empty($request->origin) ) throw new Exception('origin es un valor requerido');
        if( !isset($request->modules) || empty($request->modules) ) throw new Exception('modules es un valor requerido');
        if( !is_string( $request->modules ) || empty($request->modules) ) throw new Exception('modules debe ser de tipo string con la siguiente estructura (forms,sections)');

        /**
         * Section para validar ambientes
         */
        $origin = $this->enviroment[$request->origin];

        if( !isset( $origin  ) ) throw new Exception('El Origen (origin) de los datos es invalido');

        if( ! isset($request->formId) || empty($request->formId) ) throw new Exception('formId es un valor requerido');
        if( ! isset($request->campaignId) || empty($request->campaignId) ) throw new Exception('campaignId es un valor requerido');
        if( ! isset($request->groupId) || empty($request->groupId) )  throw new Exception('groupId es un valor requerido');


        $modules = explode( ',', $request->modules );

        $this->origin = $origin['crm'];
        foreach ($modules as $key => $module) {

            if($module == 'form') $this->processModuleForm( $request->formId,$request->campaignId,$request->groupId );
        }


        return 'Formulario creado con exito';



    }
}
