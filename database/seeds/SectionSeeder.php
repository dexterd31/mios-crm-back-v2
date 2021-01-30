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
                'fields'=> array( 
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'phone',
                    'label'=> 'telefono',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'order'=>'1'
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'phone',
                    'label'=> 'telefono',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'order'=>'1'
                ])
            ],
            [
                'form_id' => '2',
                'name_section' => 'Seccion 2',
                'fields' => array(
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'phone',
                    'label'=> 'telefono',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'order'=>'1'
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'phone',
                    'label'=> 'telefono',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'options'=>[],
                    'order'=>'1'
                ])
            ]
        );

        foreach ($Sections as $section)
        {
            $Sections = new Section();
            $Sections->form_id = $section['form_id'];
            $Sections->name_section = $section['name_section'];
            $Sections->fields = json_encode($section['fields']);
            $Sections->save();
        }
    }
}
