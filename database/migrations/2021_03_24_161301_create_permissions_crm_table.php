<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsCrmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions_crm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles_crm');
            $table->foreignId('module_id')->constrained('modules_crm');
            $table->tinyInteger('save')->default(0);
            $table->tinyInteger('view')->default(0);
            $table->tinyInteger('edit')->default(0);
            $table->tinyInteger('change')->default(0);
            $table->tinyInteger('all')->default(0);
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('permissions_crm');
    }
}
