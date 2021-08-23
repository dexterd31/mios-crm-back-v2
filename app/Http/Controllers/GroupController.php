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

    public function __construct()
    {
        $this->getRrhhService();
    }

    public function getRrhhService()
	{
		if($this->rrhhService == null)
		{
			$this->setRrhhService(new RrhhService());
		}
		return $this->rrhhService;
	}

	public function setRrhhService($rrhhService)
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
        $rrhh_id = auth()->user()->rrhh_id;
        $groups =  Group::select('groups.id', 'groups.name_group', 'groups.description', 'groups.state')
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->where('group_users.rrhh_id', $rrhh_id);

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

            foreach ($request->users as $user) {
                $groupsusers = new GroupUser([
                    'group_id' => $groups->id,
                    'rrhh_id' => $user['rrhh_id']
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
            return $request;
            $groups = Group::find($id);
            $groups->name_group = $request->name_group;
            $groups->description = $request->description;
            $groups->state = $request->state;
            $groups->save();

            $groupsusers = GroupUser::where('group_id', $groups->id)->get();
            foreach ($groupsusers as $groupuser) {
                $groupuser->delete();
            }
            foreach ($request->users as $user) {
                $groupsus = new GroupUser([
                    'group_id' => $groups->id,
                    'rrhh_id' => $user['rrhh_id']
                ]);
                $groupsus->save();
            }

            return $this->successResponse('Grupo editado Correctamente');

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
        //trae el id de la campanha del grupo
        $idCampaign = Group::select('groups.campaign_id')->where('groups.id', $id)->firstOrFail($id)->campaign_id;

        //trae los usuarios de la camapaña
        $usersRhh = $this->rrhhService->fetchUsersByCampaign($idCampaign);

        //trae los usuarios del grupo
        $usersMembersGroup = GroupUser::where('group_id', $id)->select('rrhh_id')->get();
        $miosHelper = new MiosHelper();
        $idsRrhhMembersGroup = $miosHelper->getArrayValues('rrhh_id', $usersMembersGroup);

        return $this->getUserRrhhGroupMembers($idsRrhhMembersGroup, $usersRhh);
    }

    private function getUserRrhhGroupMembers($idsRrhhMembersGroup, $usersRhh)
    {
        $available = [];
        $members = [];
        foreach ($usersRhh as $userRhh)
        {
            $user = [
                "id_rhh" => $userRhh->id,
                "name" => $userRhh->name
            ];

            if(in_array($userRhh->id, $idsRrhhMembersGroup))
            {
                array_push($members, $user);
            }
            else
            {
                array_push($available, $user);
            }
        }
        return ['available' => $available, 'members' => $members];;
    }

    /**
     * Nicoll Ramirez 
     * 03-03-2021
     * Método para consultar los usarios existentes por campañas
     */

    public function searchUser($id)
    {
        $users = $this->rrhhService->fetchUsersByCampaign($id);
        return array_values($users);
    }

    /**
     * Olme Marin
     * 25-03-2021
     * Método para consultar el listado de los grupos en la BD
     */
    public function listGroupsByUser(MiosHelper $miosHelper, $rrhhId)
    {
        // Se obtiene los grupos por el usuario usuario 
        try {
            $rrhhId = auth()->user()->rrhh_id;
            $where = ['groups.state' => 1, 'group_users.rrhh_id' => $rrhhId];
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
    public function getIdCampaignByRrhhId($rrhhId)
    {
        $groups = Group::select('campaign_id')
            ->distinct()
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->where('group_users.rrhh_id', $rrhhId)->get();
        $groupsIds = [];
        foreach ($groups as $group) {
            array_push($groupsIds, $group['campaign_id']);
        }
        return $groupsIds;
    }

    public function getGroupsByRrhhId($rrhhId)
    {
        return GroupUser::where('rrhh_id', $rrhhId)->get();
    }
}
