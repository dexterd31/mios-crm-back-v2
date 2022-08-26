<?php

namespace App\Http\Controllers;

use App\Managers\OutboundManagementManager;
use App\Models\ClientTag;
use App\Models\CustomFieldData;
use App\Models\Form;
use App\Models\Group;
use App\Models\OutboundManagementAttachment;
use App\Models\Product;
use App\Models\Server;
use App\Models\WhatsappAccount;
use App\Services\NotificationsService;
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

        $emails = (new NotificationsService)->getEmailsByCampaing(auth()->user()->rrhh->campaign_id);

        $servers = Server::get(['id', 'name']);

        $groups = Group::campaingFilter(auth()->user()->rrhh->campaign_id)->pluck('id')->toArray();

        $forms = Form::groupInFilter($groups)->distinct()->pluck('id')->toArray();

        $products = Product::join('form_product', 'form_product.product_id', 'products.id')
        ->whereIn('form_product.form_id', $forms)->distinct()->get(['products.id', 'products.name']);

        $whatsappAccounts = WhatsappAccount::whereIn('form_whatsapp_account.form_id', $forms)
        ->join('form_whatsapp_account', 'form_whatsapp_account.whatsapp_account_id', 'whatsapp_accounts.id')
        ->distinct()->get(['whatsapp_accounts.id', 'whatsapp_accounts.app_name', 'whatsapp_accounts.source']);

        return response()->json([
            'tags' => $tags,
            'emails' => $emails,
            'servers' => $servers,
            'products' => $products,
            'whatsappAccounts' => $whatsappAccounts
        ]);

    }

    public function show($outboundManagementId)
    {
        $outboundManagement = $this->outboundManagementManager->showOutboundManagement($outboundManagementId);

        return response()->json($outboundManagement, 200);
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
        $fileKeys = array_keys($request->file());

        if (count($fileKeys)) {
            $input = $request->except(...$fileKeys);
        } else {
            $input = $request->all();
        }

        $outboundManagement = $this->outboundManagementManager->save($input, $request->file());

        $this->outboundManagementManager->createDiffusion($outboundManagement);
        
        return response()->json(['success' => 'OK'], 200);
    }

    public function deleteAttachment($id)
    {
        $this->outboundManagementManager->destroyAttachment($id);
        return response()->json(['success' => 'Ok'], 200);
    }

    public function downloadAttachment($id)
    {
        $outboundManagementAttachment = OutboundManagementAttachment::find($id);
        return response()->download(storage_path("app/$outboundManagementAttachment->path"), $outboundManagementAttachment->name);
    }

    public function sendEmailTest(Request $request)
    {
        $fileKeys = array_keys($request->file());
    
        if (count($fileKeys)) {
            $input = $request->except(...$fileKeys);
        } else {
            $input = $request->all();
        }

        $this->outboundManagementManager->sendTestMail($input, $request->file());

        return response()->json(['success' => 'OK'], 200);
    }

    public function getWhatsappTemplates($whatsappAccountId)
    {
        $templates = $this->outboundManagementManager->listWhatsappTemplates($whatsappAccountId);

        return response()->json(compact('templates'), 200);
    }

    public function getFormFields($formId, Request $request)
    {
        $form = Form::find($formId);

        $fields = [];
        $customFields = [];

        $clients = ClientTag::join('client_news', 'client_news.id', 'client_tag.client_new_id')
        ->where('client_news.form_id', $formId)->whereIn('client_tag.tag_id', $request->tags)
        ->distinct()->get(['client_tag.client_new_id'])->each(function ($client) use (&$customFields) {
            $customFields = CustomFieldData::where('client_new_id', $client->client_new_id)->pluck('field_data')->toArray();
            dd($customFields);
        });


        foreach ($customFields as $key => $fieldsData) {
            $fieldsIds = [];
            foreach ($fieldsData as $fieldData) {
                $fieldsIds[] = $fieldData->id;
            }
            $customFields[$key] = $fieldsIds;
        }
        
        $form->section()->get('fields')->each(function ($section) use (&$fields) {
            $sectionFields = json_decode($section->fields);
            foreach ($sectionFields as $field) {
                $fields[] = ['id' => $field->id, 'name' => $field->label];
            }
        });
        
        if ($form->cutomFields && count($customFields)) {
            $formFieldsIds = [];

            foreach ($form->cutomFields->fields as $field) {
                $formFieldsIds[] = $field->id;
            }

            foreach ($customFields as $customFieldsIds) {
                if (count($customFieldsIds)) {
                    foreach ($formFieldsIds as $key => $id) {
                        if (!in_array($id, $customFieldsIds)) {
                            unset($formFieldsIds[$key]);
                        }
                    }
                } else {
                    $formFieldsIds = [];
                    break;
                }
            }

            foreach ($form->cutomFields->fields as $field) {
                if (in_array($field->id, $formFieldsIds)) {
                    $fields[] = ['id' => $field->id, 'name' => $field->label];
                }
            }
        }
        
        return response()->json(['fields' => $fields]);
    }
}
