<?php

use Illuminate\Database\Seeder;
use App\Models\Client;
class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clients = array([
            'campaign_id' => '1',
            'name_client' => 'Nicoll',
            'lastname' => 'Ramirez',
            'document' => '123456789',
            'email' => 'nicol@gmail.com',
            'phone' => '12233243',
            'basic_information' => array(
                [
                    'direccion' => 'calle1234343',
                    'mascota' => 'lucas'
                ])   
        ]);

        foreach ($clients as $client)
        {
            $clients = new Client();
            $clients->campaign_id = $client['campaign_id'];
            $clients->name_client = $client['name_client'];
            $clients->lastname = $client['lastname'];
            $clients->document = $client['document'];
            $clients->email = $client['email'];
            $clients->phone = $client['phone'];
            $clients->basic_information = json_encode($client['basic_information']);
            $clients->save();
        }
    }

}
