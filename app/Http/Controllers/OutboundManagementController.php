<?php

namespace App\Http\Controllers;

use App\Managers\DataBaseManager;
use App\Managers\OutboundManagementManager;
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
        $this->middleware('auth', ['except' => ['testDiffusion']]);
        // $this->middleware('auth');
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

        $servers = Server::get(['id', 'name']);

        // $campaing = (new NominaService)->fetchCampaign(auth()->user()->rrhh->campaing_id);

        $groups = Group::campaingFilter(auth()->user()->rrhh->campaign_id)->pluck('id')->toArray();

        $forms = Form::groupInFilter($groups)->distinct()->pluck('id')->toArray();

        $products = Product::join('form_product', 'form_product.product_id', 'products.id')
        ->whereIn('form_product.form_id', $forms)->distinct()->get(['products.id', 'products.name']);

        $whatsappAccounts = WhatsappAccount::whereIn('form_whatsapp_account.form_id', $forms)
        ->join('form_whatsapp_account', 'form_whatsapp_account.whatsapp_account_id', 'whatsapp_accounts.id')
        ->distinct()->get(['whatsapp_accounts.id', 'whatsapp_accounts.app_name', 'whatsapp_accounts.source']);

        return response()->json([
            'tags' => $tags,
            'fields' => $fields,
            'emails' => $emails,
            'servers' => $servers,
            // 'campaing' => $campaing,
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
        $outboundManagement = $this->outboundManagementManager->save($request->all());

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

    public function testDiffusion()
    {
    //     $this->outboundManagementManager->sendDiffusionByVoice(21, [
    //             [
    //                 "destination" => "3123365936",
    //                 "id" => 3729,
    //             ],
    //             [
    //                 "destination" => "3208803093",
    //                 "id" => 3730,
    //             ]
    //         ], [
    //         "startHour" => "19:59",
    //         "endHour" => "21:05",
    //         "days" => [
    //             0 => 1,
    //             1 => 2,
    //             2 => 3,
    //             3 => 4,
    //             4 => 5,
    //             5 => 6
    //         ],
    //         "token" => "NJ9spTb2sR",
    //         "product" => "AIRSMS"
    //     ]);
        // ini_set('max_execution_time', 0);
        // set_time_limit(0);
        // (new DataBaseManager)->createClients();
    }
}
