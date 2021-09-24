<?php

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\Tray;

class StabilizationTraysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $total = 0;
        if(env('ID_FORM'))
        {
            $forms = Form::where("id", env('ID_FORM'))->get();
        }
        else
        {
            $forms = Form::all();
        }

        foreach ($forms as &$form)
        {
            $this->command->info("trays del formulario: ".$form->id);
            $allFilds=[];
            foreach ($form->section as $section)
            {
                $sectionArray = json_decode($section->fields);
                $allFilds = array_merge($allFilds, $sectionArray);
            }
            foreach ($form->trays as &$tray)
            {
                $total++;
                $newFilds = [];
                $fieldsTray = json_decode($tray->fields);
                foreach ($fieldsTray as &$fieldTray)
                {
                    foreach ($allFilds as $filds)
                    {
                        if($filds->id == $fieldTray->id)
                        {
                            array_push($newFilds, $filds); 
                        }
                    }
                }
                $tray->fields = json_encode($newFilds);
            }

            foreach ($form->trays as &$tray)
            {
                $newFildsTable = [];
                $fieldsTable = json_decode($tray->fields_table);
                foreach ($fieldsTable as &$fieldTray)
                {
                    foreach ($allFilds as $filds)
                    {
                        if($filds->id == $fieldTray->id)
                        {
                            array_push($newFildsTable, $filds); 
                        }
                    }
                }
                $tray->fields_table = json_encode($newFildsTable);
            }
        }

        $i=1;
        foreach ($forms as $form)
        {
            foreach ($form->trays as $tray)
            {
                $tray->save();
                $this->command->info("Actualizando trays del formulario: ".$form->id." , trays actualizados: .".$i++.", Total: $total");
            }
        }
    }
}
