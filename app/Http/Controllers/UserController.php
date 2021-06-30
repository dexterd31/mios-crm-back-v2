<?php

namespace App\Http\Controllers;

use App\Models\GroupUser;
use Illuminate\Http\Request;
use App\Models\User;
use Helpers\MiosHelper;

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
            $idRhh = $request->input('id_rhh');
            $state = $request->input('state');

            $user = User::where("id_rhh", $idRhh)->first();
            if(!$user)
            {
                $user = new User([
                    'id_rhh' => $request->input('id_rhh'),
                    'state' => $request->input('state')
                ]);
            }
            else
            {
                $user->state = $state;
            }
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

    /**
     * Joao Beleno
     * 22-06-2021
     * Funcion para obtener los usuarios del grupo
     */
    public function getUsersFromMyGroups(Request $request)
    {
        $rrhhId = $request->input('rrhhId');
        $groupControllet = new GroupController();
        $groupsUser = $groupControllet->getGroupsByRrhhId($rrhhId);
        $groupsUser = $this->getArrayValues("group_id", $groupsUser);
        return User::select('group_users.group_id', 'users.id_rhh')
            ->join('group_users', 'group_users.user_id', 'users.id')
            ->whereIn('group_users.group_id', $groupsUser)
            ->where('users.id_rhh','!=', $rrhhId)->get();
    }
}
