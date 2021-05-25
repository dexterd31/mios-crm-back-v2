<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCampaingsTableAndForeingkeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaings', function ($table)
        {
            $table->dropForeign('campaings_group_id_foreign');
        });
        Schema::table('forms', function ($table)
        {
            $table->dropForeign('forms_campaign_id_foreign');
        });
        Schema::table('form_logs', function ($table)
        {
            $table->dropForeign('form_logs_campaign_id_foreign');
        });
        Schema::dropIfExists('campaings');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('campaings', function (Blueprint $table) {
            $table->id();
            $table->string('name_campaign');
            $table->timestamps();
            $table->foreignId('group_id')->constrained('groups');
        });

        Schema::table('forms', function ($table)
        {
            $table->foreignId('campaign_id')->constrained('campaings');
        });

        Schema::table('campaings', function ($table)
        {
            $table->foreignId('group_id')->constrained('groups');
        });
        Schema::table('form_logs', function ($table)
        {
            $table->foreignId('campaign_id')->constrained('campaings');
        });
    }
}
