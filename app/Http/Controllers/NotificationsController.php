<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Notifications;
use App\Models\NotificationsAttatchment;
use App\Models\NotificationsType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\MockObject\Api;

class NotificationsController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @author Juan Pablo Camargo Vanegas
     * @desc Muestra todos los registros almacenados en notifications.
     * @return \Illuminate\Http\Response
     */
    private function index($external = true)
    {
        $notifications = Notifications::all();
        foreach ($notifications as $notification){
            $notification->activators = json_decode($notification->activators);
        }
        if(!$external){
            return $notifications;
        }
        return $this->successResponse($notifications);
    }

    /**
     * @desc Almacena la información recibida en notifications.
     * @author Juan Pablo Camargo Vanegas
     * @param  \Illuminate\Http\Request  $request
     * @param  bool $external : indica si la función será utilizada directamente en la ruta (true) o en un controlador (false)
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$external = true)
    {
        $this->validate($request,[
            'form_id' => 'required|numeric',
            'notification_type' => 'required|numeric',
            'name' => 'required|string',
            'template_to_send' => 'required|string',
            'activators' => 'required|array',
            'activators.*.id' => 'required|int',
            'activators.*.type' => 'required',
            'activators.*.value' => 'required',
        ]);
        $newNotification = Notifications::create([
            'form_id' => $request->form_id,
            'notification_type' => $request->notification_type,
            'activators' => json_encode($request->activators),
            'name' => $request->name,
            'subject' => (isset($request->subject))?$request->subject:'',
            'template_to_send' => $request->template_to_send,
            'rrhh_id' => Auth::user()->rrhh_id
        ]);
        if(!isset($newNotification->id)){
            return $this->errorResponse('No se creó la notificación',204);
        }
        if(!$external){
            return $newNotification->id;
        }
        return $this->successResponse($newNotification->id);
    }

    /**
     * @desc Muestra todos los registros almacenados según el form id.
     * @author Juan Pablo Camargo Vanegas
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showByFormId(int $formId,$external = true)
    {
        $notifications = Notifications::where('form_id',$formId)->get();
        foreach ($notifications as $notification){
            $notification->activators = json_decode($notification->activators);
        }
        if(!$external){
            return $notifications;
        }
        return $this->successResponse($notifications);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @desc retorna las notificaciones y tipo de notificaciones que contenga un formulario
     * @author Juan Pablo Camargo Vanegas
     * @param int $formId : id del formulario a consultar
     * @return \Illuminate\Http\JsonResponse : objeto con la data solicitada
     */
    public function prepareNotifications(int $formId){
        $response = new \stdClass();

        $form = Form::where('id', $formId)
                    ->with(["section" => function($q){
                            $q->where('state', '!=', 1);
                        }])
                    ->first();
        if(!$form){
            return $this->errorResponse('form not found',404);
        }
        $form->filters = json_decode($form->filters);
        $form->fields_client_unique_identificator = json_decode($form->fields_client_unique_identificator);
        $form->seeRoles = json_decode($form->seeRoles);
        for ($i = 0; $i < count($form->section); $i++) {
            unset($form->section[$i]['created_at']);
            unset($form->section[$i]['updated_at']);
            unset($form->section[$i]['form_id']);
            $form->section[$i]['fields'] = json_decode($form->section[$i]['fields']);
        }
        $notification_types = NotificationsType::all();
        if(count($notification_types) === 0){
            return $this->errorResponse('notifications type not found',404);
        }
        $notifications = Notifications::all();
        if(count($notifications) === 0){
            return $this->errorResponse('notifications not found',404);
        }
        foreach ($notifications as $notification) {
            $notification->to = json_decode($notification->to);
            $notification->activators = json_decode($notification->activators);
        }
        $response->form = $form;
        $response->notifications_type = $notification_types;
        $response->notifications = $notifications;
        return $this->successResponse($response);
    }

    /**
     * @desc almacena las notificaciones junto con sus archivos si estos son enviados
     * @author Juan Pablo Camargo Vanegas
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|void
     */
    public function saveNotifications(Request $request){
        // descomentar para fase 2
        /*if(str_contains($request->header('content-type'),'multipart/form-data')){
            $multipartRequest = [
                'activators' => json_decode($request->activators)
            ];
            if(isset($request->atatchments)) $multipartRequest['atatchments'] = json_decode($request->atatchments);
            if(isset($request->to)) $multipartRequest['to'] = json_decode($request->to);
            $request->merge($multipartRequest);
        }*/
        $this->validate($request,[
            'form_id' => 'required|numeric',
            'notification_type' => 'required|numeric',
            'name' => 'required|string',
            'body_notifications' => 'required|string',
            'activators' => 'required|array',
            'activators.*.id' => 'required|numeric',
            'activators.*.type' => 'required',
            'activators.*.value' => 'required',
        ]);
        $notificationType = NotificationsType::where('id',$request->notification_type)->get();
        if(count($notificationType) == 0){
            return $this->errorResponse('Notification type not found',404);
        }
        $request->merge([
            'template_to_send' => $request->body_notifications,
        ]);
        $savedNotification = $this->store($request,false);
        //validación de archivos adjuntos
        if(isset($request->atatchments) && !empty($request->atatchments)){
            $validAtachment = false;
            foreach ($request->atatchments as $key=>$atatchment){
                $attatchments = [];
                 // descomentar para fase 2
                /*if(Storage::exists($atatchment['route'].$atatchment['file_name'])){
                    return $this->errorResponse("route {$atatchment['route']} is invalid",400);
                }*/
                if($key == 'static'){
                    $this->validate($request,[
                        "atatchments.static.file_name" => 'required|string',
                        "atatchments.static.route" => 'required|string'
                    ]);
                    $attatchments['static_atachment'] = $atatchment['file_name'];
                    $attatchments['route_atachment'] = $atatchment['route'];
                    $validAtachment = true;
                }
                if($key == 'dinamic'){
                    $this->validate($request,[
                        "atatchments.dinamic.file_name" => 'required|array',
                        "atatchments.dinamic.route" => 'required|string'
                    ]);
                    $attatchments['dinamic_atachment'] = json_encode($atatchment['file_name']);
                    $attatchments['route_atachment'] = $atatchment['route'];
                    $validAtachment = true;
                }
                if($validAtachment){
                    $attatchments['notifications_id'] = $savedNotification;
                    NotificationsAttatchment::create($attatchments);
                }
            }
            if(!$validAtachment){
                return $this->errorResponse('atatchments has one or more invalid arguments',400);
            }
        }
        $response = new \stdClass();
        $response->message = "notificación creada exitosamente";
        $response->notificationId = $savedNotification;
        $response->notifications = $this->index(false);
        return response()->json($response);
    }


    public function sendNotifications(int $formId,$formAnswerData){
        $notifications = $this->showByFormId($formId);
        foreach ($notifications as $notification){
            $activators = array_filter($formAnswerData,function ($data){
                Log::info($data);
            });
        }
    }
}
