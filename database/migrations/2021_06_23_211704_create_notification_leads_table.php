<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Complex\ln;

class CreateNotificationLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_leads', function (Blueprint $table) {
            $table->id();
            $table->boolean('readed')->default(0);
            $table->datetime('read_at')->nullable();
            $table->integer('read_by')->nullable();
            $table->integer('form_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_leads');
    }
}
