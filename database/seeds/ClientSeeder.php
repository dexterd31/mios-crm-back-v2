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
                'first_name' => 'Jair',
                'middle_name' => 'Armando',
                'first_lastname' => 'Celis',
                'second_lastname' => 'Torrado',
                'document' => '1032399970',
                'document_type_id' => 1,
                'phone' => '3207671490',
                'email' => 'jair.celis.torrado@gmail.com'
               
            ],
            
            [
                'first_name' => 'Juan',
                'middle_name' => 'Felipe',
                'first_lastname' => 'Rodriguez',
                'second_lastname' => 'Lopez',
                'document' => '57891234',
                'document_type_id' => 1,
                'phone' => '7654321',
                'email' => 'juan@gmail.com'
               
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
            $clients->phone = $client['phone'];
            $clients->email = $client['email'];
            $clients->save();
        }
    }

}
