<?php
use App\Models\FormAnswer;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    // return $router->app->version();
    return 'Api Services de CRM';
});
$router->group(['prefix' => 'api'], function () use ($router) {

    //Rutas para creación de formulario dinamico
    $router->post('/saveform', 'FormController@saveForm');
    $router->get('/formslist', 'FormController@FormsList');
    $router->get('/searchform/{id}', 'FormController@searchForm');
    $router->get('/searchPrechargeFields/{id}', 'FormController@searchPrechargeFields');
    $router->get('/searchformtype', 'FormController@searchFormType');
    $router->put('/editform/{id}', 'FormController@editForm');
    $router->put('/deleteform/{id}', 'FormController@deleteForm');
    //Reporte del formulario
    $router->post('/report','FormController@report');
    $router->get('/formsbyuser/{idUser}', 'FormController@formsByUser');

    //Base de datos
    // la variable parameters esta en base64 y puede contener el caracter '/', lo cual lanza error 404,
    // por eso se usa el regex para capturar todo el contenido de la url
    $router->get('/form/dowload/{parameters:.*}', 'UploadController@exportExcel');
    $router->post('/form/download/db', 'UploadController@exportDatabase');
    $router->post('/form/upload', 'UploadController@importExcel');
    $router->post('form/dbHistory/{form_id}', 'UploadController@index');

    //Rutas para la información del formulario
    $router->post('/formanswer/saveinfo', 'FormAnswerController@saveinfo');
    $router->post('/formanswer/integration/voice', 'FormAnswerController@saveIntegrationVoice');


    $router->post('/formanswer/filterform', 'FormAnswerController@filterForm');
    $router->get('/formanswer/historic/{id}', 'FormAnswerController@formAnswerHistoric');
    $router->put('/formanswer/update/{id}', 'FormAnswerController@updateInfo');
    $router->post('formanswer/download', 'FormAnswerController@downloadFile');

    //consultar tipo de documento de los clientes
    $router->get('/searchdocumenttype', 'FormAnswerController@searchDocumentType');

    $router->post('/template/store','TemplateController@store');
    $router->post('/template/buildTemplate','TemplateController@buildTemplate');
    $router->get('/template/show/{formId}','TemplateController@show');

    //Rutas de grupos
    $router->get('/searchgroup/{id}', 'GroupController@searchGroup');
    $router->post('/savegroup','GroupController@saveGroup');
    $router->get('/searchselectgroup/{id}','GroupController@searchSelectGroup');
    $router->get('/groupslist','GroupController@groupslist');
    $router->put('/deletegroup/{id}','GroupController@deleteGroup');
    $router->put('/updategroup/{id}','GroupController@updateGroup');
    //consultar usuarios existentes para asignar al grupo
    $router->get('/searchUser/{id}','GroupController@searchUser');
    $router->get('/groupsbyuser/{idUser}', 'GroupController@listGroupsByUser');
    $router->get('/getGroupsByRrhhId/{rrhhId}', 'GroupController@getGroupsByRrhhId');

    // rutas de campañas
    $router->get('/campaigns', 'CampaignController@index');
    $router->post('/campaigns/{id}/updateState', 'CampaignController@updateState');
    $router->get('/campaignsbyuser/{idUser}', 'CampaignController@campaignsByUser');

    //Rutas de usuarios
    $router->post('/storeUser', 'UserController@storeUser');
    $router->put('/disabledUser/{id}', 'UserController@disabledUser');
    $router->get('/getUsersFromMyGroups', 'UserController@getUsersFromMyGroups');

    //Rutas de clientes
    $router->get('/getClient/{id}', 'ClientController@getClient');
    $router->post('/client','ClientController@store');

    //Rutas de parámetros
    $router->post('/saveParameters/{id}','ParameterController@saveParameters');
    $router->get('/searchParameterByFather/{id}/{father}','ParameterController@searchParameterByFather');
    $router->get('/searchParameter/{id}','ParameterController@searchParameter');
    $router->put('/updateParameters/{id}','ParameterController@updateParameters');

    //Rutas de conexión apis
    $router->post('/apiConnection/save', 'ApiConnectionController@save');
    $router->get('/apiConnection/list/{form_id}', 'ApiConnectionController@list');
    $router->get('/apiConnection/get/{id}', 'ApiConnectionController@get');
    $router->put('/apiConnection/update/{id}', 'ApiConnectionController@update');
    $router->get('/apiConnection/delete/{id}', 'ApiConnectionController@delete');

    //Rutas de api question
    $router->post('/apiQuestion/save', 'ApiQuestionController@save');
    $router->get('/apiQuestion/list/{form_id}', 'ApiQuestionController@list');
    $router->get('/apiQuestion/get/{id}', 'ApiQuestionController@get');
    $router->put('/apiQuestion/update/{id}', 'ApiQuestionController@update');
    $router->get('/apiQuestion/delete/{id}', 'ApiQuestionController@delete');


    //Rutas Bandejas
    $router->post('/trays/save','TrayController@store');
    $router->get('/trays','TrayController@index');
    $router->get('/trays/delete/{id}','TrayController@delete');
    $router->get('/trays/form/{id}','TrayController@show');
    $router->get('/tray/{id}','TrayController@getTray');
    $router->put('/tray/{id}','TrayController@update');
    $router->get('/tray/formAnswersByTray/{id}','TrayController@formAnswersByTray');
    $router->get('/tray/changeState/{id}','TrayController@changeState');

    //Rutas escalamientos
    $router->post('/escalations', 'EscalationController@validateScalation');
    //Rutas Permisos
    $router->get('/permission/{rolCiu}', 'PermissionCrmController@list');
    $router->post('/createRoles', 'RolCrmController@createRolCrm');
    $router->post('/createPermissions', 'PermissionController@create');
    //$router->get('/permission/{rolCiuId}', 'PermissionController@index');
    $router->post('/editPermissions', 'PermissionController@edit');


    $router->get('/prueba-jsoncontains/{formId}', function($formId){
        $form_answers = FormAnswer::where('form_id', $formId)
            ->whereJsonContains('structure_answer', ['key' => 'document', 'value' => '1032399970']);

        $form_answers = $form_answers->with('client')->paginate(10);
        return $form_answers;
    });


    /**
     * Sandbox api
     */

     $router->get('contactos','SandboxController@getContactsFromDataCRM');
     $router->get('fields','SandboxController@getFields');
     $router->get('datacrm/production/test/{formId}','SandboxController@testDataCRMProduction');

     $router->get('pusher','SandboxController@testPusher');

     //Rutas para el manejo de notificaciones de nuevos lead (Integracion SBS)
     $router->get('lead/notifications/{formId}','NotificationLeadController@getNotifications');
     $router->get('lead/notification/{formId}/{rrhhId}','NotificationLeadController@setReaded');


     /**
      * Integrations
      */
      $router->group(['prefix' => 'integrations'], function () use ($router) {
          $router->post('login','integrations\AccessSyncController@login');
          $router->post('sync','integrations\ReaderSyncController@syncForms');

      });



});


