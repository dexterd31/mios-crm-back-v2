<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Nicoll Ramirez
     * 05-03-2021
     * Método para crear el usuario
     */
    public function storeUser(Request $request)
    {
        try
        {
            $user = new User([
                'id_rhh' => $request->input('id_rhh'),
                'state' => $request->input('state')
            ]);
            $user->save();
            return $this->successResponse('Usuario guardado Correctamente');
    
        }catch(\Throwable $e)
        {
            return $this->errorResponse('Error al guardar el usuario',500);
        } 
    }

    /**
     * Nicoll Ramirez 
     * 05-03-2021
     * Método para desactivar el usuario
     */
    public function disabledUser(Request $request, $id)
    {
        try
        {
            $User = User::find($id);
            $User->state = $request->state;
            $User->save();

            return $this->successResponse('Usuario desactivado correctamente');
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al desactivar el usuario',500);
        }
    }
}
