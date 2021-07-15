<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use Helpers\MiosHelper;

class DocumentTypeController extends Controller
{

    /**
     * Jhon Bernal
     * 14/07/21
     * Método para lista de tipo de documento
     */
    public function list(MiosHelper $miosHelper){
        $documentType=DocumentType::all();
        return $miosHelper->jsonResponse(true,200,'document type',$documentType);
    }

    /**
     * Jhon Bernal
     * 14/07/21
     * Método para crear de tipo de documento
     */
    public function create(Request $request,MiosHelper $miosHelper){
        $documentType= new DocumentType();
        $documentType->name_type_document = $request->name_type_document;
        $documentType->save();
        return $miosHelper->jsonResponse(true,200,'document type',$documentType);
    }

    /**
     * Jhon Bernal
     * 14/07/21
     * Método para actualizar de tipo de documento
     */
    public function update($id,MiosHelper $miosHelper){
        $documentType = DocumentType::find($id);
        $documentType->name_type_document = $request->name_type_document;
        $documentType->update();
        return $miosHelper->jsonResponse(true,200,'document type',$documentType);
    }
}
