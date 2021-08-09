<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use Helpers\MiosHelper;
use Validator;
use Illuminate\Support\Arr;

class DocumentTypeController extends Controller
{

    /**
     * @author Jhon Bernal
     * Método para lista de tipo de documento
     * @param null
     * @return mixed
     */
    public function list(MiosHelper $miosHelper){
        $documentType=DocumentType::all();
        return $miosHelper->jsonResponse(true,200,'document type',$documentType);
    }

    
    /**
     * @author Jhon Bernal
     * Método para crear de tipo de documento
     * @param $request
     * @return mixed
     */
    public function create(Request $request,MiosHelper $miosHelper){
        $success = true;
        $validator = Validator::make(array_merge($request->all()),  
            array(
                'name_type_document' => 'required|unique:document_types'
            ),
            array(
                'required' => 'El parametro :attribute es requerido.',
                'unique' => 'El valor :input ya existe  .',
                'exists' => 'El parametro :input no existe en la base de datos.'
            )
        );
        if ($validator->fails()) {
            $success = false;
            $code = 424;
            $keyMessage = 'message';
            $data = Arr::collapse($validator->errors()->messages());

        }
   
        try {
            if (!$success) {
                $documentType = DocumentType::where('name_type_document',$request->name_type_document)->first();
                $documentType= new DocumentType();
                $documentType->name_type_document = $request->name_type_document;
                $documentType->save();

                $success = true;
                $code = 200;
                $keyMessage = 'documentClient'; 
                $data = $documentType;
            }
        } catch (\Throwable $th) {
            $success = false;
            $code = 424;
            $keyMessage = 'message'; 
            $data = $th->getMessage();
        }
        return $miosHelper->jsonResponse($success, $code, $keyMessage, $data);
    }

     /**
     * @author Jhon Bernal
     * Método para actualizar de tipo de documento
     * @param $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request,$id,MiosHelper $miosHelper){
        $type = $request->name_type_document;
        $success = true;
        
        $validator = Validator::make(array_merge($request->all(),array('id'=>$id)),  
            array(
                'name_type_document' => 'required|unique:document_types',
                'id' => 'required|exists:document_types',
            ),
            array(
                'required' => 'El parametro :attribute es requerido.',
                'unique' => 'El valor :input ya existe  .',
                'exists' => 'El parametro :input no existe en la base de datos.'
            )
        );
        if ($validator->fails()) {
            $success = false;
            $code = 424;
            $keyMessage = 'message';
            $data = Arr::collapse($validator->errors()->messages());

        }
          
        try {

            if ($success) {
                $documentType = DocumentType::find($id);
                $documentType->name_type_document = $request->name_type_document;
                $documentType->update();    
                $success = true;
                $code = 200;
                $keyMessage = 'documentClient'; 
                $data = $documentType;
            }
        } catch (\Throwable $th) {
            $success = false;
            $code = 424;
            $keyMessage = 'message'; 
            $data = $th->getMessage();
        }
        return $miosHelper->jsonResponse($success, $code, $keyMessage, $data);
    }
}
