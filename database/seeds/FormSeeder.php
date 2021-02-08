<?php

use Illuminate\Database\Seeder;
use App\Models\Form;
class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Form = array(
            [
                'form_type_id' => '1',
                'name_form' => 'Formulario 1',
                'description' => 'formulario 1 para prueba',
                'key' => '1'
            ],
            [
                'form_type_id' => '1',
                'name_form' => 'SOAT',
                'description' => 'venta de SOAT para carro',
                'key' => '2'
            ]
        );

        foreach ($Form as $form)
        {
            $Form = new Form();
            $Form->form_type_id = $form['form_type_id'];
            $Form->name_form = $form['name_form'];
            $Form->description = $form['description'];
            $Form->key = $form['key'];
            $Form->save();
        }
    }
}
