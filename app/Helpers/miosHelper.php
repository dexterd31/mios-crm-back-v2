<?php

namespace Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use App\Models\GroupUser;

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

    function validateKeyName($key, $key2, $key3, $key4, $key5, $key6, $key7,$key8, $section)
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
        $documentTypeArray = [
            'Tipo de documento',
            'Tipo Documento',
            'Tipo documento',
            'tipo documento',
        ];
        $firstName = !empty($key) ? in_array($key, $firstNameArray) : false;
        $middleName = !empty($key2) ? in_array($key2, $middleNameArray) : false;
        $lastName = !empty($key3) ? in_array($key3, $lastNameArray) : false;
        $secondLastName = !empty($key4) ? in_array($key4, $secondLastNameArray) : false;
        $document = !empty($key5) ? in_array($key5, $documentArray) : false;
        $phone = !empty($key6) ? in_array($key6, $phoneArray) : false;
        $email = !empty($key7) ? in_array($key7, $emailArray) : false;
        $documenttype = !empty($key8) ? in_array($key8, $documentTypeArray) : false;

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
        if ($documenttype) {
            $section['fields'][7]['key'] = 'document_type_id';
        }
        return $section['fields'];
    }

    // Funcion para obtner los grupos por id de usuario
    function groupsByUserId($userId) {
        $groups = GroupUser::where('user_id', $userId)->get();
        return $groups;
    }
        
}
