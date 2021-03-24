<?php

use Illuminate\Database\Seeder;
use App\Models\ModuleCrm;

class ModuleCrmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = array(
            [
                'name'      => 'campaigns',
                'status'    => 1
            ],
            [
                'name'      => 'groups',
                'status'    => 1
            ],
            [
                'name'      => 'forms',
                'status'    => 1
            ],
            [
                'name'      => 'download_report_forms',
                'status'    => 1
            ],
            [
                'name'      => 'typify_form_record',
                'status'    => 1
            ]
        );

        foreach ($modules as $module) {

            $Module = new ModuleCrm();
            $Module->name = $module['name'];
            $Module->status = $module['status'];
            $Module->save();
        }
    }
}
