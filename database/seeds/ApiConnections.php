<?php

use Illuminate\Database\Seeder;
use App\Models\ApiConnection;

class ApiConnections extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conexions = array(
            [
                'name'                      => 'Login Laika',
                'url'                       => 'https://apiv2.laika.com.co',
                'autorization_type'         => null,
                'token'                     => null,
                'other_autorization_type'   => 'api-key-client',
                'other_token'               => '$2y$10$DAtaTvXcuyIXd.sWT0gnLueKF0U83Cu49XxAdhQQBg0ytoTR4dd/u',
                'mode'                      => 'POST',
                'parameter'                 => null,
                'json_send'                 => '{"query": "mutation  {\n  loginUsers(user: \"dev.cos@laika.com.co\",\n    password: \"5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8\",\n    accountType: 1) {\n    status_transaction\n    code\n    message\n    tokenAuth\n  }\n}", "variables": {}}',
                'json_response'             => '{"data": {"loginUsers": {"code": 200, "message": "Inicio de sesin del usuario dev.cos@laika.com.co fue exitoso", "tokenAuth": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI0OSIsImp0aSI6ImU1MzQ0ZWY4MDQ5ZWY0ZDBkMmFhMGI4NTM5NGZkZWI1M2JiYzZhZGJhNjJhODcwYzRiOTI5M2E3NDZmMjI0YjhjZWZhYTlkOTI2ZjUxNjk0IiwiaWF0IjoxNjE2Njk0NTQ3LCJuYmYiOjE2MTY2OTQ1NDcsImV4cCI6MzE5NDUzMTM0Nywic3ViIjoiODU2Iiwic2NvcGVzIjpbXX0.Qfi9bW3eMAJtu5VOo4oUdUBeoMrT0wibC_2TUhSktz4eCIeZeRrJtrd5E7HwexiC0n3xpD0zMmtQJ6gOMHppv6D3N2usP71lLb6fnjhPy8YfUfqHVywx0cFsqCiEV9DErQhRSAq97qAY_6TbRPFgO_cZBSrtmlsukaXV49RU6svRl0B0OGkbfa3XErT7KYKsxaJgeIKMmTRXkTu9Cim1-_JToz0jKTSdLVN7L-flg_mXNZ9u3OZh3HDNPp0QUS1kBifB0x9Br_IkNr7ho_Hxk8CMwt7Vbaa7E5dDczmQfdhpiYSJX6XY_Etw5G9Oma4Fyr2NT2qbk5iRw2WAQjGGtCMwCFn6FFS5zLZTMrEHppw6MYfgQrUxjq4Uud7oAT0282SKPdJE1bEwrHq5QIIQuP97u5TkI6VLceq99Y70ZyAHqrx89yJ9dQFNP4C4XVLmb1C8sZSiRpK0qSQ-IuO5hSB2SVSaC2SIMppWv17DXEpnQfzU_WLLfdYWRh_9wK4xCYnqQIeuQXTkw6hNSnifIW2SuNW6vb_9IgifxAx8TnPYcpS6mmprnXPMjY9xiHBT2Ib1et15WroljzvBmlcwNRHrsrxpJVKHEBqG5ihmuBMS2rqBp0Ch99XU9GYT0lBg_dbZqs9xf_yKtR1cdGWwtZFnuwOUxIHa2ExWM-Ln1HY", "status_transaction": true}}}',
                'response_token'            => 'data.loginUsers.tokenAuth',
                'request_type'              => 1,
                'api_type'                  => 2,
                'status'                    => 1,
                'form_id'                   => 1
            ],
            [
                'name'                      => 'Laika consulta de usuario',
                'url'                       => 'https://apiv2.laika.com.co/consultant',
                'autorization_type'         => 'Bearer',
                'token'                     => null,
                'other_autorization_type'   => 'api-key-client',
                'other_token'               => '$2y$10$DAtaTvXcuyIXd.sWT0gnLueKF0U83Cu49XxAdhQQBg0ytoTR4dd/u',
                'mode'                      => 'POST',
                'parameter'                 => null,
                'json_send'                 => '{"query": "query ($phone: String, $email: String,$document: String){\n  users(phone:$phone, email: $email, document: $document){\n    code\n    message\n    status_transaction\n    users{\n      id\n      fullname\n      document_type_id\n      document\n      email\n      phone\n      laikamember\n      count_pets    \n      pet_user{\n        id\n        name\n        birthday\n        age_name\n        size\n        breed  \n        pet{\n          name\n        }\n      }\n      addresses{\n        id\n        address\n      }\n    }\n  }\n}\n\n", "variables": {"email": "$email", "phone": "$phone", "document": "$document"}}',
                'json_response'             => '{"data": {"users": {"code": 200, "users": [{"id": 1, "email": "david.raga@laika.com.co", "phone": "3115343303", "document": "17628715234", "fullname": "David Raga Renteria", "pet_user": [{"id": 211, "pet": {"name": "Perro"}, "name": "Brunito", "size": "Pequeo", "breed": "Chihuahueo", "age_name": "Cachorro", "birthday": "2021-03-25"}, {"id": 216, "pet": {"name": "Gato"}, "name": "Perdita", "size": "Pequeo", "breed": "criollo", "age_name": "Adulto", "birthday": "2021-03-25"}, {"id": 219, "pet": {"name": "Perro"}, "name": "Pepe", "size": "Pequeo", "breed": "Criollo", "age_name": "Cachorro", "birthday": "2021-03-25"}, {"id": 223, "pet": {"name": "Perro"}, "name": "Perro", "size": "Pequeo", "breed": "Schnauzer estndar", "age_name": "Cachorro", "birthday": "2021-03-25"}, {"id": 266, "pet": {"name": "Perro"}, "name": "Peludo", "size": "Pequeo", "breed": "American Staffordshire Terrier", "age_name": "Cachorro", "birthday": "2021-03-25"}, {"id": 274, "pet": {"name": "Perro"}, "name": "luna", "size": "Pequeo", "breed": "Beagle", "age_name": "Adulto", "birthday": "2021-03-25"}, {"id": 276, "pet": {"name": "Gato"}, "name": "garfield", "size": "Pequeo", "breed": "Alano espaol", "age_name": "Cachorro", "birthday": "2021-03-25"}, {"id": 345, "pet": {"name": "Perro"}, "name": "Prueba 5", "size": "Pequeo", "breed": "Beauceron", "age_name": "Cachorro", "birthday": "2021-03-25"}, {"id": 346, "pet": {"name": "Gato"}, "name": "123 prue a", "size": "Pequeo", "breed": "Alaskan malamute", "age_name": "Adulto", "birthday": "2018-01-09"}], "addresses": [{"id": 616, "address": "Calle 80, Bogot, Colombia"}, {"id": 617, "address": "Calle 19, Bogot, Colombia"}, {"id": 619, "address": "Calle 80, Bogot, Colombia"}, {"id": 620, "address": "Avenida Calle 100, Bogot, Colombia"}, {"id": 621, "address": "Calle 13, Bogot, Colombia"}, {"id": 660, "address": "Bogot -La Caro, Bogot, Colombia"}, {"id": 675, "address": "Calle 180"}, {"id": 676, "address": "Calle 10"}, {"id": 677, "address": "Portal Norte"}, {"id": 680, "address": "Calle 181 A # 9-72"}, {"id": 681, "address": "Calle 80"}, {"id": 3924, "address": "sdfasd"}, {"id": 3925, "address": "cll 13 c sur"}, {"id": 3927, "address": "cll 127 # 23"}, {"id": 3929, "address": "ciudad de mexico"}, {"id": 3931, "address": "cll 57 # 23 - 63 "}, {"id": 3938, "address": "Gjbb"}, {"id": 3943, "address": "Calle 134#50-45"}, {"id": 3970, "address": "123"}, {"id": 3972, "address": "Calle 8a #92-71"}, {"id": 3978, "address": "Cll 54C Sur #87-21"}, {"id": 4006, "address": "Calle 13Cs Calle 13Cs 24E-31"}, {"id": 4156, "address": "Mexicali"}, {"id": 4188, "address": "Carrera 2C #32 - 36"}, {"id": 4275, "address": "Calle 8a # 92 - 71"}, {"id": 4277, "address": "Lago onega"}, {"id": 4310, "address": "Calle 20 # 25 - 24"}, {"id": 4314, "address": "Calle 22 # 12 - 34"}, {"id": 4315, "address": "Calle 22 # 12 - 34"}, {"id": 4343, "address": "Avenida calle 123 # 09 - 18"}, {"id": 4344, "address": "Avenida Carrera 123 # 56 - 108"}, {"id": 4414, "address": "Calle  #  - "}, {"id": 4418, "address": "Lago onega #ext 123"}, {"id": 4433, "address": "14636 #ext 134"}, {"id": 4504, "address": "mexico"}, {"id": 4505, "address": "mm"}, {"id": 4726, "address": "Medelln "}, {"id": 150731, "address": "Lago onega 218, Modelo Pensil, Miguel Hidalgo, Ciudad de Mxico"}, {"id": 150737, "address": "Anaxagoras 735 401, Narvarte Poniente, Benito Jurez, Ciudad de Mxico 03020"}, {"id": 150742, "address": "Santiago apostol 430 10, San Jernimo Ldice, La Magdalena Contreras, Ciudad de Mxico 10200"}, {"id": 150762, "address": " lago onega 218, [object Object], Ocampo, Chihuahua 33333"}, {"id": 150767, "address": "Lago onoga 218, Chorro de Agua, Ocampo, Chihuahua 33333"}, {"id": 150768, "address": "Lago onega 218 6, La Magdalena, Ocampo, Chihuahua 33333"}, {"id": 150771, "address": "Lago onega 425gdhdhsya 6363gdhs, Cusaroachi, Ocampo, Chihuahua 33333"}], "count_pets": 9, "laikamember": "Si", "document_type_id": 1}], "message": "usuarios consultados correctamente", "status_transaction": true}}}',
                'response_token'            => null,
                'request_type'              => 2,
                'api_type'                  => 2,
                'status'                    => 1,
                'form_id'                   => 1
            ]
        );

        foreach ($conexions as $conexion ) {
            $Conexion = new ApiConnection();
            $Conexion->name                     = $conexion['name'];
            $Conexion->url                      = $conexion['url'];
            $Conexion->autorization_type        = $conexion['autorization_type'];
            $Conexion->token                    = $conexion['token'];
            $Conexion->other_autorization_type  = $conexion['other_autorization_type'];
            $Conexion->other_token              = $conexion['other_token'];
            $Conexion->mode                     = $conexion['mode'];
            $Conexion->parameter                = $conexion['parameter'];
            $Conexion->json_send                = $conexion['json_send'];
            $Conexion->json_response            = $conexion['json_response'];
            $Conexion->response_token           = $conexion['response_token'];
            $Conexion->request_type             = $conexion['request_type'];
            $Conexion->api_type                 = $conexion['api_type'];
            $Conexion->status                   = $conexion['status'];
            $Conexion->form_id                  = $conexion['form_id'];
            $Conexion->save();
        }
    }
}
