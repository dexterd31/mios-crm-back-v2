<?php

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
    return $router->app->version();
});
$router->group(['prefix' => 'api'], function () use ($router) {

    //Rutas para creación de formulario dinamico
    $router->post('/saveform', 'FormController@saveForm');
    $router->get('/formslist', 'FormController@FormsList');
    $router->get('/searchform/{id}', 'FormController@searchForm');
    $router->get('/searchformtype', 'FormController@searchFormType');
    $router->put('/editform/{id}', 'FormController@editForm');
    $router->put('/deleteform/{id}', 'FormController@deleteForm');

    //Base de datos
    $router->post('/form/dowload', 'UploadController@exportExcel');
    $router->post('/form/upload', 'UploadController@importExcel');
   
    //Rutas para la información del formulario
    $router->post('/formanswer/saveinfo', 'FormAnswerController@saveinfo');
    $router->post('/formanswer/filterform', 'FormAnswerController@filterForm');
    $router->put('/formanswer/updateFormAnswer/{id}', 'FormAnswerController@updateFormAnswer');
    $router->get('/formanswer/historic/{form_id}/{client_id}', 'FormAnswerController@formAnswerHistoric');
    //consultar tipo de documento de los clientes
    $router->get('/searchdocumenttype', 'FormAnswerController@searchDocumentType');

    
    //Rutas de grupos
    $router->get('/searchgroup/{id}', 'GroupController@searchGroup');
    $router->post('/savegroup','GroupController@saveGroup');
    $router->get('/searchselectgroup','GroupController@searchSelectGroup');
    $router->get('/groupslist','GroupController@groupslist');
    $router->put('/deletegroup/{id}','GroupController@deleteGroup');
    $router->put('/updategroup/{id}','GroupController@updateGroup');
    //consultar usuarios existentes para asignar al grupo
    $router->get('/searchUser/{id}','GroupController@searchUser');

    // rutas de campañas
    $router->get('/campaigns', 'CampaignController@index');
    $router->post('/campaigns/{id}/updateState', 'CampaignController@updateState');
    
    //Rutas de usuarios
    $router->post('/storeUser', 'UserController@storeUser');
    $router->put('/disabledUser/{id}', 'UserController@disabledUser');
    
    //Rutas de clientes
    $router->get('/getClient/{id}', 'ClientController@getClient');

    //Rutas de bandejas
    $router->post('/trays/save','StateFormController@save');
    $router->get('/trays/list/{form_id}','StateFormController@list');
    $router->get('/trays/get/{id}','StateFormController@get');
    $router->put('/trays/update/{id}','StateFormController@update');
    $router->get('/trays/delete/{id}','StateFormController@delete');
    $router->get('/trays/show/{id}','StateFormController@trayQuery');
});

//['first_name', 'middle_name', 'first_lastname', 'second_lastname', 'document', 'phone', 'email','document_type_id']