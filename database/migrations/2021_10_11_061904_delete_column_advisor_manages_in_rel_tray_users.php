<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteColumnAdvisorManagesInRelTrayUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('rel_trays_users', 'advisor_manage'))
        {
            Schema::table('rel_trays_users', function (Blueprint $table) {
                $table->dropColumn('advisor_manage');
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
        Schema::table('rel_trays_users', function (Blueprint $table) {
            $table->tinyInteger('advisor_manage')->default(0);
        });
}
}
