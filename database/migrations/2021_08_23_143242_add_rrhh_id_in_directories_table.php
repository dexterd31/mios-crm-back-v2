<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Directory;
use App\Models\User;
use PhpParser\Node\Stmt\Foreach_;
use Helpers\MiosHelper;
class AddRrhhIdInDirectoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $directories = Directory::all();
        $miosHelper = new MiosHelper();
        $idsUsers = $miosHelper->getArrayValues("user_id", $directories);

        $users = User::whereIn("id", $idsUsers)->get()->keyBy('id');
        foreach ($idsUsers as $idUser)
        {
            if(isset($users[$idUser]))
            {
                Directory::where("user_id", $idUser)->update(['rrhh_id' => $users[$idUser]->id]);   
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
        Schema::table('directories', function (Blueprint $table) {
            $table->dropColumn('rrhh_id');
        });
    }
}
