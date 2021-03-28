<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function getClient($id){
        $client = Client::where('id',$id)->first();
        return $client;
    }
}
