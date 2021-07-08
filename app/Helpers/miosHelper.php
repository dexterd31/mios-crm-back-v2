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

    function validateKeyName($key, $key2, $key3, $key4, $key5, $key6, $key7, $key8, $section)
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
    function groupsByUserId($userId)
    {
        $groupsIds = [];
        $groups = GroupUser::where('user_id', $userId)->get();
        foreach ($groups as $group) {
            array_push($groupsIds, $group['id']);
        }
        return $groupsIds;
    }

    public function getArrayValues($key, $array)
    {
        $arrayValues = array();
        foreach ($array as $value)
        {
            if(is_object($array))
            {
                array_push($arrayValues, $value->$key);
            }
            elseif (is_array($array))
            {
                array_push($arrayValues, $value[$key]);
            }
        }
        return $arrayValues;
    }
    /**
     * @author: Leonardo Giraldo
     * Función para reemplasar los acentos en una cadena de texto
     * @param $str: String (Cadena de texto)
     * */
    function replaceAccents($str){
        $search = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        return str_replace($search, $replace, $str);
    }
}
