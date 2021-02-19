<?php

use Illuminate\Database\Seeder;
use App\Models\Section;
class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Sections = array(
            [
                'form_id' => '1',
                'name_section' => 'Datos personales',
                'type_section' => 1,
                'fields'=> array( 
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'name',
                    'label'=> 'nombre',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'lastname',
                    'label'=> 'apellido',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ])
            ],
            [
                'form_id' => '1',
                'name_section' => 'Datos de la mascota',
                'type_section' => 2,
                'fields' => array(
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'name',
                    'label'=> 'nombre',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'age',
                    'label'=> 'edad',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ])
             ],
             [
                'form_id' => '2',
                'name_section' => 'Datos personales',
                'type_section' => 1,
                'fields'=> array( 
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'nombre',
                    'label'=> 'nombre',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'apellido',
                    'label'=> 'apellido',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ])
            ],
            [
                'form_id' => '2',
                'name_section' => 'Datos del carro',
                'type_section' => 2,
                'fields' => array(
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'placa',
                    'label'=> 'placa',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'marca',
                    'label'=> 'marca',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'cols'=>'1'
                ])
            ]

        );

        foreach ($Sections as $section)
        {
            $Sections = new Section();
            $Sections->form_id = $section['form_id'];
            $Sections->name_section = $section['name_section'];
            $Sections->type_section = $section['type_section'];
            $Sections->fields = json_encode($section['fields']);
            $Sections->save();
        }
    }
}
