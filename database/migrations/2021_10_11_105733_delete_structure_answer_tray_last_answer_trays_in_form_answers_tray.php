<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteStructureAnswerTrayLastAnswerTraysInFormAnswersTray extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('form_answers_trays', 'structure_answer_tray'))
        {
            Schema::table('form_answers_trays', function (Blueprint $table) {
                $table->dropColumn('structure_answer_tray');
            });
        }
        if(Schema::hasColumn('form_answers_trays', 'lastAnswersTrays'))
        {
            Schema::table('form_answers_trays', function (Blueprint $table) {
                $table->dropColumn('lastAnswersTrays');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(!Schema::hasColumn('form_answers_trays', 'structure_answer_tray'))
        {
            Schema::table('form_answers_trays', function (Blueprint $table) {
                $table->json('structure_answer_tray')->nullable();
            });
        }
        if(!Schema::hasColumn('form_answers_trays', 'lastAnswersTrays'))
        {
            Schema::table('form_answers_trays', function (Blueprint $table) {
                $table->tinyInteger('lastAnswersTrays')->default(1);
            });
        }
    }
}
