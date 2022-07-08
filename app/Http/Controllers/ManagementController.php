<?php

namespace App\Http\Controllers;

use App\Managers\DataBaseManager;
use App\Models\Section;
use Illuminate\Http\Request;

class ManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => 'test']);
    }

    /**
     * Retorna una lista de clientes y las columnas a usar en la tabla.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Request $request
     * @return Illuminate\Http\Response
     */
    public function indexDataBaseManagement($formId, Request $request)
    {
        $filterOptions = [];

        if ($request->method() == 'POST') {
            $filterOptions = [
                'tags' => $request->tags,
                'fromDate' => $request->from_date,
                'toDate' => $request->to_date,
            ];
        }

        [$clients, $tableColumns] = (new DataBaseManager)->listManagement($form_id, $filterOptions);

        $nameColumns = [];

        Section::where('form_id', $request->form_id)->get('fields')
        ->each(function ($section) use ($tableColumns, &$nameColumns) {
            $fields = json_decode($section->fields);
            foreach ($fields as $field) {
                if (in_array($field->id, $tableColumns)) {
                    $nameColumns[] = ucwords($field->label);
                }
            }
        });

        return response()->json(['clients' => $clients, 'name_columns' => $nameColumns]);
    }

    public function test()
    {
        (new DataBaseManager)->createClients();
    }
}
