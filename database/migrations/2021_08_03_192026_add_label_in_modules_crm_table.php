<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ModuleCrm;

class AddLabelInModulesCrmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modules_crm', function (Blueprint $table) {
            $table->string('label')->default("");
        });


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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules_crm', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
}
