<?php

namespace App\Traits;

use App\Models\Attachment;
use App\Models\Section;
use Carbon\Carbon;
use stdClass;

trait FindAndFormatValues
{
    /**
     * Valida que el field exista en el formulario, valida el tipo de dato y lo formatea de ser necesario,
     * @param $form_id : id del fomulario
     * @param $field_id: id del field a consultar
     * @param $value: valor del field que se está validando
     * @return stdClass : objeto que puede contener los siguientes atributos:
     *                      -   valid (boolean) : indica si la validación fue exitosa
     *                      -   value : retorna el valor formateado en caso que el atributo valid sea verdadero
     *                      -   message : retorna el mensaje de error en caso que el atributo valid sea falso
     */
    public function findAndFormatValues($form_id, $field_id, $value, $moneyConvert = false)
    {
        $response = new stdClass();
        $response->valid = false;
        $response->message = "";
        $fields = json_decode(Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first()->fields);

        if(count($fields) == 0){
            $response->message = "field not found";
            return $response;
        }
        $field = collect($fields)->filter(function($x) use ($field_id){
            return $x->id == $field_id;
        })->first();
        if(empty($field)){
            $response->message = "field not found";
            return $response;
        }
        if(($field->controlType == 'dropdown' || $field->controlType == 'autocomplete' || $field->controlType == 'radiobutton')){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                if(intval($value) == 0){
                    return $x->name == $value;
                }
                return $x->id == $value;
            })->first();
            if($field_name){
                $response->valid = true;
                $response->value = $field_name->id;
                $response->name = $field_name->name;
                return $response;
            }
            $response->message = "value $value not match";
            return $response;
        }elseif($field->controlType == 'datepicker'){
            if($value !="Invalid date"){
                $date = "";
                try {
                    if(is_int($value)){
                       //Se suma un dia pues producción le resta un dia a las fechas formato date de excel
                        $unix_date = (($value+1) - 25569) * 86400;
                        $date = Carbon::createFromTimestamp($unix_date)->format('Y-m-d');
                    }else{
                        $date = Carbon::parse(str_replace("/","-",$value))->format('Y-m-d');
                    }
                    $response->valid = true;
                    $response->value = $date;
                }catch (\Exception $ex){
                    $response->valid = false;
                    $response->message = "date $value is not a valid format";
                }
            }else{
                $response->valid = true;
                $response->value = '';
            }
            return $response;
        }elseif($field->controlType == 'file'){
            $attachment = Attachment::where('id',$value)->first();
            $response->valid = true;
            $response->value = url().'/api/attachment/downloadFile/'.$attachment->id;
            return $response;
        }elseif($field->controlType == 'multiselect'){
            $multiAnswer=[];
            foreach($value as $val){
                $field_name = collect($field->options)->filter(function($x) use ($val){
                    return $x->id == $val;
                })->first();
                if (is_null($field_name)) {
                    continue;
                } else {
                    $field_name = $field_name->name;
                }
                array_push($multiAnswer,$field_name);
            }
            $response->valid = true;
            $response->value = implode(",",$multiAnswer);
            return $response;
        }elseif($field->controlType == 'currency'){
            $response->valid = true;
            if($moneyConvert){
                $response->value = number_format(intval($value));
                return $response;
            }
            $response->value = str_replace(",","",$value);
            return $response;
        }else{
            $response->valid = true;
            $response->value = $value;
            return $response;
        }

    }
}
