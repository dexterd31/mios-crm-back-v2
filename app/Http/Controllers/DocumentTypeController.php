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
        try {
            $documentType= new DocumentType();
            $documentType->name_type_document = $request->name_type_document;
            $documentType->save();
            return $miosHelper->jsonResponse(true,200,'created',$documentType);
        } catch (\Throwable $th) {
            return $miosHelper->jsonResponse(false,424,'Error en crear',$th->getMessage());
        }
    }

    /**
     * Jhon Bernal
     * 14/07/21
     * Método para actualizar de tipo de documento
     */
    public function update(Request $request,$id,MiosHelper $miosHelper){
        try {
            $documentType = DocumentType::find($id);
            $documentType->name_type_document = $request->name_type_document;
            $documentType->update();
        return $miosHelper->jsonResponse(true,200,'updates',$documentType);
        } catch (\Throwable $th) {
            return $miosHelper->jsonResponse(false,424,'Error en actualizar',$th->getMessage());
        }
    }
}
