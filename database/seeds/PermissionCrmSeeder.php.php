<?php

use Illuminate\Database\Seeder;

class PermissionCrmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = array(
            //Administrador
            [
                'rol_id'    => 2,
                'module_id' => 1,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 1,
                'all'       => 1
            ],
            [
                'rol_id'    => 2,
                'module_id' => 2,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 1,
                'all'       => 1
            ],
            [
                'rol_id'    => 2,
                'module_id' => 3,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 1,
                'all'       => 1
            ],
            [
                'rol_id'    => 2,
                'module_id' => 4,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 1,
                'all'       => 1
            ],
            [
                'rol_id'    => 2,
                'module_id' => 5,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 1,
                'all'       => 1
            ],
            // Asesor
            [
                'rol_id'    => 1,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 1,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 1,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 1,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 1,
                'module_id' => 5,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 0,
                'all'       => 0
            ],
            // Supervisor-CRM
            [
                'rol_id'    => 3,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 1
            ],
            [
                'rol_id'    => 3,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 1
            ],[
                'rol_id'    => 3,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 1
            ],[
                'rol_id'    => 3,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 1
            ],
            [
                'rol_id'    => 3,
                'module_id' => 5,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 1
            ],
            // Radicador
            [
                'rol_id'    => 4,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 4,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 4,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 4,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 4,
                'module_id' => 5,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            // Solucionador
            [
                'rol_id'    => 5,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 5,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 5,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 5,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 5,
                'module_id' => 5,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            // Supervisor-ESCA
            [
                'rol_id'    => 6,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 6,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 6,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 6,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 6,
                'module_id' => 5,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            // Calidad
            [
                'rol_id'    => 7,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 7,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 7,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 7,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 7,
                'module_id' => 5,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 0,
                'all'       => 0
            ],
            // Datamarshall
            [
                'rol_id'    => 8,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 8,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 8,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 8,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 8,
                'module_id' => 5,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            // Backoffice
            [
                'rol_id'    => 9,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 9,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 9,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 9,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 9,
                'module_id' => 5,
                'save'      => 1,
                'view'      => 1,
                'edit'      => 1,
                'change'    => 0,
                'all'       => 0
            ],
            // Usuario externo
            [
                'rol_id'    => 10,
                'module_id' => 1,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 10,
                'module_id' => 2,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'status'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 10,
                'module_id' => 3,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],[
                'rol_id'    => 10,
                'module_id' => 4,
                'save'      => 0,
                'view'      => 1,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ],
            [
                'rol_id'    => 10,
                'module_id' => 5,
                'save'      => 0,
                'view'      => 0,
                'edit'      => 0,
                'change'    => 0,
                'all'       => 0
            ]
        );
    }
}
