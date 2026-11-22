<?php

/**
 * @file    RestaurarCursoVistaTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Pruebas de la vista «Restaurar curso»: listado filtrado, modal,
 * confirmación, avisos y accesibilidad. Refs #326.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Enums\CursoEstado;
use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurarCursoVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 998301;

    private const OTRO = 998302;

    private const ARCHIVADO = 998301;

    private const EN_CURSO = 998302;

    private const AJENO_ARCHIVADO = 998303;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([self::DOCENTE, self::OTRO] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => 'Docente'.$id,
                'apellido' => 'Restaurar',
            ]);
        }

        $this->crearCurso(self::ARCHIVADO, 'A Archivado Propio', self::DOCENTE, CursoEstado::Archivado->value);
        $this->crearCurso(self::EN_CURSO, 'B En Curso Propio', self::DOCENTE, CursoEstado::EnCurso->value);
        $this->crearCurso(self::AJENO_ARCHIVADO, 'C Archivado Ajeno', self::OTRO, CursoEstado::Archivado->value);
    }

    private function crearCurso(int $id, string $nombre, int $docente, string $estado): void
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => $docente,
            'estado' => $estado,
            'fecha_creacion' => now()->toDateString(),
        ]);
    }

    public function test_servicio_devuelve_solo_archivados_propios_ordenados(): void
    {
        $cursos = app(ListarCursosDocenteService::class)->archivados(self::DOCENTE);

        $this->assertSame([self::ARCHIVADO], array_map('intval', $cursos->pluck('id_curso')->all()));
        $this->assertSame(CursoEstado::Archivado->value, $cursos->first()->estado);
    }
}
