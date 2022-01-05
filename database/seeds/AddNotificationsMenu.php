<?php

use App\Models\ModuleCrm;
use Illuminate\Database\Seeder;

class AddNotificationsMenu extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $moduleCrm = new ModuleCrm();
        $moduleCrm->name = 'notificaciones';
        $moduleCrm->status = 1;
        $moduleCrm->label = 'Notificaciones';
        $moduleCrm->save();
    }
}
