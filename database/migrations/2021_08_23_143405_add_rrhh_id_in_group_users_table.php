<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\GroupUser;
use App\Models\User;
use Helpers\MiosHelper;

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
        $miosHelper = new MiosHelper();
        $idsUsers = $miosHelper->getArrayValues("user_id", $groupUsers);
        $users = User::whereIn("id", $idsUsers)->get()->keyBy('id');
        foreach ($idsUsers as $idUser)
        {
            if(isset($users[$idUser]))
            {
                GroupUser::where("user_id", $idUser)->update(['rrhh_id' => $users[$idUser]->rrhh_id]);   
            }
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
            $table->dropColumn('rrhh_id');
        });
    }
}
