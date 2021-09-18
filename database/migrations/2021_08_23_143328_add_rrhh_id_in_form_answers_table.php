<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FormAnswer;
use App\Models\User;
use Helpers\MiosHelper;

class AddRrhhIdInFormAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $formAnswers = FormAnswer::all();
        $miosHelper = new MiosHelper();
        $idsUsers = $miosHelper->getArrayValues("user_id", $formAnswers);
        $users = User::whereIn("id", $idsUsers)->get()->keyBy('id');
        foreach ($idsUsers as $idUser)
        {
            if(isset($users[$idUser]))
            {
                FormAnswer::where("user_id", $idUser)->update(['rrhh_id' => $users[$idUser]->id]);   
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
        Schema::table('form_answers', function (Blueprint $table) {
            $table->dropColumn('rrhh_id');
        });
    }
}
