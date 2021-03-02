<?php 
namespace Helpers;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class MiosHelper {
    // Funcion para paginar arreglos como respuesta del api
    function paginate($items, $perPage = 2, $page = null) {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, [
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
        ]);
    }
}


