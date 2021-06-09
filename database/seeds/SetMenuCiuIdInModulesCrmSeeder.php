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
        $module = ModuleCrm::where("id", 1)->first();
        $module->menu_ciu_id = 10;
        $module->save();
        $module = ModuleCrm::where("id", 2)->first();
        $module->menu_ciu_id = 12;
        $module->save();
        $module = ModuleCrm::where("id", 3)->first();
        $module->menu_ciu_id = 13;
        $module->save();
        $module = ModuleCrm::where("id", 4)->first();
        $module->menu_ciu_id = 13;
        $module->save();
        $module = ModuleCrm::where("id", 5)->first();
        $module->menu_ciu_id = 13;
        $module->save();
        $module = ModuleCrm::where("id", 6)->first();
        $module->menu_ciu_id = 13;
        $module->save();
    }
}
