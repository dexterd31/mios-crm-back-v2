<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FormLog;
use App\Models\User;

class AddRrhhIdInFormLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $formLogs = FormLog::all();
        foreach ($formLogs as $formLog)
        {
            $formLog->rrhh_id = User::find($formLog->user_id)->id_rhh;
            $formLog->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_logs', function (Blueprint $table) {
            //
        });
    }
}
