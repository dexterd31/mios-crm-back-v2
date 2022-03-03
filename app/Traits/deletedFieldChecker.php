<?php

namespace App\Traits;

use App\Models\Section;

trait deletedFieldChecker
{
    private function deletedFieldChecker($formId, $field_id)
    {
        $isDeleted = false;

        $section = Section::where('form_id', $formId)
            ->whereJsonContains('fields', ['id' => $field_id])
            ->first();

        if (!is_null($section)) {
            $field = collect(json_decode($section->fields))->filter(function($item) use ($field_id){
                return $item->id == $field_id;
            })->first();

            if(isset($field->is_deleted) && $field->is_deleted){
                $isDeleted = true;
            }
        } else {
            $isDeleted = true;
        }

        return $isDeleted;
    }
}
