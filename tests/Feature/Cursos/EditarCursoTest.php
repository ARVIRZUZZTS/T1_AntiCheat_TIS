<?php

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EditarCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 991001;

    private const OTRO_DOCENTE = 991002;

    private const CURSO = 991101;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');

        foreach ([self::DOCENTE => 'EditarUno', self::OTRO_DOCENTE => 'EditarDos'] as $id => $nombre) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => $nombre,
                'apellido' => 'Editar Curso',
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        DB::table('curso')->insert([
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Calculo I - Grupo 2',
            'sis_doc' => self::DOCENTE,
            'estado' => 'EnCurso',
            'fecha_creacion' => now()->toDateString(),
        ]);
        DB::table('curso_tipo_gestion')->insert([
            'id_curso' => self::CURSO,
            'id_tg' => $this->gestiones()[0],
            'anio' => now()->year,
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE), 'web');
    }

    public function test_exige_materia_grupo_gestion_y_anio(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['materia', 'grupo', 'gestion', 'anio'])
            ->assertJsonPath('errors.materia.0', 'Ingrese la materia.')
            ->assertJsonPath('errors.grupo.0', 'Ingrese el grupo.')
            ->assertJsonPath('errors.gestion.0', 'Seleccione la gestion.')
            ->assertJsonPath('errors.anio.0', 'Ingrese el año.');
    }

    public function test_el_grupo_solo_acepta_letras_y_numeros_de_hasta_tres(): void
    {
        $mensaje = 'El grupo solo admite letras y numeros, hasta 3 caracteres.';

        foreach (['2-A', '21AB', 'group'] as $grupo) {
            $this->putJson(route('api.cursos.actualizar', self::CURSO), $this->datosValidos(['grupo' => $grupo]))
                ->assertStatus(422)
                ->assertJsonPath('errors.grupo.0', $mensaje);
        }
    }

    public function test_guarda_el_grupo_en_mayusculas_y_limpia_espacios(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO), $this->datosValidos([
            'materia' => '  Calculo    I ',
            'grupo' => ' 2a ',
        ]))
            ->assertOk()
            ->assertJsonPath('datos.materia', 'Calculo I')
            ->assertJsonPath('datos.grupo', '2A')
            ->assertJsonPath('datos.nombre', 'Calculo I - Grupo 2A');

        $this->assertDatabaseHas('curso', [
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Calculo I - Grupo 2A',
        ]);
    }

    public function test_la_gestion_debe_ser_una_de_las_vigentes(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO), $this->datosValidos(['gestion' => 999]))
            ->assertStatus(422)
            ->assertJsonPath('errors.gestion.0', 'Seleccione una gestion valida.');
    }

    public function test_el_anio_va_desde_el_actual_y_la_materia_hasta_80(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO), $this->datosValidos(['anio' => now()->year - 1]))
            ->assertStatus(422)
            ->assertJsonPath('errors.anio.0', 'El año debe ser '.now()->year.' o posterior.');

        $this->putJson(route('api.cursos.actualizar', self::CURSO), $this->datosValidos([
            'materia' => str_repeat('a', 81),
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.materia.0', 'La materia no debe superar los 80 caracteres.');
    }

    public function test_existencia_y_pertenencia_se_validan_antes_que_los_campos(): void
    {
        $this->putJson(route('api.cursos.actualizar', 999999), [])->assertNotFound();

        $this->actingAs(Usuario::findOrFail(self::OTRO_DOCENTE), 'web')
            ->putJson(route('api.cursos.actualizar', self::CURSO), [])
            ->assertForbidden();
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->app['auth']->forgetGuards();

        $this->putJson(route('api.cursos.actualizar', self::CURSO), $this->datosValidos())
            ->assertUnauthorized();
    }

    /** @param  array<string, mixed>  $sobrescribir  @return array<string, mixed> */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'materia' => 'Calculo I',
            'grupo' => '2',
            'gestion' => $this->gestiones()[0],
            'anio' => now()->year,
        ], $sobrescribir);
    }

    /** @return list<int> */
    private function gestiones(): array
    {
        return DB::table('tipo_gestion')
            ->orderBy('id_tipo_gestion')
            ->pluck('id_tipo_gestion')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
