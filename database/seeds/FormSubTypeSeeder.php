<?php

use Illuminate\Database\Seeder;
use App\Models\FormSubType;

class FormSubTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $FormSubType = array(
            [
                'name_subtype' => 'Productos',
                'observation' => 'venta de productos Laika',
                'key' => '1'
            ],
            [
                'name_subtype' => 'SOAT',
                'observation' => 'venta de SOAT para carro',
                'key' => '2'
            ]
        );

        foreach ($FormSubType as $subtype)
        {
            $FormSubType = new FormSubType();
            $FormSubType->name_subtype = $subtype['name_subtype'];
            $FormSubType->observation = $subtype['observation'];
            $FormSubType->key = $subtype['key'];
            $FormSubType->save();
        }

    }
}
