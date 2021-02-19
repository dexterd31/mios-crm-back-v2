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
        $clients = array(
            [
                'name_client' => 'Nicoll',
                'lastname' => 'Ramirez',
                'document' => '123456789',
                'email' => 'nicol@gmail.com',
                'phone' => '12233',
                'basic_information' => array(
                    [
                        'direccion' => 'calle1234343',
                        'mascota' => 'lucas'
                    ])   
            ],
            [
                'name_client' => 'Juan',
                'lastname' => 'Rodriguez',
                'document' => '57891234',
                'email' => 'juan@gmail.com',
                'phone' => '31652',
                'basic_information' => array(
                    [
                        'direccion' => 'calle1234343',
                        'mascota' => 'pato'
                    ])   
            ]);

        foreach ($clients as $client)
        {
            $clients = new Client();
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
