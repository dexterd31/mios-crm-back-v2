<?php

namespace App\Http\Controllers;

use App\Managers\OutboundManagementManager;
use App\Models\Form;
use App\Models\OutboundManagementAttachment;
use App\Services\NotificationsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OutboundManagementController extends Controller
{
    protected $outboundManagementManager;

    public function __construct(OutboundManagementManager $outboundManagementManager)
    {
        $this->middleware('auth');
        $this->outboundManagementManager = $outboundManagementManager;
    }

    public function indexByForm($formId, Request $request)
    {
        try {
            $filterOptions = $request->filter_options ?? [];
    
            $outboundManagement = $this->outboundManagementManager->listManagement($formId, (array) $filterOptions);
    
            return response()->json($outboundManagement);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create($formId)
    {
        try {
            $form = Form::find($formId);
    
            $tags = $form->tags()->get(['id', 'name']);
    
            $fields = [];
    
    
            $form->section()->get('fields')->each(function ($section) use (&$fields) {
                $sectionFields = json_decode($section->fields);
                foreach ($sectionFields as $field) {
                    $fields[] = ['id' => $field->id, 'name' => $field->label];
                }
            });
    
            if ($form->cutomFields) {
                foreach ($form->cutomFields->fields as $field) {
                    $fields[] = ['id' => $field->id, 'name' => $field->label];
                }
            }
    
            $emails = (new NotificationsService)->getEmailsByCampaing(auth()->user()->rrhh->campaign_id);

            return response()->json(['tags' => $tags, 'fields' => $fields, 'emails' => $emails]);

        } catch (Exception $e) {
            Log::error("OutboundManagementController@create: {$e->getMessage()}");
            return response()->json(['error' => 'Ocurrio un error al traer la informacion para crear la gestion, por favor comuniquese con el administrador del sistema.'], 500);
        }

    }

    public function show($outboundManagementId)
    {
        try {
            $outboundManagement = $this->outboundManagementManager->showOutboundManagement($outboundManagementId);
    
            return response()->json($outboundManagement, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $fileKeys = array_keys($request->file());
    
            if (count($fileKeys)) {
                $input = $request->except(...$fileKeys);
            } else {
                $input = $request->all();
            }
    
            $this->outboundManagementManager->save($input, $request->file());
    
            return response()->json(['success' => 'OK'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendDiffusion(Request $request)
    {
        try {
            $outboundManagement = $this->outboundManagementManager->save($request->all());
    
            $this->outboundManagementManager->createDiffusion($outboundManagement);
            
            return response()->json(['success' => 'OK'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteAttachment($id)
    {
        try {
            $this->outboundManagementManager->destroyAttachment($id);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadAttachment($id)
    {
        try {
            $outboundManagementAttachment = OutboundManagementAttachment::find($id);
            return response()->download(storage_path("app/$outboundManagementAttachment->path"), $outboundManagementAttachment->name);
        } catch (\Throwable $th) {
            Log::error("OutboundManagementController@downloadAttachment: {$th->getMessage()}");
            return response()->json(['error' => 'Error al descargar el adjunto, por favor comuniquese con el administrador.'], 500);
        }
    }

    public function sendEmailTest(Request $request)
    {
        (new NotificationsService)->sendEmail();
    }

}
