<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStructureAnswerTrayInFormAnswersTraysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_answers_trays', function (Blueprint $table) {
            $table->json('structure_answer_tray')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_answers_trays', function (Blueprint $table) {
            $table->dropColumn('structure_answer_tray');
        });
    }
}
