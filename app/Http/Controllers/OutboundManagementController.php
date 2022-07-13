<?php

namespace App\Http\Controllers;

use App\Managers\OutboundManagementManager;
use App\Models\Form;
use App\Models\OutboundManagement;
use Illuminate\Http\Request;

class OutboundManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => 'show']);
    }

    public function indexByForm($formId, Request $request)
    {
        $filterOptions = $request->filter_options ?? [];

        $outboundManagement = (new OutboundManagementManager)->listManagement($formId, (array) $filterOptions);

        return response()->json($outboundManagement);
    }

    public function create($formId)
    {
        $tags = Form::find($formId)->tags()->get(['id', 'name']);

        $fields = [];

        $form = Form::find($formId);

        $form->section()->get('fields')->each(function ($section) use (&$fields) {
            $sectionFields = json_decode($section->fields);
            foreach ($sectionFields as $field) {
                $fields[] = ['id' => $field->id, 'name' => $field->label];
            }
        });

        foreach ($form->cutomFields->fields as $field) {
            $fields[] = ['id' => $field->id, 'name' => $field->label];
        }

        return response()->json(['tags' => $tags, 'fields' => $fields]);
    }

    Public function show($outboundManagementId)
    {

    }

    public function storeAndUpdate(Request $request)
    {

    }
}
