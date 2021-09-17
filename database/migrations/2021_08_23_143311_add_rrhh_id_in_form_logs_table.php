<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FormLog;
use App\Models\User;
use Helpers\MiosHelper;

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
        $miosHelper = new MiosHelper();
        $idsUsers = $miosHelper->getArrayValues("user_id", $formLogs);
        $users = User::whereIn("id", $idsUsers)->get()->keyBy('id');
        foreach ($idsUsers as $idUser)
        {
            if(isset($users[$idUser]))
            {
                FormLog::where("user_id", $idUser)->update(['rrhh_id' => $users[$idUser]->id]);   
            }
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
            $table->dropColumn('rrhh_id');
        });
    }
}
