<?php

namespace App\Services;

use App\Traits\RequestService;
use Tymon\JWTAuth\Facades\JWTAuth;


class RrhhService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.rrhh.base_uri');
        $this->secret = JWTAuth::getToken();
    }

    /**
     * Metodo que retorna una lista de usuarios
     * @param idsRrhh (Array) Arreglo con los ids rrhh del usuario solicitado
     * @author Carlos Galindez
     * @created 01/02/21
     */
    public function fetchUsers($idsRrhh = [])
    {
        $request = array(
            'userIds'=> $idsRrhh
        );
        return $this->request('GET', '/api/users/candidate?'.http_build_query($request));
    }

    /**
     * Metodo que retorna datos de una usuario desde rrhh
     * @param id UserID
     * @author Carlos Galindez
     * @creatd 02/02/21
     */
    public function fetchUser($id){
        return $this->request('GET', '/api/user/candidate/'.$id);
    }

    /**
     * Metodo que permite unir los nombre del solicitante que vienen como array a otro array
     * @param users es un array con la informacion de usuarios
     * @param arrJoin es el array con que se quiere hacer merge con users;
     * @author Carlos Galindez
     * @created 01/02/21
     */
    public function mergeUsers($users,$arrJoin,$arg,$entry){

        $users = collect($users);
        $users->each(function($e) use($arrJoin){
           $index = $arrJoin->search(function ($item) use($e) {
                return $item->applicant_user_id == $e->id;
            });
            $arrJoin[$index]['applicant'] = $e->name;
        });
        return $arrJoin;
    }

    /**
     * Metodo que permite solicitar y unir informacion de los usuarios solicitados
     * @param userIds [1,2,3,4]
     * @param arrMerge arreglo con data a unir
     * @param arg identificador del arrMerge que permite identificar el user id
     * @param return parametro tipo string que permite retornar la informacion del usuario dentro del arrMerge
     * @author Carlos Galindez
     * @created 02/02/2021
     */
    public function fecthUsersAndMerge($usersId,$arrMerge,$arg,$return){
        $requestBody = [
            'userIds'=>$usersId,
            'arrMerge'=>$arrMerge,
            'arg'=>$arg,
            'return'=>$return
        ];
        return $this->request('POST', '/api/users/merge', $requestBody);

    }

    /**
     * Metodo que permite crear un usuario en rrhh
     * @param requestBody array asociativo con datos de usuario
     * @return array asociativo con datos del usuario creado
     * @author Heber Ruiz
     * @created 02/09/2021
     */
    public function createCiuUser($requestBody)
    {
        return $this->request('POST', '/api/user/create', $requestBody);
    }

    public function changeUserState($rrhh_id, $state_id)
    {
        $requestBody = ['state_id' => $state_id];
        return $this->request('POST', '/api/user/changestate/'.$rrhh_id, $requestBody);
    }

    public function changeUserCampaign($rrhh_id, $campaign_id)
    {
        $requestBody = ['campaign_id' => $campaign_id];
        return $this->request('POST', '/api/user/changecampaign/'.$rrhh_id, $requestBody);
    }

    public function searchUsers($request = [])
    {
        return $this->request('GET', '/api/users/search?'.http_build_query($request));
    }

    public function searchUserByIdDocument($id_number, $id_type_id)
    {
        $requestBody = ['id_number' => $id_number, 'id_type_id' => $id_type_id];
        return $this->request('GET', '/api/user/findbyiddocument?'.http_build_query($requestBody));
    }

    public function fetchUsersByCampaign($id)
    {
        return $this->request('GET','/api/users/campaign/'.$id);
    }
}
