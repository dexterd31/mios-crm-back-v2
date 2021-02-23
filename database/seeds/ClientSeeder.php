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
                'first_name' => 'Nicoll',
                'middle_name' => 'Natalia',
                'first_lastname' => 'Ramirez',
                'second_lastname' => 'Manjarres',
                'document' => '123456789',
                'document_type_id' => 1,
               
            ],
            [
                'first_name' => 'Juan',
                'middle_name' => 'Felipe',
                'first_lastname' => 'Rodriguez',
                'second_lastname' => 'Lopez',
                'document' => '57891234',
                'document_type_id' => 1,
               
            ]);

        foreach ($clients as $client)
        {
            $clients = new Client();
            $clients->first_name = $client['first_name'];
            $clients->middle_name = $client['middle_name'];
            $clients->first_lastname = $client['first_lastname'];
            $clients->second_lastname = $client['second_lastname'];
            $clients->document = $client['document'];
            $clients->document_type_id = $client['document_type_id'];
            $clients->save();
        }
    }

}
