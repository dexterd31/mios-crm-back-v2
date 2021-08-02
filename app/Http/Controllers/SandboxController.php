<?php

namespace App\Http\Controllers;

use App\Events\NewDataCRMLead;
use App\Models\ApiConnection;
use Illuminate\Http\Request;
use App\Services\DataCRMService;
use App\Traits\RequestServiceHttp;
use Illuminate\Support\Facades\Log;

use function GuzzleHttp\json_decode;

class SandboxController extends Controller
{
    private $dataCrmService;
    use RequestServiceHttp;

    public function __construct(DataCRMService $dataCrmService)
    {
        $this->dataCrmService = $dataCrmService;
    }
    public function getContactsFromDataCRM(){

        $formsDataCRM = ApiConnection::where('api_type',10)->where('status',1)->get();
        foreach ($formsDataCRM as $key => $value) {
            $this->dataCrmService->getAccounts($value->form_id);
        }

    }

    public function getFields(){
       // return $this->dataCrmService->fliedsPotentials(5);
        $test = '[{"id": 1623365960247, "key": "firstName", "label": "Primer nombre", "value": "DANIEL ALBERTO MORENO ROZO", "preloaded": true}, {"id": 1623365960249, "key": "lastName", "label": "Primer apellido", "value": "DANIEL ALBERTO MORENO ROZO", "preloaded": true}, {"id": 1623365960251, "key": "document_type_id", "label": "Tipo de documento", "value": 1, "preloaded": false}, {"id": 1623365960252, "key": "document", "label": "No. documento", "value": "12321321", "preloaded": false}, {"id": 1623365960253, "key": "phone", "label": "Teléfono", "value": "3152874716", "preloaded": true}, {"id": 1623365960254, "key": "email", "label": "Correo electrónico", "value": "danielmoreno_2000@hotmail.com", "preloaded": true}, {"id": 1624543588306, "key": "tipo-producto8", "label": "Tipo producto", "value": "tu-bicicleta", "preloaded": true}, {"id": 1623366066085, "key": "registro0", "label": "Registro", "value": "qwewq", "preloaded": false}, {"id": 1623366150660, "key": "campaña1", "label": "Campaña", "value": "qwe", "preloaded": false}, {"id": 1623366197238, "key": "instancia2", "label": "Instancia", "value": "qwe", "preloaded": false}, {"id": 1623366279161, "key": "gestión3", "label": "Gestión", "value": "qwe", "preloaded": false}, {"id": 1623366339145, "key": "duración4", "label": "Duración", "value": "qwe", "preloaded": false}, {"id": 1623366423618, "key": "duración-llamada5", "label": "Duración Llamada", "value": "qwe", "preloaded": false}, {"id": 1623366816765, "key": "estado-registro6", "label": "Estado registro", "value": "qwe", "preloaded": false}, {"id": 1623366891661, "key": "estado-de-la-gestión7", "label": "Estado de la gestion", "value": "qwe", "preloaded": false}, {"id": 1623367342141, "key": "telefono-marcado8", "label": "Teléfono Marcado", "value": "qwe", "preloaded": false}, {"id": 1623367342149, "key": "intentos-telefono-marcado", "label": "Intentos telefono marcado", "value": "qwe", "preloaded": false}, {"id": 1623367342159, "key": "bonificacion", "label": "Bonificación", "value": "qwe", "preloaded": false}, {"id": 1623367342160, "key": "fecha-hora-agenda", "label": "Fecha Hora Agenda", "value": "qwe", "preloaded": false}, {"id": 1623367342161, "key": "teléfono-agenda", "label": "Teléfono Agenda", "value": "qwe", "preloaded": false}, {"id": 1623367342162, "key": "intentos-teléfono-agenda", "label": "Intentos Teléfono Agenda", "value": "qwe", "preloaded": false}, {"id": 1623367342165, "key": "resultado", "label": "Resultado", "value": "qwe", "preloaded": false}, {"id": 1623367342166, "key": "observaciones", "label": "Observaciones", "value": "qwe", "preloaded": false}, {"id": 1623367342167, "key": "usuario-ultima-modificacion", "label": "Usuario ultima modificacion", "value": "qe", "preloaded": false}, {"id": 1623367342168, "key": "nombres-usuario-ultima-modificación", "label": "Nombre usuario ultima modificacion", "value": "qwe", "preloaded": false}, {"id": 1623367342169, "key": "apellidos-usuario-ultima-modificación", "label": "Apellido usuario ultima modificacion", "value": "qwe", "preloaded": false}, {"id": 1623367342170, "key": "usuario-asignado", "label": "Usuario asignado", "value": "qwe", "preloaded": false}, {"id": 1623367342171, "key": "nombres-usuario-asignado", "label": "Nombre usuario asignado", "value": "qwe", "preloaded": false}, {"id": 1623367342172, "key": "apellidos-usuario-asignado", "label": "Apelido usuario asignado", "value": "qwe", "preloaded": false}, {"id": 1623367342173, "key": "usuario-ultima-gestión", "label": "Usuario ultima gestion", "value": "qwe", "preloaded": false}, {"id": 1623367342174, "key": "nombres-usuario-ultima-gestión", "label": "Nombre usuario ultima gestion", "value": "qwe", "preloaded": false}, {"id": 1623367342175, "key": "apellidos-usuario-ultima-gestión", "label": "Apellido usuario ultima gestion", "value": "qwe", "preloaded": false}, {"id": 1623367342176, "key": "tipo-gestión", "label": "Tipo gestion", "value": "qwe", "preloaded": false}, {"id": 1623367342177, "key": "fecha-creación", "label": "Fecha de Creación", "value": "qwe", "preloaded": false}, {"id": 1623367342178, "key": "hora-creación", "label": "Hora Creación", "value": "qwe", "preloaded": false}, {"id": 1623367342179, "key": "estado-qa", "label": "Estado QA", "value": "qwe", "preloaded": false}, {"id": 1623367342180, "key": "suc_ramo_pol_item", "label": "SUC_RAMO_POL_ITEM", "value": "qwe", "preloaded": true}, {"id": 1623792686133, "key": "grupo-negocio30", "label": "Grupo negocio", "value": "qwe", "preloaded": false}, {"id": 1623793251596, "key": "grupo-llamada31", "label": "Grupo llamada", "value": "qwe", "preloaded": false}, {"id": 1623793273184, "key": "id32", "label": "Id", "value": "qwe", "preloaded": false}, {"id": 1623793293700, "key": "cedula33", "label": "Cedula", "value": "qwe", "preloaded": false}, {"id": 1623793344031, "key": "nombres34", "label": "Nombres", "value": "qwe", "preloaded": false}, {"id": 1623793360094, "key": "apellidos35", "label": "Apellidos", "value": "qwe", "preloaded": false}, {"id": 1623793376773, "key": "edad36", "label": "Edad", "value": "qwe", "preloaded": false}, {"id": 1623793389601, "key": "celular37", "label": "Celular", "value": "qwe", "preloaded": false}, {"id": 1623793402553, "key": "teléfono38", "label": "Teléfono", "value": "qwe", "preloaded": false}, {"id": 1623793434776, "key": "email39", "label": "Email", "value": "qwe@qwe.com", "preloaded": false}, {"id": 1623793466372, "key": "marca40", "label": "Marca", "value": "qwe", "preloaded": false}, {"id": 1623794065387, "key": "modelo41", "label": "Modelo", "value": "qwe", "preloaded": false}, {"id": 1623794079648, "key": "año42", "label": "Ano", "value": "qwe", "preloaded": false}, {"id": 1623794093264, "key": "placa43", "label": "Placa", "value": "qwe", "preloaded": false}, {"id": 1623794108422, "key": "ciudad44", "label": "Ciudad", "value": "qwe", "preloaded": false}, {"id": 1623794133686, "key": "acepta-términos45", "label": "Acepta terminos", "value": "qwe", "preloaded": false}, {"id": 1623794151171, "key": "origen46", "label": "Origen", "value": "qwe", "preloaded": false}, {"id": 1623794167687, "key": "número-de-cotización47", "label": "Número de cotización", "value": "1", "preloaded": false}, {"id": 1623794200994, "key": "valor-de-cotización48", "label": "Valor de cotización", "value": "1qe", "preloaded": false}, {"id": 1623794232469, "key": "número-de-inspección49", "label": "Número de inspección", "value": "1", "preloaded": false}, {"id": 1623794250145, "key": "número-de-póliza50", "label": "Numero Poliza", "value": "1", "preloaded": false}, {"id": 1623794277009, "key": "fecha-de-nacimiento-del-cliente51", "label": "Fecha de nacimiento del cliente", "value": "2021-06-25T05:00:00.000Z", "preloaded": false}, {"id": 1623794306865, "key": "cálculo-de-la-edad-por-fecha-de-nacimiento52", "label": "Cálculo de la edad por fecha de nacimiento", "value": "1", "preloaded": false}, {"id": 1623794362865, "key": "marketingcode53", "label": "MarketingCode", "value": "qe", "preloaded": false}, {"id": 1623794388787, "key": "número-de-folio54", "label": "Número de folio", "value": "1", "preloaded": false}, {"id": 1623794414181, "key": "mes-de-creación55", "label": "Mes de creación", "value": "qwe", "preloaded": false}, {"id": 1623794864694, "key": "año-de-gestión56", "label": "Año de Gestión", "value": "qe", "preloaded": false}, {"id": 1623794881539, "key": "mes-de-gestión57", "label": "Mes de Gestión", "value": "qwe", "preloaded": false}, {"id": 1623794919522, "key": "días-semana58", "label": "Días Semana", "value": "1", "preloaded": false}, {"id": 1623794946322, "key": "hora-de-gestión59", "label": "Hora Gestion", "value": "qew", "preloaded": false}, {"id": 1623794969735, "key": "base-tp60", "label": "Base TP", "value": "qe", "preloaded": false}, {"id": 1623794988004, "key": "tipo-de-vehículo-61", "label": "Tipo de vehículo ", "value": "qwe", "preloaded": false}, {"id": 1623795014892, "key": "origen-e-lead62", "label": "Origen E-Lead", "value": "qwe", "preloaded": false}, {"id": 1623795030959, "key": "peso63", "label": "Peso", "value": "qwe", "preloaded": false}, {"id": 1623795042787, "key": "número-de-póliza64", "label": "Número de Póliza", "value": "1", "preloaded": false}, {"id": 1623795061115, "key": "valor-póliza65", "label": "Valor póliza", "value": "qwe", "preloaded": false}, {"id": 1623795214041, "key": "tipo-contacto66", "label": "Tipo contacto", "value": "qwe", "preloaded": false}, {"id": 1623795233794, "key": "base67", "label": "Base", "value": "qwe", "preloaded": false}, {"id": 1623795247966, "key": "gestionados-68", "label": "Gestionados ", "value": "qwe", "preloaded": false}, {"id": 1623795261849, "key": "creados69", "label": "Creados", "value": "qwe", "preloaded": false}, {"id": 1623795274070, "key": "contactos-generales70", "label": "Contactos generales", "value": "qew", "preloaded": false}, {"id": 1623795473039, "key": "contactos-generales-271", "label": "Contactos generales 2", "value": "qe", "preloaded": false}, {"id": 1623795502271, "key": "contactos-directos72", "label": "Contactos Directos", "value": "qwe", "preloaded": false}, {"id": 1623795519824, "key": "apartamentos73", "label": "Apartamentos", "value": "qwe", "preloaded": false}, {"id": 1623795536516, "key": "cotización74", "label": "Cotización", "value": "qwe", "preloaded": false}, {"id": 1623795558302, "key": "inspección75", "label": "Inspección", "value": "qew", "preloaded": false}, {"id": 1623795570533, "key": "número-de-apartamentos76", "label": "Número de apartamentos", "value": "qe", "preloaded": false}, {"id": 1623795589443, "key": "pendiente-pago77", "label": "Pendiente pago", "value": "qwe", "preloaded": false}, {"id": 1623795608672, "key": "efectivos78", "label": "Efectivos", "value": "qew", "preloaded": false}, {"id": 1623795623209, "key": "asesor79", "label": "Asesor", "value": "qwe", "preloaded": false}, {"id": 1623795635228, "key": "ciudad80", "label": "Ciudad", "value": "qe", "preloaded": false}, {"id": 1623795647273, "key": "hora-creación81", "label": "Hora Creación", "value": "qwe", "preloaded": false}, {"id": 1623795664377, "key": "día-creación82", "label": "Día Creación", "value": "qe", "preloaded": false}, {"id": 1623795686329, "key": "semana83", "label": "Semana", "value": "qwe", "preloaded": false}, {"id": 1623795697070, "key": "semana-creación84", "label": "Semana creación", "value": "qwe", "preloaded": false}, {"id": 1623795860057, "key": "barridos85", "label": "Barridos", "value": "qwe", "preloaded": false}, {"id": 1623790914875, "key": "tipo0", "label": "Tipo", "value": 2, "preloaded": false}, {"id": 1623859755743, "key": "gestión-nivel-1-moto2", "label": "Gestión nivel 1 Moto", "value": 1, "preloaded": false}, {"id": 1623859863581, "key": "gestión-nivel-2-efectivo3", "label": "Gestión nivel 2 efectivo", "value": 1, "preloaded": false}, {"id": 1623862099412, "key": "gestión-nivel-3-prospecto4", "label": "Gestión nivel 3 prospecto", "value": 1, "preloaded": false}, {"id": 1624025533392, "key": "tipificación-calidad0", "label": "Tipificación Calidad", "value": 1, "preloaded": false}, {"id": 1624025606195, "key": "confirmación-calidad1", "label": "Confirmación Calidad", "value": 1, "preloaded": false}, {"id": 1624025867295, "key": "tipificación-devuelta-0", "label": "Tipificación devuelta ", "value": 1, "preloaded": false}, {"id": 1624026004873, "key": "observación-corregido1", "label": "Observación Corregido", "value": "qweqw", "preloaded": false}, {"id": 1624564593452, "key": "account-id0", "label": "Account Id", "value": "11x66510", "preloaded": true}, {"id": 1624564724394, "key": "potential-id1", "label": "Potential Id", "value": "13x66511", "preloaded": true}]';
       return $this->dataCrmService->updatePotentials(5,json_decode($test),'13x66513');

    }

