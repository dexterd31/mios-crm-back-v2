<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Upload;
use App\Models\User;
use Helpers\MiosHelper;

class AddRrhhIdInUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $uploads = Upload::all();
        $miosHelper = new MiosHelper();
        $idsUsers = $miosHelper->getArrayValues("user_id", $uploads);
        $users = User::whereIn("id", $idsUsers)->get()->keyBy('id');
        foreach ($idsUsers as $idUser)
        {
            if(isset($users[$idUser]))
            {
                Upload::where("user_id", $idUser)->update(['rrhh_id' => $users[$idUser]->id]);   
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
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('rrhh_id');
        });
    }
}
