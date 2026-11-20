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
        // Si la base de pruebas no esta levantada se omite: los tests que usan
        // su propia base en memoria (SQLite) no dependen de ella y no deben
        // fallar por eso. Los que si la usan fallaran igual en su consulta.
        try {
            DB::connection()->getPdo();
        } catch (\PDOException) {
            return;
        }

        DB::table('rol')->insertOrIgnore([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
    }
}
