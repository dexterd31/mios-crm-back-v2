<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FormAnswerLog;
use App\Models\User;

class AddRrhhIdInFormAnswerLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_answer_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $formAnswerLogs = FormAnswerLog::all();
        foreach ($formAnswerLogs as $formAnswerLog)
        {
            $formAnswerLog->rrhh_id = User::find($formAnswerLog->user_id)->id_rhh;
            $formAnswerLog->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_answer_logs', function (Blueprint $table) {
            $table->dropColumn('rrhh_id');
        });
    }
}
