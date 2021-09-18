<?php

use Illuminate\Database\Seeder;
use App\Models\ActionPermission;

class ActionPermissionViewDisabledSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $actionSave = new ActionPermission([
            'action' => 'ViewDisabled',
            'name' => 'Ver deshabilitados'
        ]);
        $actionSave->save();
    }
}
