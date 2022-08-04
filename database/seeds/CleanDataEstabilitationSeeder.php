<?php

use Illuminate\Database\Seeder;
use App\Models\Section;

class CleanDataEstabilitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sections = Section::all();
        foreach ($sections as $section)
        {
            $fields = json_decode($section->fields);

            foreach ($fields as &$field)
            {
                if(isset($field->dependencies))
                {
                    foreach ($field->dependencies as &$dependencie)
                    {
                        unset($dependencie->idFieldOld);
                        unset($dependencie->activators[0]->idOld);
                        foreach ($dependencie->options as &$option)
                        {
                            unset($option->idOld);
                        }
                    }
                }

            }
            $section->fields = json_encode($fields);
            $section->save();
        }

    }
}
