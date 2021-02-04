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

    $router->post('/saveform', 'FormController@saveForm');
    $router->get('/formslist', 'FormController@FormsList');
    $router->get('/searchform/{id}', 'FormController@searchForm');
    $router->get('/searchformtype', 'FormController@searchFormType');
    
    
 });