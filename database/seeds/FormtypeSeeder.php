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
                'formsubtype_id' => '1',
                'name' => 'Venta',
                'description' => 'ventas',
                'key' => '1'
            ],
            [
                'formsubtype_id' => '2',
                'name' => 'Compra',
                'description' => 'compras',
                'key' => '2'
            ]
    );

    foreach($FormType as $type)
    {
        $FormType = new FormType();
        $FormType->formsubtype_id = $type['formsubtype_id'];
        $FormType->name = $type['name'];
        $FormType->description = $type['description'];
        $FormType->key = $type['key'];
        $FormType->save();
    }
    }
}
