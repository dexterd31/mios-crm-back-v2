<?php

namespace App\Http\Controllers;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
       //ModelTest no existe, es solo para ejemplo,
    //la data debe ser retornada con $this->successResponse() cuando sea 200 
    // y $this->errorResponse() cuando sea un error, este recibira dos parametros, mensaje de error y codigo de error
    public function index()
    {
        $orders = ModelTest::all();

        //Return 200
        return $this->successResponse($orders);

        //Return 404 
        return $this->errorResponse('No hay ordenes disponibles',404);
    }
    //

    //
}
