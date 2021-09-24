<?php

use Illuminate\Database\Seeder;
use App\Models\FormAnswer;
use App\Models\KeyValue;
use App\Models\ClientNew;

class StabilizationKeyValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if(env('ID_FORM'))
        {
            $keyValues = KeyValue::where("form_id", env('ID_FORM'))->get();
        }
        else
        {
            $keyValues = KeyValue::all();
        }
        
        $i = 0;
        $total = count($keyValues);
        foreach ($keyValues as $keyValue)
        {
            $clientNew = ClientNew::where("form_id", $keyValue->form_id)->where("cliet_old_id", $keyValue->client_id)->first();
            $keyValue->client_new_id = isset($clientNew) ? $clientNew->id : 0;
            $keyValue->save();
            $this->command->info("Actualizando keyValues del formulario: ".$keyValue->form_id." , keyValues actualizados: .".$i++.", Total: $total");
        }
    }
}
