<?php

use Illuminate\Database\Seeder;
use App\Models\Form;

class FieldsClientUniqueIdentificatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $forms = Form::all();
        foreach ($forms as $form)
        {
            foreach ($form->sections as $section)
            {
                if($section->type_section == 1)
                {
                    $fields = json_decode($section->fields);
                    foreach ($fields as $field)
                    {
                        $field->isClientInfo = true;
                        if($field->key == "document")
                        {
                            $form->fields_client_unique_identificator = json_encode($field);
                            break;
                        }
                    }
                    break;
                }
            }
            $form->save();
        }
    }
}
