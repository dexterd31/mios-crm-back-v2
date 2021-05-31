<?php

use Illuminate\Database\Seeder;
use App\Models\ModuleCrm;

class SetMenuCiuIdInModulesCrmSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module = ModuleCrm::where("id", 1);
        $module->menu_ciu_id = 10;
        $module->save();
        $module = ModuleCrm::where("id", 2);
        $module->menu_ciu_id = 12;
        $module->save();
        $module = ModuleCrm::where("id", 3);
        $module->menu_ciu_id = 13;
        $module->save();
        $module = ModuleCrm::where("id", 4);
        $module->menu_ciu_id = 13;
        $module->save();
        $module = ModuleCrm::where("id", 5);
        $module->menu_ciu_id = 13;
        $module->save();
        $module = ModuleCrm::where("id", 6);
        $module->menu_ciu_id = 13;
        $module->save();
    }
}
