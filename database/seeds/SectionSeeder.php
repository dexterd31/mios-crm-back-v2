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
                'name_section' => 'Datos básicos del cliente',
                'type_section' => 1,
                'fields'=> array( 
                [
                    'id'=>1616799311180,
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
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id'=>1616799311181,
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
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id'=>1616799311182,
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
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id'=>1616799311183,
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
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id'=>1616799311184,
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
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id'=>1616799311185,
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'phone',
                    'label'=> 'Teléfono',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id'=>1616799311186,
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'email',
                    'label'=> 'Email',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>[],
                    'cols'=>1,
                    'inReport'=> true,
                    'preloaded' => true,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                    ],
                    [
                        "type"=> "options",
                        "key"=> "document_type_id",
                        "controlType"=> "dropdown",
                        "label"=> "Tipo de documento",
                        "value"=> "",
                        "required"=> false,
                        "canAdd"=> false,
                        "options"=> array(
                            [
                            
                                "id"=> 1,
                                "name"=> "Cédula de ciudadania"
                            ],
                           [
                                "id"=> 2,
                                "name"=> "Tarjeta de ciudadania"
                           ],
                           [
                                "id"=> 3,
                                "name"=> "NIT"
                           ],
                           [
                                "id"=> 3,
                                "name"=> "Cédula de extranjería"
                           ]),
                            "minLength"=> null,
                            "maxLength"=> null,
                            "inReport"=> true,
                            'preloaded' => true,
                            'dependencies' => [],
                            "disabled"=> false,
                            "cols"=> 1,
                            "editRoles"=> [],
                            "seeRoles"=> []
                    ])
            ],     
            [
                'form_id' => '1',
                'name_section' => 'Datos de la mascota',
                'type_section' => 2,
                'fields' => array(
                [
                    'id' => 616799311187,
                    'type'=> 'text',
                    'controlType'=> 'textbox',
                    'key'=> 'name',
                    'label'=> 'nombre',
                    'value'=> '',
                    'disabled'=> false,
                    'required'=> true,
                    'minlength'=>1,
                    'maxLength'=> 30,
                    'options'=>array([]),
                    'cols'=>1,
                    'inReport'=> true,
                    'preloaded' => false,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ],
                [
                    'id' => 1616799311188,
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
                    'preloaded' => false,
                    'dependencies' => [],
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin','asesor')
                ])
             ],
             [
                'form_id' => '2',
                'name_section' => 'Datos básicos del cliente',
                'type_section' => 1,
                'fields'=> array( 
                    [
                        'id' => 1616799311180,
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
                        'preloaded' => true,
                        'dependencies' => [],
                        'editRoles' => array(
                            'admin','supervisor','asesor'),
                        'seeRoles' => array(
                            'admin','supervisor','asesor')
                    ],
                    [
                        'id' => 1616799311181,
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
                        'preloaded' => true,
                        'dependencies' => [],
                        'editRoles' => array(
                            'admin','supervisor','asesor'),
                        'seeRoles' => array(
                            'admin','asesor')
                    ],
                    [
                        'id' => 1616799311182,
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
                        'preloaded' => true,
                        'dependencies' => [],
                        'editRoles' => array(
                            'admin','supervisor','asesor'),
                        'seeRoles' => array(
                            'admin','asesor')
                    ],
                    [
                        'id' => 1616799311183,
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
                        'preloaded' => true,
                        'dependencies' => [],
                        'editRoles' => array(
                            'admin','supervisor','asesor'),
                        'seeRoles' => array(
                            'admin','asesor')
                    ],
                    [
                        'id' => 1616799311184,
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
                        'preloaded' => true,
                        'dependencies' => [],
                        'editRoles' => array(
                            'admin','supervisor','asesor'),
                        'seeRoles' => array(
                            'admin','asesor')
                        ],
                        [
                            'id' => 1616799311185,
                            'type'=> 'text',
                            'controlType'=> 'textbox',
                            'key'=> 'phone',
                            'label'=> 'Teléfono',
                            'value'=> '',
                            'disabled'=> false,
                            'required'=> true,
                            'minlength'=>1,
                            'maxLength'=> 30,
                            'options'=>[],
                            'cols'=>1,
                            'inReport'=> true,
                            'preloaded' => true,
                            'dependencies' => [],
                            'editRoles' => array(
                                'admin','supervisor'),
                            'seeRoles' => array(
                                'admin','asesor')
                        ],
                        [
                            'id' => 1616799311186,
                            'type'=> 'text',
                            'controlType'=> 'textbox',
                            'key'=> 'email',
                            'label'=> 'Email',
                            'value'=> '',
                            'disabled'=> false,
                            'required'=> true,
                            'minlength'=>1,
                            'maxLength'=> 30,
                            'options'=>[],
                            'cols'=>1,
                            'inReport'=> true,
                            'preloaded' => true,
                            'dependencies' => [],
                            'editRoles' => array(
                                'admin','supervisor'),
                            'seeRoles' => array(
                                'admin','asesor')
                        ])
            ],
            [
                'form_id' => '2',
                'name_section' => 'Datos del carro',
                'type_section' => 2,
                'fields' => array(
                [
                    'id' => 1616799311187,
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
                    'dependencies' => [],
                    'preloaded' => false,
                    'editRoles' => array(
                        'admin'),
                    'seeRoles' => array(
                        'admin','supervisor','asesor')
                ],
                [
                    'id' => 1616799311188,
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
                    'dependencies' => [],
                    'preloaded' => false,
                    'editRoles' => array(
                        'admin','supervisor'),
                    'seeRoles' => array(
                        'admin')
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
