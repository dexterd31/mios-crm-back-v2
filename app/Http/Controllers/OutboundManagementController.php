<?php

namespace App\Http\Controllers;

use App\Managers\OutboundManagementManager;
use App\Models\Form;
use App\Models\OutboundManagement;
use App\Models\Section;
use App\Services\NotificationsService;
use Exception;
use Illuminate\Http\Request;

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
        $filterOptions = $request->filter_options ?? [];

        $outboundManagement = $this->outboundManagementManager->listManagement($formId, (array) $filterOptions);

        return response()->json($outboundManagement);
    }

    public function create($formId)
    {
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
    }

    public function show($outboundManagementId)
    {
        $outboundManagement = OutboundManagement::find($outboundManagementId)->load('tags');
        
        if ($outboundManagement->channel == 'SMS') {
            $content = $outboundManagement->settings->sms->message_content;

            $outboundManagement->settings->sms->message_content = $this->replaceContent($outboundManagement->form_id, $content);

        } else if ($outboundManagement->channel == 'Email') {
            $content = $outboundManagement->settings->email->body;
            
            $outboundManagement->settings->email->body = $this->replaceContent($outboundManagement->form_id, $content);

            $outboundManagement->load('attachments');
        }

        return response()->json($outboundManagement);
    }

    public function save(Request $request)
    {
        $fileKeys = array_keys($request->file());

        if (count($fileKeys)) {
            $input = $request->except(...$fileKeys);
        } else {
            $input = $request->all();
        }

        $this->outboundManagementManager->save($input, $request->file());

        return response()->json(['success' => 'OK'], 200);
    }

    public function sendDiffusion(Request $request)
    {
        $outboundManagement = $this->outboundManagementManager->save($request->all());

        $this->outboundManagementManager->createDiffusion($outboundManagement);
        
        return response()->json(['success' => 'OK'], 200);
    }

    public function deleteAttachment($id, Request $request)
    {
        try {
            $this->outboundManagementManager->destroyAttachment($id, $request->path);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    private function replaceContent($formId, $content)
    {
        $sections = Section::formFilter($formId)->get()->each(function ($section) use (&$content) {
            $fields = json_decode($section->fields);
            foreach ($fields as $field) {
                $content = str_replace("[[$field->id]]", $field->label, $content);
            }
        });

        return $content;
    }
}
