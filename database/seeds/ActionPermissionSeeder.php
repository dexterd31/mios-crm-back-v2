<?php

use Illuminate\Database\Seeder;
use App\Models\ActionPermission;

class ActionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //save, view ,edit, change

        $actionSave = new ActionPermission([
            'action' => 'save',
            'name' => 'Salvar'
        ]);
        $actionSave->save();

        $actionView = new ActionPermission([
            'action' => 'view',
            'name' => 'Ver'
        ]);
        $actionView->save();

        $actionEdit = new ActionPermission([
            'action' => 'edit',
            'name' => 'Editar'
        ]);
        $actionEdit->save();

        $actionChange = new ActionPermission([
            'action' => 'change',
            'name' => 'Change'
        ]);
        $actionChange->save();
    }
}
