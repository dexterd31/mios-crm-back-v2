<?php

namespace App\Http\Controllers;

use App\Jobs\DiffusionBySMS;
use App\Managers\OutboundManagementManager;
use App\Models\Channel;
use App\Models\ClientNew;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\OutboundManagement;
use App\Services\NotificationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OutboundManagementController extends Controller
{
    protected $outboundManagementManager;

    public function __construct(OutboundManagementManager $outboundManagementManager)
    {
        $this->middleware('auth', ['except' => 'show']);
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

        $tags = $form->tags;

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

        return response()->json(['tags' => $tags, 'fields' => $fields]);
    }

    Public function show($outboundManagementId)
    {
        $outboundManagement = OutboundManagement::find($outboundManagementId)->load('tags');

        return response()->json($outboundManagement);
    }

    public function storeAndUpdate(Request $request)
    {
        $this->outboundManagementManager->storeAndUpdate($request->all());

        return response()->json(['success' => 'OK'], 200);
    }

    public function sendDiffusion(Request $request)
    {
        $outboundManagement = $this->outboundManagementManager->storeAndUpdate($request->all());

        $this->outboundManagementManager->createDiffusion($outboundManagement);
        
        return response()->json(['success' => 'OK'], 200);
    }
}
