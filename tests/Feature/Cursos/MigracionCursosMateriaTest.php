<?php

/**
 * @file    MigracionCursosMateriaTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Pruebas de la migración de datos (comando cursos:asignar-materia-grupo):
 * asigna materia y grupo a los cursos existentes interpretando nombre_curso,
 * crea las materias que no están en el catálogo, deja el grupo en null cuando no
 * se puede deducir y llena docente_materia.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  test: creación inicial.
 */

namespace Tests\Feature\Cursos;

use App\Models\Materia;
use App\Support\Validadores;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigracionCursosMateriaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 900701;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC900701', 'password' => 'x',
            'nombre_usuario' => 'DocenteMigracion', 'apellido' => 'Materia',
        ]);

        // Materia del catálogo a la que debe engancharse uno de los cursos.
        Materia::query()->firstOrCreate(
            ['nombre_normalizado' => Validadores::normalizarNombreMateria('Fisica General')],
            ['nombre' => 'Fisica General', 'codigo' => '2006063', 'semestre' => 1, 'origen' => Materia::ORIGEN_CATALOGO, 'fecha_creacion' => now()],
        );

        DB::table('curso')->insert([
            ['id_curso' => 900711, 'nombre_curso' => 'Fisica General - Grupo 2', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString()],
            ['id_curso' => 900712, 'nombre_curso' => 'Taller Libre ZZ G3', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString()],
            ['id_curso' => 900713, 'nombre_curso' => 'Curso Raro Sin Grupo QQ', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString()],
        ]);
    }

    public function test_engancha_al_catalogo_y_asigna_grupo(): void
    {
        Artisan::call('cursos:asignar-materia-grupo');

        // Curso del catálogo: engancha a "Fisica General" y grupo 2.
        $catalogo = DB::table('curso')->where('id_curso', 900711)->first();
        $idFisica = (int) Materia::query()->where('nombre_normalizado', 'fisica general')->value('id_materia');
        $this->assertSame($idFisica, (int) $catalogo->id_materia);
        $this->assertSame(2, (int) $catalogo->numero_grupo);

        // Curso fuera del catálogo: NO se inventa materia; queda sin materia,
        // pero el grupo igual se deduce.
        $nuevo = DB::table('curso')->where('id_curso', 900712)->first();
        $this->assertNull($nuevo->id_materia);
        $this->assertSame(3, (int) $nuevo->numero_grupo);

        // Curso sin grupo ni materia del catálogo: ambos quedan null.
        $sinGrupo = DB::table('curso')->where('id_curso', 900713)->first();
        $this->assertNull($sinGrupo->numero_grupo);
        $this->assertNull($sinGrupo->id_materia);
    }

    public function test_llena_docente_materia_solo_con_las_del_catalogo(): void
    {
        Artisan::call('cursos:asignar-materia-grupo');

        // Solo el curso enganchado al catálogo (Fisica General) alimenta docente_materia.
        $this->assertSame(1, DB::table('docente_materia')->where('id_docente', self::DOCENTE)->count());
    }

    public function test_no_crea_ni_borra_materias(): void
    {
        $antes = DB::table('materia')->count();

        Artisan::call('cursos:asignar-materia-grupo');
        Artisan::call('cursos:asignar-materia-grupo');

        $this->assertSame($antes, DB::table('materia')->count());
        $this->assertSame(1, DB::table('docente_materia')->where('id_docente', self::DOCENTE)->count());
    }
}
