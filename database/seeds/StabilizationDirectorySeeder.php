<?php

use App\Models\Form;
use Illuminate\Database\Seeder;
use App\Models\Directory;
use App\Models\ClientNew;

class StabilizationDirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(env('ID_FORM'))
        {
            $forms = Form::where("id", env('ID_FORM'))->get();
        }
        else
        {
            $forms = Form::all();
        }

        $clientsNew = [];
        $clientNewList = [];
        $clientNewId = ClientNew::max('id') + 1000; 
        foreach ($forms as $form)
        {
            $i=0;
            $allFilds = $this->mergeSections($form->section);
            $documentFild = $this->getFildDocument($allFilds);
            foreach ($form->directory as $directory)
            {
                $clientUnique = null;
                $clientData = [];
                $this->command->info("Actualisando directory: ". $i++.", ".count($form->directory));
                $clientNew = ClientNew::where("cliet_old_id", $directory->client_id)->where("form_id", $directory->form_id)->first();
                if($clientNew)
                {
                    $directory->client_new_id = $clientNew->id;
                    $directory->save();
                    continue;
                }
                else if(in_array((Object)[$directory->form_id, $directory->client_id], $clientNewList))
                {
                    $clientNewIdAux = array_search((Object)[$directory->form_id, $directory->client_id], $clientNewList);
                    $directory->client_new_id = $clientNewIdAux;
                    $directory->save();
                    continue;
                }
                else
                {
                    $directorysData = json_decode($directory->data);
                    foreach ($directorysData as $directoryData)
                    {
                        foreach ($allFilds as $fild)
                        {

                            if($fild->id == $directoryData->id)
                            {                            
                                if(isset($fild->isClientInfo) && $fild->isClientInfo)
                                {
                                    array_push($clientData, [
                                        "id" => $directoryData->id,
                                        "value" => $directoryData->value,
                                    ]);
                                }
                                            
                                if(isset($fild->client_unique) && $fild->client_unique && $directoryData->value)
                                {
                                    $clientUnique = [(Object)[
                                        "label" => isset($fild->label) ? $fild->label : "no se encuntra label en el formAnswer" ,
                                        "preloaded" => true,
                                        "id" => $fild->id,
                                        "key" => $fild->key,
                                        "value" => $directoryData->value,
                                        "isClientInfo" => true,
                                        "client_unique" => true,
                                        "cliet_old_id" => $directory->client_id
                                    ]];
                                }
                                else if($fild->key == "phone" && $documentFild && $form->id == 13)
                                {
                                    $clientUnique = [(Object)[
                                        "label" => isset($documentFild->label) ? $documentFild->label : "no se encuntra label en el formAnswer" ,
                                        "preloaded" => true,
                                        "id" => $documentFild->id,
                                        "key" => $documentFild->key,
                                        "value" => $directoryData->value,
                                        "isClientInfo" => true,
                                        "client_unique" => true,
                                        "cliet_old_id" => $directory->client_id
                                    ]];
                                    
                                }
                            }
                        }
                    }
                }

                if(isset($clientUnique))
                {
                    $clientNewId++;
                    array_push($clientsNew,[
                        "id" => $clientNewId,
                        "information_data" => json_encode($clientData),
                        "unique_indentificator" => json_encode($clientUnique),
                        "form_id" => $directory->form_id,
                        "cliet_old_id" => $directory->client_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
        
                    $clientNewList[$clientNewId] = (Object)[$directory->form_id, $directory->client_id];
                    $directory->client_new_id = $clientNewId;
                    $directory->save();
                }
            }             
        }

        $this->command->info('Insertadno '.count($clientsNew).'  clientNew');
        $clientsNewChunk = array_chunk($clientsNew, 100);
        $qtd = 0;
        foreach ($clientsNewChunk as $clientNewChunk)
        {
            $this->command->info("guardando 100 ClientNew, $qtd ya insertados, de un total de ".count($clientsNew));
            ClientNew::insert($clientNewChunk);
            $qtd += 100;
        }
    }

    private function getFildDocument($filds)
    {
        foreach ($filds as $fild)
        {
            if($fild->key == 'document')
            {
                return $fild;
            }
        }
    }

    private function mergeSections($sections)
    {
        $allFilds = [];
        foreach ($sections as $section)
        {
            $sectionArray = json_decode($section->fields);
            $allFilds = array_merge($allFilds, $sectionArray);
        }
        return $allFilds;
    }
}
