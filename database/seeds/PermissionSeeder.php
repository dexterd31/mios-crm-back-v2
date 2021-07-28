<?php

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\PermissionCrm;
use Illuminate\Support\Facades\Log;
use App\Models\ActionPermission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $actionPermission = ActionPermission::all()->keyBy('action');;
        $permissionsCrm = PermissionCrm::join('roles_crm', 'permissions_crm.rol_id', 'roles_crm.id')->get();

        foreach ($permissionsCrm as $permissionCrm)
        {
            if($permissionCrm->save == 1 && $permissionCrm->ciu_id != 0)
            {
                $permission = new Permission([
                    'role_ciu_id' => $permissionCrm->ciu_id,
                    'module_id' => $permissionCrm->module_id,
                    'action_permission_id' => $actionPermission['save']->id
                ]);
                $permission->save();
            }
            if($permissionCrm->view == 1 && $permissionCrm->ciu_id != 0)
            {
                $permission = new Permission([
                    'role_ciu_id' => $permissionCrm->ciu_id,
                    'module_id' => $permissionCrm->module_id,
                    'action_permission_id' => $actionPermission['view']->id
                ]);
                $permission->save();
            }
            if($permissionCrm->edit == 1 && $permissionCrm->ciu_id != 0)
            {
                $permission = new Permission([
                    'role_ciu_id' => $permissionCrm->ciu_id,
                    'module_id' => $permissionCrm->module_id,
                    'action_permission_id' => $actionPermission['edit']->id
                ]);
                $permission->save();
            }
            if($permissionCrm->change == 1 && $permissionCrm->ciu_id != 0)
            {
                $permission = new Permission([
                    'role_ciu_id' => $permissionCrm->ciu_id,
                    'module_id' => $permissionCrm->module_id,
                    'action_permission_id' => $actionPermission['change']->id
                ]);
                $permission->save();
            }
        }
    }
}
