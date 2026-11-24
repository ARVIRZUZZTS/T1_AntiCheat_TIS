<?php

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerEstudiantesVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 990201;

    private const ACTIVO = 990301;

    private const FINALIZADO = 990302;

    private const ARCHIVADO = 990303;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'DOC990201',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Prueba',
        ]);
        DB::table('rol_usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'id_rol' => DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol'),
        ]);

        foreach ([
            self::ACTIVO => 'EnCurso',
            self::FINALIZADO => 'Finalizo',
            self::ARCHIVADO => 'Archivado',
        ] as $id => $estado) {
            DB::table('curso')->insert([
                'id_curso' => $id,
                'nombre_curso' => 'Curso '.$estado,
                'sis_doc' => self::DOCENTE,
                'estado' => $estado,
                'fecha_creacion' => now()->toDateString(),
            ]);
        }
    }

    public function test_ver_estudiantes_solo_ofrece_cursos_activos(): void
    {
        $respuesta = $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('cursos.ver-estudiantes'))
            ->assertOk();

        $cursos = $respuesta->original->getData()['cursos'];

        $this->assertSame([self::ACTIVO], $cursos->pluck('id_curso')->all());
        $respuesta->assertSee('Curso EnCurso')->assertDontSee('Curso Finalizo')->assertDontSee('Curso Archivado');
    }

    public function test_ver_estudiantes_ignora_curso_finalizado_por_query(): void
    {
        $respuesta = $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('cursos.ver-estudiantes', ['curso' => self::FINALIZADO]))
            ->assertOk()
            ->assertDontSee('Curso Finalizo');

        $this->assertNull($respuesta->original->getData()['cursoSeleccionado']);
    }
}
