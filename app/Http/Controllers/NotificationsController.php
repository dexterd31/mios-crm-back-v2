<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Notifications;
use App\Models\NotificationsAttatchment;
use App\Models\NotificationsType;
use App\Services\NotificationsService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'to' => 'required|array',
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
            'to' => json_encode($request->to),
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
        $notifications = Notifications::where('form_id',$formId)->get();
        /*if(count($notifications) === 0){
            return $this->errorResponse('notifications not found',404);
        }*/
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
            'to' => 'required|array',
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
        if(isset($request->attachments) && !empty($request->attachments)){
            $validAtachment = false;
            foreach ($request->attachments as $key=>$attachment){
                $attatchments = [];
                 // descomentar para fase 2
                /*if(Storage::exists($atatchment['route'].$atatchment['file_name'])){
                    return $this->errorResponse("route {$atatchment['route']} is invalid",400);
                }*/
                if($key == 'static'){
                    $this->validate($request,[
                        "attachments.static.file_name" => 'required|string'
                    ]);
                    $attatchments['static_atachment'] = $attachment['file_name'];
                    $attatchments['route_atachment'] = $attachment['route'].'/';
                    $validAtachment = true;
                }
                if($key == 'dynamic'){
                    $this->validate($request,[
                        "attachments.dynamic.file_name" => 'required|array'
                    ]);
                    $attatchments['dinamic_atachment'] = json_encode($attachment['file_name']);
                    $attatchments['route_atachment'] = $attachment['route'].'/';
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

    /**
     * @desc método para el envío de notificaciones según la respuesta del formulario
     * @author Juan Pablo Camargo Vanegas
     * @param int $formId
     * @param $formAnswerData
     * @return void
     */
    public function sendNotifications(int $formId,$formAnswerData){
        $notifications = $this->showByFormId($formId,false);
        if(count($notifications) == 0){
            return;
        }
        foreach ($notifications as $notification){
            $sendEmail = true;
            foreach ($notification->activators as $activator){
                $existingActivator = $this->activatorsInResponse($formAnswerData,$activator);
                if(!$existingActivator){
                    $sendEmail = false;
                }
            }
            //envío de notificación
            if($sendEmail){
                switch($notification->notification_type){
                    case 1: //email
                        $this->sendEmailNotification($formId,$notification,$formAnswerData);
                        break;
                    case 2: //sms
                        break;
                }
            }
        }
    }

    /**
     * @desc valida que el activador de la notificación se encuentre en las respuestas del formulario
     * @author Juan Pablo Camargo Vanegas
     * @param $formAnswerData
     * @param $activator
     * @return array|false
     */
    private function activatorsInResponse($formAnswerData,$activator){
        $structureAnswer = json_decode($formAnswerData->structure_answer,true);
        $activators = array_filter($structureAnswer,function ($data) use ($activator){
           return ($data['id'] == $activator->id) && strtolower($data['value']) == strtolower($activator->value);
        });
        if(count($activators) > 0){
            return $activators;
        }
        return false;
    }

    /**
     * @param $notification
     * @return void
     */
    private function sendEmailNotification($formId,$notification,$formAnswerData){
        $attatchments = [];
        $notificationService = new NotificationsService();
        $nAttatchments = NotificationsAttatchment::where('notifications_id',$notification->id)->get();
        if(count($nAttatchments) > 0){
            $dinamicAttatchments = [];
            $staticAttatchments = [];
            foreach ($nAttatchments as $attatchment){
                if(!is_null($attatchment->dinamic_atachment)){
                    array_push($dinamicAttatchments,['name' => json_decode($attatchment->dinamic_atachment),'route' => $attatchment->route_atachment]);
                }
                if(!is_null($attatchment->static_atachment)){
                    array_push($staticAttatchments,['name' => $attatchment->static_atachment,'route' => $attatchment->route_atachment]);
                }
            }
        }
        $emailBody = $notification->template_to_send;
        $to = (isset($notification->to))? json_decode($notification->to) : null;
        $formController = new FormController();
        foreach (json_decode($formAnswerData->structure_answer,true) as $data){
            $formatedAnswer = $formController->findAndFormatValues($formId,$data['id'],$data['value'],true);
            if(isset($formatedAnswer->name)){
                $data['value'] = $formatedAnswer->name;
            }else{
                $data['value'] = $formatedAnswer->value;
            }
            $emailBody =  str_replace("[[{$data['key']}]]",$data['value'],$emailBody);
            $notification->subject =  str_replace("[[{$data['key']}]]",$data['value'],$notification->subject);
            $emailBody = $this->getSignature($formAnswerData, $emailBody);
            $notification->subject = $this->getSignature($formAnswerData,$notification->subject);
            if(!is_null($to)) $to = str_replace($data['id'],$data['value'],$to);
            if(isset($dinamicAttatchments)){
                array_walk_recursive($dinamicAttatchments,function (&$attatchment) use ($data){
                    $attatchment = str_replace($data['id'],$data['value'],$attatchment);
                });
            }
        }
        $expresion = '/(\[\[\w+\]\])|(\[\[[a-z0-9-]+\]\])/m';
        $emailBody = preg_replace($expresion,'',$emailBody);
        if(isset($dinamicAttatchments) || isset($staticAttatchments)){
            foreach ($dinamicAttatchments as $attatchment){
                $attatchment['name'] = implode("",$attatchment['name']);
                $existAttachment = $this->existAttachment($attatchment['name'],$attatchment['route']);
                if(!$existAttachment) break;
                $content = Storage::get($attatchment['route'].'/'.$existAttachment);
                $attatchment['file'] = $content;
                array_push($attatchments,$attatchment);
            }
            foreach ($staticAttatchments as $attatchment){
                $existAttachment = $this->existAttachment($attatchment['name'],$attatchment['route']);
                if(!$existAttachment) break;
                $content = Storage::get($attatchment['route'].'/'.$existAttachment);
                $attatchment['file'] = $content;
                array_push($attatchments,$attatchment);
            }
        }
        $emailTemplate = view('email_templates.axaFalabellaMail',['emailBody' => $emailBody])->render();
        $notificationService->sendEmail($emailTemplate,$notification->subject,$to,$attatchments);

    }

    /**
     * Retorna el texto enviado con los datos de creacion, actualizacion y firma del agente
     * @author Edwin David Sanchez Balbin
     *
     * @param int $notificationId
     * @param string $emailBody
     * @return string
     */
    private function getSignature(object $formAnswer, string $text) : string
    {
        $signature = auth()->user()->rrhh->name;
        $createdAt = $this->formatedDate($formAnswer->created_at);
        $updatedAt = $this->formatedDate($formAnswer->updated_at);
        $text =  str_replace("[[signature_crm_2022]]",$signature,$text);
        $text =  str_replace("[[created_at]]",$createdAt, $text);
        $text =  str_replace("[[updated_at]]",$updatedAt,$text);
        return $text;
    }

    private function formatedDate(string $date){
        return Carbon::parse($date)->timezone('America/bogota')->format('Y-m-d H:i:s');
    }


    /**
     * @desc valida la existencia del archivo según su nombre y ruta (opcional)
     * @author Juan Pablo Camargo Vanegas
     * @param string $fileName: nombre del archivo con su extensión
     * @param string|null $path: rúta del archivo (en caso de que tenga más carpetas en el storage)
     * @return false|mixed|string
     */
    private function existAttachment(string $fileName, string $path = null){
            $fileName = !empty($path) ? $path.'/'.$fileName : $fileName;
            $fileData = explode('.',$fileName);
            $fileExist = glob("../storage/app/$fileData[0]*.$fileData[1]");
            if(count($fileExist) > 0){
                $fileExistData = explode('/',$fileExist[0]);
                return end($fileExistData);
            }
            return false;
    }
}
