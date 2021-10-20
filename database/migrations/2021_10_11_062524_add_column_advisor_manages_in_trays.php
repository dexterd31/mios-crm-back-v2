<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnAdvisorManagesInTrays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trays', function (Blueprint $table) {
            $table->tinyInteger('advisor_manage')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('trays', 'advisor_manage')){
            Schema::table('trays', function (Blueprint $table) {
                $table->dropColumn('advisor_manage');
            });
        }

    }
}
