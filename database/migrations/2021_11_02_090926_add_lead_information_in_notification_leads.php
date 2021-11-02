<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeadInformationInNotificationLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_leads', function (Blueprint $table) {
            $table->json("lead_information")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        if (Schema::hasColumn('notification_leads', 'lead_information')) {
            Schema::table('notification_leads', function (Blueprint $table) {
                $table->dropColumn('lead_information');
            });
        }
    }
}
