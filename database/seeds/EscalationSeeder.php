<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EscalationSeeder extends Seeder
{
    /**
     * Seeds para modulo de carteras
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('escalations')->insert([
            'name' => 'prueba',
            'form_id' => 1,
            'fields' => '[
                {
                    "id": 1616608110733,
                    "value": ""
                }
            ]',
            'asunto_id' => 1,
            'estado_id' => 1,
            'campaign_id' => 1,
            'state' => 1
        ]);

    }
}