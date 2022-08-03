<?php

use App\Jobs\CreateClients;
use App\Models\ModuleCrm;
use Illuminate\Database\Seeder;

class AddDatabaseManagerPermissions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module = new ModuleCrm;
        $module->name = 'database_manager';
        $module->menu_ciu_id = 0;
        $module->status = 1;
        $module->label = 'Gestionador de base de datos';
        $module->save();
    }
}
