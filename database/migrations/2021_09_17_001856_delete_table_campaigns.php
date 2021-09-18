<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteTableCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('form_logs', 'campaign_id'))
        {
            Schema::table('form_logs', function (Blueprint $table) {
                if($this->foreignKeysExists('form_logs', "form_logs_campaign_id_foreign"))
                {
                    $table->dropForeign(['campaign_id']);
                }
                $table->dropColumn('campaign_id');
            });
        }
        Schema::dropIfExists('campaings');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    public function foreignKeysExists($table, $foreignKey)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        $foreignKeys = array_map(function($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));

        return in_array($foreignKey, $foreignKeys);
    }
}
