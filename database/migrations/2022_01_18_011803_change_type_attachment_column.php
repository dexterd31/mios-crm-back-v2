<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTypeAttachmentColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications_attatchment', function (Blueprint $table) {
            $table->string('type_attachment', 50)->nullable(false)->change();
            $table->longText('file_attachment')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications_attatchment', function (Blueprint $table) {
            $table->longText('type_attachment', 50)->nullable(true)->change();
            $table->longText('file_attachment')->nullable(true)->change();
        });
    }
}
