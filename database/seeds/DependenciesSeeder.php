<?php

use Illuminate\Database\Seeder;
use App\Models\Form;

class DependenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $forms = Form::all();
        foreach($forms as $form)
        {
            foreach ($form->section as $section)
            {
                $dependencies = [];
                $fields = json_encode($section->fields);
                foreach ($fields as $field)
                {
                    if(isset($field->dependencies))
                    {
                        foreach ($field->dependencies as $dependencie)
                        {
                            if(!array_key_exists($dependencie->idField, $dependencies))
                            {
                                $dependencies[$dependencie->idField] = [];
                            }
                            array_push( $dependencies, (Object)[
                                "id" => $field->id,
                                "Label" => $field->id,
                                "options"=> $field->options
                            ]);
                        }
                    }
                    # code...
                }
            }
        }
    }
}

$Dependencies->1630445772796=[
    {
        "id"=1630445864052,
        "options"=[
            {
                "id": 1,
                "name": null
            }
        ],
        "Label"=""

    },
    {
        "id"=1630446261326,
        "options"=[
            {
                "id": 1,
                "name": null
            }
        ],
        "Label"=""

    }
];