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
                'group_id' => 1,
                'form_type_id' => 1,
                'name_form' => 'Laika',
                'state' => 1,
                'seeRoles'=> ["asesor", "admin"],
                'tooltip' => ["have"=> false, "content"=> ''],
                'filters' => array(
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'document',
                        'label'=> 'No. Documento',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'options'=>[],
                        'cols'=>'1'
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
                        'cols'=>'1'
                    ],
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'email',
                        'label'=> 'Email',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'options'=>[],
                        'cols'=>'1'
                    ]
                )
            ],
            [

                'group_id' => 1,
                'form_type_id' => 1,
                'name_form' => 'SOAT',
                'state' => 1,
                'seeRoles'=> ["asesor", "admin"],
                'tooltip' => ["have"=> false, "content"=> ''],
                'filters' => array(
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'document',
                        'label'=> 'No. Documento',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'options'=>[],
                        'cols'=>'1'
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
                        'cols'=>'1'
                    ],
                    [
                        'type'=> 'text',
                        'controlType'=> 'textbox',
                        'key'=> 'email',
                        'label'=> 'Email',
                        'value'=> '',
                        'disabled'=> false,
                        'required'=> true,
                        'options'=>[],
                        'cols'=>'1'
                    ]
                )
                    ],
            [

                        'group_id' => 1,
                        'form_type_id' => 1,
                        'name_form' => 'Form dependencias',
                        'state' => 1,
                        'seeRoles'=> ["asesor", "admin"],
                        'tooltip' => ["have"=> false, "content"=> ''],
                        'filters' => array(
                            [
                                'type'=> 'text',
                                'controlType'=> 'textbox',
                                'key'=> 'document',
                                'label'=> 'No. Documento',
                                'value'=> '',
                                'disabled'=> false,
                                'required'=> true,
                                'options'=>[],
                                'cols'=>'1'
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
                                'cols'=>'1'
                            ],
                            [
                                'type'=> 'text',
                                'controlType'=> 'textbox',
                                'key'=> 'email',
                                'label'=> 'Email',
                                'value'=> '',
                                'disabled'=> false,
                                'required'=> true,
                                'options'=>[],
                                'cols'=>'1'
                            ]
                        )
                    ]
        );

        foreach ($Form as $form)
        {
            $Form = new Form();
            $Form->group_id = $form['group_id'];
            $Form->form_type_id = $form['form_type_id'];
            $Form->name_form = $form['name_form'];
            $Form->filters = json_encode($form['filters']);
            $Form->state = $form['state'];
            $Form->seeRoles = json_encode($form['seeRoles']);
            $Form->save();
        }
    }
}
