<?php

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $channels = array(
            [
                'name_type_document' => 'Cédula de Ciudadanía'
            ],
            [
                'name_type_document' => 'Tarjeta de Identidad'
            ],
            [
                'name_type_document' => 'NIT'
            ],
            [
                'name_type_document' => 'Cédula de Extranjería'
            ]
        );

        foreach ($channels as $channel)
        {
            $Channel = new DocumentType();
            $Channel->name_type_document = $channel['name_type_document'];
            $Channel->save();
        }
    }
}
