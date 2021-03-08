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
                    'key'=> 'firstName',
                    'label'=> 'primer nombre',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [3,1])
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'middleName',
                    'label'=> 'segundo nombre',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1, 2]),
                    'seeRoles' => array(
                        [3,1])
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'lastName',
                    'label'=> 'primer apellido',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [3,1])
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'secondLastName',
                    'label'=> 'primer apellido',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [3,1])
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'document',
                    'label'=> 'Documento',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [3,1])
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
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [3,1])
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'age',
                    'label'=> 'edad',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [3,1])
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
                        'key'=> 'firstName',
                        'label'=> 'primer nombre',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'minlength'=>1,
                    'maxLength'=> 30,
                        'options'=>[],
                        'cols'=>1,
                        'inReport'=> true,
                        'editRoles' => array(
                            [1,2]),
                        'seeRoles' => array(
                            [3,1])
                    ],
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'middleName',
                        'label'=> 'segundo nombre',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'minlength'=>1,
                    'maxLength'=> 30,
                        'options'=>[],
                        'cols'=>1,
                        'inReport'=> true,
                        'editRoles' => array(
                            [1,2]),
                        'seeRoles' => array(
                            [3,1])
                    ],
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'lastName',
                        'label'=> 'primer apellido',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'minlength'=>1,
                    'maxLength'=> 30,
                        'options'=>[],
                        'cols'=>1,
                        'inReport'=> true,
                        'editRoles' => array(
                            [1,2]),
                        'seeRoles' => array(
                            [3,1])
                    ],
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'secondLastName',
                        'label'=> 'primer apellido',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'minlength'=>1,
                    'maxLength'=> 30,
                        'options'=>[],
                        'cols'=>1,
                        'inReport'=> true,
                        'editRoles' => array(
                            [1,2]),
                        'seeRoles' => array(
                            [3,1])
                    ],
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'document',
                        'label'=> 'Documento',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'minlength'=>1,
                    'maxLength'=> 30,
                        'options'=>[],
                        'cols'=>1,
                        'inReport'=> true,
                        'editRoles' => array(
                            [1,2]),
                        'seeRoles' => array(
                            [3,1])
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
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1]),
                    'seeRoles' => array(
                        [3])
                ],
                [
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'marca',
                    'label'=> 'marca',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'editRoles' => array(
                        [1,2]),
                    'seeRoles' => array(
                        [1])
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
