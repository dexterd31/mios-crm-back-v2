<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundManagementTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_management_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aoutbound_management_id')->comment('Id de la gestión.');
            $table->foreignId('tag_id')->comment('Id del tag.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outbound_management_tags');
    }
}
