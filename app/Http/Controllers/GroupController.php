<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\RrhhService;
use Helpers\MiosHelper;
use Log;

class GroupController extends Controller
{
    private $rrhhService;

    public function __construct(RrhhService $rrhhService)
    {
        $this->rrhhService = $rrhhService;
    }
    /**
     * Nicol Ramirez 
     * 26-02-2021
     * Método para consultar el listado de los grupos en la BD
     */
    public function groupslist(Request $request)
    {
        $userId = auth()->user()->rrhh_id;
        $userLocal = User::where('id_rhh','=',$userId)->firstOrFail();
        $groups =  Group::select('groups.id', 'groups.name_group', 'groups.description', 'groups.state')
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->where('group_users.User_id', $userLocal->id);

        if(!is_null($request->campaign_id) && $request->campaign_id != "null")
        {
            $groups = $groups->where('groups.campaign_id', $request->campaign_id);
        }

        $groups = $groups->get();
        return $groups;
    }

    /**
     * Nicol Ramirez
     * 17-02-2020
     * Método para crear el grupo con sus usuarios
     */
    public function saveGroup(Request $request)
    {
        try {
            $groups = new Group([
                'campaign_id' => $request->input('campaign_id'),
                'name_group' => $request->input('name_group'),
                'description' => $request->input('description'),
                'state' => $request->input('state')
            ]);
            $groups->save();

            foreach ($request->users as $userId) {
                $groupsusers = new GroupUser([
                    'group_id' => $groups->id,
                    'user_id' => $userId['userId']
                ]);
                $groupsusers->save();
            }

            return $this->successResponse('Guardado Correctamente');
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al guardar el formulario', 500);
        }
    }

    /**
     * Nicoll Ramirez
     * 01-03-2021
     * Método para editar los grupos
     */

    public function updateGroup(Request $request, $id)
    {
        try {
            $groups = Group::find($id);
            $groups->name_group = $request->name_group;
            $groups->description = $request->description;
            $groups->state = $request->state;
            $groups->save();

            $groupsusers = GroupUser::where('group_id', $groups->id)->get();
            foreach ($groupsusers as $groupuser) {
                $groupuser->delete();
            }
            foreach ($request->users as $userId) {
                $groupsus = new GroupUser([
                    'group_id' => $groups->id,
                    'user_id' => $userId['userId']
                ]);
                $groupsus->save();
            }

            return $this->successResponse('Grupo editado Correctamente');
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al editar el grupo', 500);
        }
    }

    /**
     * Nicol Ramirez
     * 26-02-2021
     * Método para desactivar los grupos
     */
    public function deleteGroup(Request $request, $id)
    {
        try {
            $group = Group::find($id);
            $group->state = $request->state;
            $group->save();

            return $this->successResponse('Grupo desactivado correctamente');
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al desactivar el Grupo', 500);
        }
    }

    /**
     * Nicol Ramirez
     * 16-02-2020
     * Método para consultar los grupos creados
     */
    public function searchGroup($id)
    {
        $usersRhh = $this->rrhhService->fetchUsersByCampaign(3);
        $idsRrhh = $this->getIdsRrhh($usersRhh);
        $subquery = DB::table('group_users')
            ->select('group_users.user_id','group_users.group_id', 'group_users.id')
            ->where('group_id', $id);

        $groupusers = DB::table('group_users')
            ->join('groups', 'group_users.group_id', '=', 'groups.id')
            ->join('users', 'group_users.user_id', '=', 'users.id')
            ->where('groups.id', $id)
            ->select('name_group', 'groups.description', 'group_users.user_id', 'users.id_rhh')->get();

        $users = User::select('users.id', 'users.id_rhh')
            ->leftJoinSub($subquery, 'group_users', function ($join) {
                $join->on('users.id', 'group_users.user_id');
            })
            ->where('group_users.id', null)
            ->whereIn('users.id_rhh', $idsRrhh)
            ->get();

        $merged_data = $this->mergeUserCrmWithRrhh($groupusers, $usersRhh);
        $merged_data2 = $this->mergeUserCrmWithRrhh($users, $usersRhh);
        $data = ['available' => $merged_data2, 'members' => $merged_data];
        return $data;
    }

    private function mergeUserCrmWithRrhh($userscrm, $usersRhh)
    {
        foreach ($userscrm as $usercrm)
        {
            foreach($usersRhh as $userRhh)
            {
                if($userRhh->id == $usercrm->id_rhh)
                {
                    $usercrm->name = $userRhh->name;
                    $usercrm->documento = $userRhh->documento;
                    $usercrm->state = $userRhh->state;
                    $usercrm->email = $userRhh->email;
                }
            }
        }
        return $userscrm;
    }

    private function getIdsRrhh($usersRhh)
    {
        $idsRrhh = array();
        foreach ($usersRhh as $userRhh)
        {
            array_push($idsRrhh, $userRhh->id);
        }
        return $idsRrhh;
    }

    /**
     * Nicoll Ramirez 
     * 03-03-2021
     * Método para consultar los usarios existentes por campañas
     */

    public function searchUser($id)
    {
        $users = $this->rrhhService->fetchUsersByCampaign($id);
        $users = collect($users);
        $users = $users->filter(function ($value, $key) {
            return User::where('id_rhh', $value->id)->first() != null;
        });
        foreach ($users as $user) {
            $crmUser = User::where('id_rhh', $user->id)->first();
            if ($crmUser != null) {
                $user->id = $crmUser->id;
            }
        }
        return array_values($users->all());
    }

    /**
     * Olme Marin
     * 25-03-2021
     * Método para consultar el listado de los grupos en la BD
     */
    public function listGroupsByUser(MiosHelper $miosHelper, $idUser)
    {
        // Se obtiene los grupos por el usuario usuario 
        try {
            $where = ['groups.state' => 1, 'group_users.user_id' => $idUser];
            $groups = DB::table('groups')->join('group_users', 'groups.id', '=', 'group_users.group_id')
                ->where($where)
                ->select('groups.id', 'groups.campaign_id', 'groups.name_group', 'groups.description', 'groups.state', 'groups.created_at', 'groups.updated_at')
                ->get();
            $data = $miosHelper->jsonResponse(true, 200, 'groups', $groups);
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(true, 500, 'message', 'Ha ocurrido un error: ' . $th);
        }

        return response()->json($data, $data['code']);
    }

    /**
     * Joao Beleno
     * 12-05-2021
     * Funcion para obtener las campaign que pertenecen a los grupos del usuario por id de usuario
     */
    public function getIdCampaignByUserId($userId)
    {
        $groups = Group::select('campaign_id')
            ->distinct()
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->where('group_users.User_id', $userId)->get();
        $groupsIds = [];
        foreach ($groups as $group) {
            array_push($groupsIds, $group['campaign_id']);
        }
        return $groupsIds;
    }
}
