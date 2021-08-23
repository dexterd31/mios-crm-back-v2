<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\GroupUser;
use App\Models\User;

class AddRrhhIdInGroupUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_users', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $groupUsers = GroupUser::all();
        foreach ($groupUsers as $groupUser)
        {
            $groupUser->rrhh_id = User::find($groupUser->user_id)->id_rhh;
            $groupUser->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_users', function (Blueprint $table) {
            //
        });
    }
}
