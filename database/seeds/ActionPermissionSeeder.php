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
            'action' => 'save'
        ]);
        $actionSave->save();

        $actionView = new ActionPermission([
            'action' => 'view'
        ]);
        $actionView->save();

        $actionEdit = new ActionPermission([
            'action' => 'edit'
        ]);
        $actionEdit->save();

        $actionChange = new ActionPermission([
            'action' => 'change'
        ]);
        $actionChange->save();
    }
}
