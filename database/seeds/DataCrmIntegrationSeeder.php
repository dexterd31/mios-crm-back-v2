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
        ApiConnection::create([
            'name'=>'Data CRM',
            'url'=>'https://develop.datacrm.la/datacrm/apsbsseguros/',
            'status'=>1,
            'json_send'=>'{
                "username":"test",
                "user_pass":"1",
            }',
            'form_id'=>1,
            'api_type'=>10
        ]);
    }
}
