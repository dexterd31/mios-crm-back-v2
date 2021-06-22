<?php

use App\Models\ApiConnection;
use Illuminate\Database\Seeder;

class DataCrmIntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $crendentials = array(
           "username"=>"nancy.duran@sbseguros.co",
           "user_pass"=>"emSpdpBQCdWNm3DX"
        );
        ApiConnection::create([
            'name'=>'Data CRM',
            'url'=>'https://develop.datacrm.la/datacrm/apsbsseguros',
            'status'=>1,
            'json_send'=>json_encode($crendentials),
            'form_id'=>1,
            'api_type'=>10,
            'mode'=>'POST',
            'json_response'=>json_encode(array()),
            'request_type'=>0,

        ]);
    }
}
