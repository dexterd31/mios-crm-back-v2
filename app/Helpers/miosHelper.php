<?php

namespace Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use App\Models\Section;

class MiosHelper
{
    // Funcion para paginar arreglos como respuesta del api
    function paginate($items, $perPage = 2, $page = null)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    // Funcion para la respuesta json
    function jsonResponse($success, $code, $keyMessage, $data)
    {
        $data = [
            "suceess"           => $success,
            "code"              => $code,
            "{$keyMessage}"     => $data
        ];
        return $data;
    }

    // Funcion para recibir jsonDecode
    function jsonDecodeResponse($data)
    {
        $json = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), true);
        return $json;
    }

    function validateKeyName($key, $key2, $key3, $key4, $key5, $key6, $key7, $section)
    {
        $firstNameArray = [
            'Nombre',
            'Primer nombre',
            'Nombre completo',
            'primer nombre',
            'Primer Nombre',
            'nombre',
            'Nombre Completo',
            'nombre completo'

        ];
        $middleNameArray = [
            'Segundo Nombre',
            'segundo Nombre',
            'segundo nombre',
            'Nombre',
            'Segundo nombre'
        ];
        $lastNameArray = [
            'Apellido',
            'Primer Apellido',
            'primer apellido',
            'apellidos',
            'Apellidos',
            'apellido'
        ];
        $secondLastNameArray = [
            'Apellido',
            'Segundo Apellido',
            'segundo apellido',
            'apellidos',
            'Apellidos',
            'Segundo apellido'
        ];
        $documentArray = [
            'Cédula',
            'Documento de Identidad',
            'Documento',
            'Numero de Documento',
            'No. Documento',
            'DNI'
        ];
        $phoneArray = [
            'Teléfono',
            'Celular',
            'Telefono',
            'Número de Teléfono',
            'Numero de Celular',
            'Fijo',
            'celular',
            'Teléfono de contacto'
        ];
        $emailArray = [
            'Email',
            'E-mail',
            'Correo Electrónico',
            'Correo',
            'Correo electrónico'
        ];
        $firstName = !empty($key) ? in_array($key, $firstNameArray) : false;
        $middleName = !empty($key2) ? in_array($key2, $middleNameArray) : false;
        $lastName = !empty($key3) ? in_array($key3, $lastNameArray) : false;
        $secondLastName = !empty($key4) ? in_array($key4, $secondLastNameArray) : false;
        $document = !empty($key5) ? in_array($key5, $documentArray) : false;
        $phone = !empty($key6) ? in_array($key6, $phoneArray) : false;
        $email = !empty($key7) ? in_array($key7, $emailArray) : false;

        if ($firstName) {
            $section['fields'][0]['key'] = 'firstName';
        }

        if ($middleName) {
            $section['fields'][1]['key'] = 'middleName';
        }

        if ($lastName) {
            $section['fields'][2]['key'] = 'lastName';
        }

        if ($secondLastName) {
            $section['fields'][3]['key'] = 'secondLastName';
        }

        if ($document) {
            $section['fields'][4]['key'] = 'document';
        }
        if ($phone) {
            $section['fields'][5]['key'] = 'phone';
        }
        if ($email) {
            $section['fields'][6]['key'] = 'email';
        }
        return $section['fields'];
    }

    // Funcion para hacer formatear el structureAnswer
    function structureAnswer($formId, $responseSection)
    {
        $sectionsFind   = Section::where('form_id', $formId)->get();
        $arraySections  = $this->getKeysValues($sectionsFind);
        $row            = array();
        $result         = [];

        $i = 0;
        foreach ($arraySections as $obj) {
            $register   = $obj;
            
            foreach($register as $key => $value){
                if (isset($responseSection[$i][$key]) != null || isset($responseSection[$i][$key]) != '') {
                    $row[$key] = $responseSection[$i][$key];
                } else {
                    $row[$key] = NULL;
                }
            }
            
            array_push($result, $row);
            $row = array();
            $i++;
            
        }
        return $result;
    }

    // Funcion para obtener un array con las key values de un formulario
    function getKeysValues($sections)
    {
        $arraySections      = array();
        $arrayTemporal      = [];
        $arrayFormAnswer    = [];

        //Se obtienen los registro de fields
        foreach ($sections as $section) {
            array_push($arrayTemporal, $section["fields"]);
        }

        //Se construye el array con los keyvalues
        foreach ($arrayTemporal as $obj) {
            $register   = $this->jsonDecodeResponse($obj);
            $count      = count($register);
            for ($i = 0; $i < $count; $i++) {
                $arraySections[$register[$i]['key']] = NULL;
            }
            array_push($arrayFormAnswer, $arraySections);
            $arraySections = array();
        }
        return $arrayFormAnswer;
    }

}
