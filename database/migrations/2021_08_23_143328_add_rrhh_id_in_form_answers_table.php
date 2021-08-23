<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FormAnswer;
use App\Models\User;

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
        foreach ($formAnswers as $formAnswer)
        {
            $formAnswer->rrhh_id = User::find($formAnswer->user_id)->id_rhh;
            $formAnswer->save();
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
            //
        });
    }
}
