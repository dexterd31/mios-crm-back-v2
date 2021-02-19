<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Relationships extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      
        Schema::table('forms', function ($table)
            {
                $table->foreignId('group_id')->constrained('groups'); 
                $table->foreignId('campaign_id')->constrained('campaings');
                $table->foreignId('form_type_id')->constrained('form_types');
            });
        Schema::table('campaings', function ($table)
            {
                $table->foreignId('group_id')->constrained('groups'); 
            });

        Schema::table('form_answers', function ($table)
            {
                $table->foreignId('user_id')->constrained('users'); 
                $table->foreignId('form_id')->constrained('forms');
                $table->foreignId('channel_id')->constrained('channels');
                $table->foreignId('client_id')->constrained('clients');
            });

        Schema::table('state_forms', function ($table)
            {
                $table->foreignId('form_answer_id')->constrained('form_answers'); 
              
            });
            
        Schema::table('sections', function ($table)
            {
                $table->foreignId('form_id')->constrained('forms'); 
                
            });
            
        Schema::table('key_values', function ($table)
            {
                $table->foreignId('client_id')->constrained('clients'); 
                
            });
        Schema::table('uploads', function ($table)
            {
                $table->foreignId('user_id')->constrained('users'); 
                
            });
        Schema::table('group_users', function ($table)
            {
                $table->foreignId('group_id')->constrained('groups'); 
                $table->foreignId('user_id')->constrained('users'); 
                
            });

    
        
    

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
}
