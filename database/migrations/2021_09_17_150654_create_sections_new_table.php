<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSectionsNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sections_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms'); 
            $table->string('name_section');
            $table->tinyInteger('type_section');
            $table->json('fields');
            $table->boolean('collapse');
            $table->boolean('duplicate')->default(0);
            $table->tinyInteger('state')->nullable();
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
        Schema::dropIfExists('sections_new');
    }
}
