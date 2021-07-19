<?php

namespace App\Http\Controllers;

use App\Models\ModuleCrm;
use Illuminate\Http\Request;
use App\Models\PermissionCrm;
use App\Models\RolCrm;
use Helpers\MiosHelper;
use Illuminate\Support\Facades\Log;

class PermissionCrmController extends Controller
{
    /**
     * Olme Marin
     * 24-03-2020
     * Método para enlistar los permisos que tiene un rol
     */
    public function list(MiosHelper $miosHelper, $rolCiu)
    {
        try {
            $rolCrm = RolCrm::where('key', trim($rolCiu))->first();
            if (empty($rolCrm)) {
                $data = $miosHelper->jsonResponse(true, 204, 'message', 'No se han encontrado el rol');
            } else {
                $idRolCrm         = $rolCrm->id;
                $permissions      = PermissionCrm::where('rol_id', $idRolCrm)->get()->load('module');

                foreach ($permissions as $permission) {

                    // Se limpia lo que no se necesita mostrar de la consulta
                    unset($permission['id']);
                    unset($permission['rol_id']);
                    unset($permission['module_id']);
                    unset($permission['status']);
                    unset($permission['created_at']);
                    unset($permission['updated_at']);
                    unset($permission['module']['id']);
                    unset($permission['module']['status']);
                    unset($permission['module']['created_at']);
                    unset($permission['module']['updated_at']);
                }

                // Se arma la respuesta
                $response = [
                    'rol_id' => $idRolCrm,
                    'rol_name' => $rolCrm->name,
                    'modules' => $permissions
                ];

                $data   = $miosHelper->jsonResponse(true, 200, 'permissions', $response);
            }
        } catch (\Throwable $th) {
            $data   = $miosHelper->jsonResponse(true, 500, 'message', 'Ha ocurrido un error: ' . $th);
        }
        return response()->json($data, $data['code']);
    }

    public function createPermissionCrm($idMenusCiu, $idRolCrm)
    {
        $modulosCrm = ModuleCrm::whereIn("menu_ciu_id", $idMenusCiu)->get();
        $permissions = array();
        foreach($modulosCrm as $moduloCrm)
        {
            $permission = array();
            $permission = [
                'rol_id' => $idRolCrm,
                'module_id' => $moduloCrm['id'],
                'save' => 1,
                'view' => 1,
                'edit' => 1,
                'change' => 1,
                'status' => 1,
                'all' => 1
            ];
            array_push($permissions, $permission);
        }
        PermissionCrm::insert($permissions);
    }
}
