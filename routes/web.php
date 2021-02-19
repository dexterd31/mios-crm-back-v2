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
    
    //Rutas para guardar información del formulario
    $router->post('/formanswer/saveinfo', 'FormAnswerController@saveinfo');
    
    //Rutas de grupos
    $router->get('/searchgroup', 'GroupController@searchGroup');
    $router->post('/savegroup','GroupController@saveGroup');
    
    //Rutas para consultar la información de respuestas del formulario
    $router->get('/filterform', 'FormAnswerController@filterForm');
    
    
 });