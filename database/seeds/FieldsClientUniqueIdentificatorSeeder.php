<<<<<<< HEAD
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
        $keyDataClient = array(
            "firstName" => "first_name",
            "middleName" => "middle_name",
            "lastName" => "first_lastname",
            "secondLastName" => "second_lastname",
            "document_type_id" => "document_type_id",
            "document" => "document",
            "phone" => "phone",
            "email" => "email"
        );

        $forms = Form::all();
        foreach($forms as $form)
        {
            $filters = json_decode($form->filters);
            foreach ($filters as $filter)
            {
                if(array_key_exists($filter->key, $keyDataClient))
                {
                    $filter->isClientInfo = true;
                    if($filter->key == "document")
                    {
                        $filter->preloaded = true;
                        $filter->client_unique = true;
                        
                    }
                }
            }
            $form->filters = json_encode($filters);

            foreach($form->section as $section)
            {
                if($section->type_section == 1)
                {
                    $fields = json_decode($section->fields);
                    foreach ($fields as $field)
                    {
                        if($field->key == "document")
                        {
                            $form->fields_client_unique_identificator = json_encode($field);
                        }
                    }
                }
            }
            $form->save();
        }
    }
}
=======
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
>>>>>>> 9a01ced58d63a4a159d6e5c0d41a9969b8236399
