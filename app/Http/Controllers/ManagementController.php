<?php

namespace App\Http\Controllers;

use App\Managers\DataBaseManager;
use App\Models\Section;
use App\Models\Tag;
use Illuminate\Http\Request;

class ManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['indexDataBaseManagement', 'test']]);
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

        [$clients, $tableColumns] = (new DataBaseManager)->listManagement($formId, $filterOptions);

        $nameColumns = ['updated_at' => 'Actualizado por última vez'];

        Section::where('form_id', $formId)->get('fields')
        ->each(function ($section) use ($tableColumns, &$nameColumns) {
            $fields = json_decode($section->fields);
            foreach ($fields as $field) {
                foreach ($tableColumns as $key => $value) {
                    if ($field->id == $value) {
                        $nameColumns[$key] = ucwords($field->label);
                    }
                }
            }
        });

        $tags = Tag::formFilter($formId)->get(['id', 'name']);

        return response()->json(['clients' => $clients, 'name_columns' => $nameColumns, 'tags' => $tags]);
    }

    public function test()
    {
        (new DataBaseManager)->createClients();
    }
}
