<?php

namespace App\Http\Controllers;
use App\Models\Group;
use App\Models\GroupUser;
use GroupsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Nicol Ramirez
     * 16-02-2020
     * Método para consultar los grupos creados
     */
    public function searchGroup()
    {
        $groups = DB::table('group_users')
        ->join('groups','group_users.group_id','=','groups.id')
        ->join('campaings','campaings.group_id','=','groups.id')
        ->join('users','group_users.user_id','=','users.id')
        ->where('campaings.id',1)
        ->select('name_campaign','name_group','groups.description','username')
        ->get();
        return $groups;
    }

    /**
     * Nicol Ramirez
     * 17-02-2020
     * Método para crear el grupo con sus usuarios
     */
    public function saveGroup(Request $request)
    {
        /* try
        { */
            $groups = new Group([
                'name_group' => $request->input('name_group'),
                'description' => $request->input('description'),
                'state' =>$request->input('state')
            ]);
            $groups->save();

            $usersId = $request->input('users');
            $userIdsArr = explode(',',$usersId);
                dd($userIdsArr);
            foreach( $userIdsArr as $userId )
            {

                $groupsusers = new GroupUser([
                        'group_id'=> $groups->id,
                        'user_id' => $userId
                    ]);
                $groupsusers->save();
            }
    
            return 'guardado';
           // return $this->successResponse('Guardado Correctamente');
    
       /*  }catch(\Throwable $e)
        {
            return $this->errorResponse('Error al guardar el formulario',500);
        } */
    }    
    
    /**
     * Nicol Ramirez
     * 17-02-2020
     * Método para crear el grupo con sus usuarios
     */
    public function searchSelectGroup(){
        $groups = DB::table('groups')
                    ->select('id','name_group')->get();
        return $groups;
    }

    /**
     * Nicol Ramirez 
     * 26-02-2021
     * Método para consultar el listado de los grupos en la BD
     */
    public function groupslist(Request $request){
        $groups = Group::select('id','name_group','description','state')
                        ->where('campaign_id',$request->campaign_id)->get();
        return $groups;
    }

    public function deleteGroup(Request $request, $id){
        try
        {
            $group = Group::find($id);
            $group->state = $request->state;
            $group->save();

            return $this->successResponse('Grupo desactivado correctamente');
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al desactivar el Grupo',500);
        }
    }
}
