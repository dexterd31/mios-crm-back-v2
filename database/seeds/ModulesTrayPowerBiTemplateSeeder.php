<?php

use Illuminate\Database\Seeder;
use App\Models\ModuleCrm;


class ModulesTrayPowerBiTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ModuleCrm::insert([
            [
                'name' => 'template',
                'status' => 1,
                "label" => "Administrador de plantillas",
            ],
            [
                'name' => 'Tray',
                'status' => 1,
                "label" => 'Bandejas',
            ],
            [
                'name' => 'report',
                'status' => 1,
                "label" => "Excel, Power Bi",
            ],
            [
                'name' => 'dataBase',
                'status' => 1,
                "label" => "Base de datos",
            ],
            [
                'name' => 'vicidial',
                'status' => 1,
                "label" => "Vicidial dashboard",
            ]
        ]);
    }
}
