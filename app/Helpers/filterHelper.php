<?php

namespace Helpers;

use App\Models\FormAnswer;

class FilterHelper
{
    // funcion para filtar por base de datos
    function filterByDataBase($formId, $item1value, $item2value, $item3value) {
        // Se continua la busqueda por gestio o base de datos
        $form_answers = FormAnswer::where('form_id', $formId)
        ->where('structure_answer', 'like', '%' . $item1value . '%')
        ->orWhere('structure_answer', 'like', '%' . $item2value . '%')
        ->orWhere('structure_answer', 'like', '%' . $item3value . '%')
        ->with('client')->paginate(10);

        return $form_answers;
    }

    // funcion para filtar por gestiones de mios
    function filterByGestions($formId, $item1value, $item2value, $item3value) {
        
    }
}