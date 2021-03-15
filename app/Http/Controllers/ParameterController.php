<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;

class ParameterController extends Controller
{
    public function saveParameters(Request $request)
    {
        $parameters = new Parameter([
            'section_id' => $request->input('section_id'),
            'name' => $request->input('label'),
            'options' => json_encode($request->options),
            'idSuperior' => $request->input('idSuperior')
        ]); 
        $parameters->save();
        
        return 'ok';
    }
}
