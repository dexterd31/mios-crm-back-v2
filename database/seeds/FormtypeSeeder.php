<?php

use Illuminate\Database\Seeder;
use App\Models\FormType;

class FormtypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $FormType = array(
            [
                'name_type' => 'Outbound',
                'description' => 'Ad',
                'key' => '1'
            ],
            [
                'name_type' => 'Inbound',
                'description' => 'compras',
                'key' => '2'
            ]
    );

    foreach($FormType as $type)
    {
        $FormType = new FormType();
        $FormType->name_type = $type['name_type'];
        $FormType->description = $type['description'];
        $FormType->key = $type['key'];
        $FormType->save();
    }
    }
}