    public function testDataCRMProduction($formId){
       return $this->dataCrmService->getDataProductionTest($formId);
    }

    public function testPusher(){
        sleep(2);
        event( new NewDataCRMLead( 4 ) );
    }
    public function updatePotentials(){
        $this->baseUri = 'https://app.datacrm.la/datacrm/sbsseguros';

        $arr = $this->filedsPotentialsForms();

        foreach ($arr as $key => $value) {
           if($value->name === 'campaignid') array_splice( $arr,$key,1);
        }



        //campaignid
        Log::info($arr);
        return;

        $requestBody = array(
            'operation' => 'update',
            'sessionName' => '3f35bacb61047b11676ab',
            'element' => '{"potentialname":"NEG30767","potential_no":"NEG30767","amount":"","related_to":"11x68190","closingdate":"2021-08-29","opportunity_type":"","nextstep":"","leadsource":"","sales_stage":"Prospecto","assigned_user_id":"19x6","probability":"","campaignid":"1x207","createdtime":"2021-07-30T05:00:00.000Z","modifiedtime":"2021-07-30 14:52:40","modifiedby":"19x6","description":"","forecast_amount":"","isconvertedfromlead":"0","contact_id":"","potential_date":"","createdby":"19x6","sector":"","cf_891":"0","cf_893":"0","cf_895":"","cf_897":"","cf_899":"0","cf_901":"Contacto efectivo","cf_903":"En negociaci\\u00f3n - Prospecto de venta","cf_909":"Cliente espera propuesta de otro competidor","cf_911":"","cf_913":"","cf_915":"","cf_917":"0","cf_919":"","cf_921":"","cf_923":"0","cf_925":"","cf_927":"","cf_929":"0","cf_931":"","cf_933":"","cf_935":"","cf_937":"","cf_939":"","cf_941":"","cf_943":"","cf_945":"","cf_947":"","cf_963":"","cf_965":"","cf_967":"","cf_969":"","cf_975":"0","reason_loss":"","cf_996":"18414294","cf_998":"","cf_1002":"0","rdstationid":"","cf_1013":"0","cf_1015":"MINI","cf_1017":"MINI COOPER S","cf_1019":"2013","cf_1021":"MKU086","cf_1023":111,"cf_1025":"","cf_1029":"","cf_1031":"","cf_1035":"0","cf_1041":"Tu Auto","cf_1043":"","cf_1064":"","cf_1068":"","potentialname_pick":"","product_relatedid":"","asignedby_user_id":"","potentialsorigin_pick":"","public_url_rd":"","identificador":"","cf_1167":"","cf_1172":"","cf_1174":"","cf_1176":"","id":"13x68191","bill_city":"Bogota D.C.","accountname":"BRYAN  RICARDO  SUAREZ  ROJAS "}'
        );

        $this->post('/webservice.php', http_build_query($requestBody));


    }

    public function filedsPotentialsForms(){
        $data = $this->get('/webservice.php?operation=describe&sessionName=3f35bacb61047b11676ab&elementType=Potentials');
       return $data->result->fields;
    }


}
