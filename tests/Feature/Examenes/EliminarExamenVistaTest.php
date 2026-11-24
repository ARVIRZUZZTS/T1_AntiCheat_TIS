<?php

namespace Tests\Feature\Examenes;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EliminarExamenVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 980201;

    private const CURSO = 980301;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'DOC980201',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Prueba',
        ]);
        DB::table('rol_usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'id_rol' => DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol'),
        ]);

        DB::table('curso')->insert([
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Curso activo',
            'sis_doc' => self::DOCENTE,
            'estado' => 'EnCurso',
            'fecha_creacion' => now()->toDateString(),
        ]);

        foreach ([
            [980401, now()->addDay(), '12:00:00', 90],
            [980402, now()->subDay(), '12:00:00', 90],
            [980403, now(), '00:00:00', 1440],
        ] as [$id, $fecha, $hora, $duracion]) {
            DB::table('examen')->insert([
                'id_examen' => $id,
                'fecha' => $fecha->toDateString(),
                'hora_inicio' => $hora,
                'duracion' => $duracion,
                'creador' => self::DOCENTE,
                'tipo_examen' => DB::table('tipo_examen')->value('id_tipo_examen'),
            ]);
            DB::table('examen_curso')->insert([
                'id_examen' => $id,
                'id_curso' => self::CURSO,
            ]);
        }
    }

    public function test_la_pagina_solo_lista_examenes_programados(): void
    {
        $respuesta = $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('examenes.eliminar'))
            ->assertOk();

        $examenes = $respuesta->original->getData()['examenes'] ?? [];

        $this->assertSame([980401], array_column($examenes, 'id'));
        $this->assertSame(['Programado'], array_column($examenes, 'estado'));
    }
}
