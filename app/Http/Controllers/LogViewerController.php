<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    public function logs($path = null)
    {
        if (is_null($path)) {
            $path = 'logs';
        } else {
            $path = "logs/$path";
        }

        if (is_dir(storage_path($path))) {
            $result = $this->listDirectory($path);
        } else {
            $result = $this->readLog("$path.log");
        }
        
        return response()->json($result);
    }
    
    private function listDirectory($path)
    {
        try {
            $directory = opendir(storage_path($path));
    
            $directories = [];
            $files = [];
            while ($file = readdir($directory)) {
                if( $file != "." && $file != ".."){
                    $subPath = "$path/$file";
                    if(is_dir(storage_path($subPath)) ){
                        $directories[] = [
                            'directory' => $subPath,
                            'content' => $this->listDirectory($subPath)
                        ];
                        
                    } else {
                        $extension = array_reverse(explode('.', $file))[0];
                        if ($extension == 'log') $files[] = explode('.', $subPath)[0];
                    }
                }
            }
    
            return compact('directories', 'files');
        } catch (Exception $e) {
            return 'No existe el directorio o archivo solicitado.';
        }
    }

    private function readLog($path)
    {
        $logData = [];
        $file = fopen(storage_path($path), 'r');
        $index = 0;

        while (!feof($file)) {
            $line = fgets($file);
            if (strpos($line, 'local.')) {
                $index++;
                $logData[$index] = $line;
            } else {
                if (isset($logData[$index])) {
                    $logData[$index] .= $line;
                }
            }
        }
        
        fclose($file);

        return $logData;
    }

    public function clearLog($path)
    {
        try {
            $path = "logs/$path.log";
            $file = fopen(storage_path($path), 'w');
            fwrite($file, '');
            fclose($file);

            return 'Archivo limpiado';
        } catch (Exception $e) {
            return 'No se encontro archivo';
        }
    }
}
