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
                'name' => 'Productos',
                'observation' => 'venta de productos Laika',
                'key' => '1'
            ],
            [
                'name' => 'SOAT',
                'observation' => 'venta de SOAT para carro',
                'key' => '2'
            ]
        );

        foreach ($FormSubType as $subtype)
        {
            $FormSubType = new FormSubType();
            $FormSubType->name = $subtype['name'];
            $FormSubType->observation = $subtype['observation'];
            $FormSubType->key = $subtype['key'];
            $FormSubType->save();
        }

    }
}
