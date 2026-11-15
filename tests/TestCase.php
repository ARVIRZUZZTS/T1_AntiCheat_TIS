<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los dos roles de referencia (docente / auxiliar): parte del suite los
        // consulta por nombre y parte los inserta. Se siembran de forma
        // idempotente para que ambos estilos funcionen sobre una base de
        // pruebas vacía, sin que los que insertan choquen.
        DB::table('rol')->insertOrIgnore([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
    }
}
