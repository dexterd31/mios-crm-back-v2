<?php

namespace App\Services;

use App\Http\Controllers\ClientNewController;
use App\Models\Directory;
use App\Models\KeyValue;
use App\Models\NotificationLeads;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TmkPymesServices
{
    private $leadFields;
    private $keyPrimary = "razon_social";

    /**
     * @param $formId
     * @return string
     */
    public function setAccount($formId)
    {
        $clientNewRequest = new Request();
        $clientNewRequest->replace([
            "form_id" => $formId,
            "information_data" => $this->buildClientInformationData(),
            "unique_indentificator" => $this->buildClientUniqueId()
        ]);
        $clienNewController = new ClientNewController();
        $clientNew = $clienNewController->create($clientNewRequest);

        $keyValue = null;
        $keysToDirectory = [];
        //$keysToSaveLocal = Section::getFields($formId, $this->leadColumns());

        // controller -> keyValueController -> create
        /*foreach ($keysToSaveLocal as $key => $value) {
            $keyValue = null;
            $valueDynamic = $this->leadFields[$value->key] ?? '';
            $keyValueToSave = [
                'form_id' => $formId,
                'client_new_id' => $clientNew->id,
                'key' => $value->key,
                'value' => $valueDynamic,
                'description' => null,
                'field_id' => $value->id
            ];
            KeyValue::create($keyValueToSave);

            $keysToDirectory[] = array(
                'id' => $value->id,
                'value' => $valueDynamic,
                'key' => $value->key
            );
        }*/
        // validar si se guarda o no
        /*Directory::create([
            'data' => json_encode($keysToDirectory),
            'rrhh_id' => 1, //NOTE: ID DE USUARIO QUEMADO EN EL .ENV POR AHORA
            'form_id' => $formId,
            'client_new_id' => $clientNew->id
        ]);*/

        NotificationLeads::create([
            'client_id' => 0,
            'phone' => $this->leadFields['telefono'],
            'form_id' => $formId,
            'createdtime' => Carbon::now()->format('Y-m-d H:i:s'),
            'id_datacrm' => $this->leadFields['razon_social'],
            'client_new_id' => $clientNew->id
        ]);

        return 'pendiente validar';
    }


    /**
     * @return false|string
     */
    private function buildClientInformationData()
    {
        $timeId = time();
        $values = [];
        $i = 0;
        foreach ($this->leadFields as $leadField) {
            $values[] = ['id' => $timeId + $i, 'value' => $this->cleanString($leadField)];
            $i++;
        }
        return json_encode($values);
    }

    /**
     * @return false|string
     */
    private function buildClientUniqueId()
    {
        return json_encode([
            'id' => time(),
            'key' => $this->keyPrimary,
            'label' => 'Razon social',
            'value' => "{$this->leadFields[$this->keyPrimary]}",
            'preloaded' => true,
            'isClientInfo' => true,
            'client_unique' => true
        ]);
    }

    private function cleanString($string)
    {
        $string = str_replace(' ', '-', $string);
        $unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');
        $str = strtr($string, $unwanted_array);
        return trim($str);

    }

    /**
     * @return string[]
     */
    public function leadColumns(): array
    {
        return [
            "nombre",
            "razon_social",
            "nit",
            "ciudad",
            "email",
            "tipo_telefono",
            "telefono",
            "extension",
            "producto",
            "optin",
            "glid",
            "utm_source",
            "utm_campaign",
            "utm_medium",
            "queu_promo",
            "referencia_producto",
            "canal_trafico",
            "resumen_plan"
        ];
    }

    /**
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->leadFields;
    }

    /**
     * @param mixed $leadFields
     */
    public function setLeadFields($leadFields): void
    {
        $this->leadFields = $leadFields;
    }

}
