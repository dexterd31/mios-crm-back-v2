<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attachment;

class AttachmentController extends Controller
{
    /**
     *
     *
     *
     */
    public function show($idAttach){
        return Attachment::where('id',$idAttach)->first();
    }

    /**
     *
     */
    public function downloadFile($id){
        $attachment = Attachment::findOrfail($id);
        return response()->download(storage_path("app/" . $attachment->source), $attachment->name);
    }
}
