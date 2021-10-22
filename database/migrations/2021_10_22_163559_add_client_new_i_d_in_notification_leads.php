<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientNewIDInNotificationLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_leads', function (Blueprint $table) {
            //
            $table->bigInteger('client_new_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification_leads', function (Blueprint $table) {
            //
            $table->dropColumn('client_new_id');
        });
    }
}
