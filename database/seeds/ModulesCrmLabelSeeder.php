<?php

use Illuminate\Database\Seeder;
use App\Models\ModuleCrm;

class ModulesCrmLabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $campaignsModule = ModuleCrm::where("name", "campaigns")->first();
        $campaignsModule->label = "Camapaña";
        $campaignsModule->save();

        $groupsModule = ModuleCrm::where("name", "groups")->first();
        $groupsModule->label = "Grupos";
        $groupsModule->save();

        $formsModule = ModuleCrm::where("name", "forms")->first();
        $formsModule->label = "Formularios";
        $formsModule->save();

        $downloadReportFormsModule = ModuleCrm::where("name", "download_report_forms")->first();
        $downloadReportFormsModule->label = "Descarga de reportes";
        $downloadReportFormsModule->save();

        $typifyFormRecord = ModuleCrm::where("name", "typify_form_record")->first();
        $typifyFormRecord->label = "Tipificar formularios";
        $typifyFormRecord->save();

        $escalations = ModuleCrm::where("name", "escalations")->first();
        $escalations->label = "Escalamientos";
        $escalations->save();
    }
}
