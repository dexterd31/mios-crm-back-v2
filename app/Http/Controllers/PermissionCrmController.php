<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PermissionCrm;
use App\Models\RolCrm;
use Helpers\MiosHelper;

class PermissionCrmController extends Controller
{
    /**
     * Olme Marin
     * 24-03-2020
     * Método para enlistar los permisos que tiene un rol
     */
    public function list(MiosHelper $miosHelper, $idRolCiu)
    {

        $rolCrm = RolCrm::where('ciu_id', $idRolCiu)->first();
        if (empty($rolCrm)) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'No se han encontrado el rol con ese id');
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

        return response()->json($data, $data['code']);
    }
}
