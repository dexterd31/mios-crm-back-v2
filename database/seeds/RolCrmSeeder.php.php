<?php

use Illuminate\Database\Seeder;
use App\Models\RolCrm;

class RolCrmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = array(
            [
                'ciu_id'    => 9,
                'name'      => 'Asesor',
                'key'       => 'asesor',
                'status'    => 1
            ],
            [
                'ciu_id'    => 10,
                'name'      => 'Administrador',
                'key'       => 'admin',
                'status'    => 1
            ],
            [
                'ciu_id'    => 11,
                'name'      => 'Supervisor-CRM',
                'key'       => 'supervisor_crm',
                'status'    => 1
            ],
            [
                'ciu_id'    => 12,
                'name'      => 'Radicador',
                'key'       => 'radicador',
                'status'    => 1
            ],
            [
                'ciu_id'    => 13,
                'name'      => 'Solucionador',
                'key'       => 'solucionador',
                'status'    => 1
            ],
            [
                'ciu_id'    => 14,
                'name'      => 'Supervisor-ESCA',
                'key'       => 'supervisor_esca',
                'status'    => 1
            ],
            [
                'ciu_id'    => 0,
                'name'      => 'Calidad',
                'key'       => 'calidad',
                'status'    => 1
            ],
            [
                'ciu_id'    => 0,
                'name'      => 'Datamarshall',
                'key'       => 'datamarshall',
                'status'    => 1
            ],
            [
                'ciu_id'    => 0,
                'name'      => 'Backoffice',
                'key'       => 'backoffice',
                'status'    => 1
            ],
            [
                'ciu_id'    => 0,
                'name'      => 'Usuario externo',
                'key'       => 'usuario_externo',
                'status'    => 1
            ]
        );

        foreach( $roles as $rol) {
            $Rol = new RolCrm();
            $Rol->ciu_id    = $rol['ciu_id'];
            $Rol->name      = $rol['name'];
            $Rol->key       = $rol['key'];
            $Rol->status    = $rol['status'];
            $Rol->save();
        }
    }
}
