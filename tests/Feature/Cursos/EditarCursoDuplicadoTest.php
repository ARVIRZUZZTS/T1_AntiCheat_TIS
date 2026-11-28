<?php

namespace Tests\Feature\Cursos;

use App\Models\Materia;
use App\Models\Usuario;
use App\Support\Validadores;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EditarCursoDuplicadoTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 992001;

    private const CURSO_PROPIO = 992101;

    private const CURSO_AJENO = 992102;

    private const ESTUDIANTE = '992301';

    private const EXAMEN = 992401;

    private const INCIDENCIA = 992501;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'DOC'.self::DOCENTE,
            'password' => 'x',
            'nombre_usuario' => 'EditarDuplicado',
            'apellido' => 'Curso',
        ]);
        DB::table('rol_usuario')->insert(['id_usuario' => self::DOCENTE, 'id_rol' => $rol]);

        $gestiones = $this->gestiones();

        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_PROPIO, 'nombre_curso' => 'Calculo I - Grupo 2', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString(), 'id_materia' => $this->materiaId('Calculo I'), 'numero_grupo' => 2],
            ['id_curso' => self::CURSO_AJENO, 'nombre_curso' => 'Otro Curso - Grupo 9', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString(), 'id_materia' => $this->materiaId('Otro Curso'), 'numero_grupo' => 9],
        ]);
        DB::table('curso_tipo_gestion')->insert([
            ['id_curso' => self::CURSO_PROPIO, 'id_tg' => $gestiones[1], 'anio' => now()->year + 1],
            ['id_curso' => self::CURSO_AJENO, 'id_tg' => $gestiones[0], 'anio' => now()->year],
        ]);

        DB::table('estudiante')->insert([
            'sis_estudiante' => self::ESTUDIANTE,
            'nombre_estudiante' => 'Estudiante',
            'apellido_estudiante' => 'Duplicado',
        ]);
        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => self::ESTUDIANTE,
            'id_curso' => self::CURSO_PROPIO,
        ]);

        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '12:00:00',
            'duracion' => 90,
            'creador' => self::DOCENTE,
            'tipo_examen' => DB::table('tipo_examen')->value('id_tipo_examen'),
        ]);
        DB::table('examen_curso')->insert([
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_PROPIO,
        ]);

        DB::table('central_riesgo')->insert([
            'id_registro' => self::INCIDENCIA,
            'id_registrador' => self::DOCENTE,
            'id_curso' => self::CURSO_PROPIO,
            'tipo_infraccion' => 'sospechoso',
            'sis_estudiante' => self::ESTUDIANTE,
            'id_examen' => self::EXAMEN,
            'fecha_registro' => now()->toDateTimeString(),
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE), 'web');
    }

    public function test_duplicado_devuelve_409_con_mensaje_y_los_cuatro_campos(): void
    {
        $gestiones = $this->gestiones();
        $mensaje = 'Este curso ya existe en la gestión y el año elegidos.';

        $this->putJson(route('api.cursos.actualizar', self::CURSO_PROPIO), $this->datosValidos([
            'materia' => 'otro curso',
            'grupo' => '9',
            'gestion' => $gestiones[0],
            'anio' => now()->year,
        ]))
            ->assertStatus(409)
            ->assertJsonPath('message', $mensaje)
            ->assertJsonPath('errors.materia.0', $mensaje)
            ->assertJsonPath('errors.grupo.0', $mensaje)
            ->assertJsonPath('errors.gestion.0', $mensaje)
            ->assertJsonPath('errors.anio.0', $mensaje)
            ->assertJsonPath('campos.materia', 'otro curso')
            ->assertJsonPath('campos.grupo', '9')
            ->assertJsonPath('campos.gestion', $gestiones[0])
            ->assertJsonPath('campos.anio', now()->year);

        $this->assertDatabaseHas('curso', [
            'id_curso' => self::CURSO_PROPIO,
            'nombre_curso' => 'Calculo I - Grupo 2',
        ]);
    }

    public function test_guardar_sin_cambios_no_genera_duplicado(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO_PROPIO), $this->datosValidos())
            ->assertOk()
            ->assertJsonPath('datos.nombre', 'Cálculo I - Grupo 2');

        $this->assertSame(1, DB::table('curso')
            ->where('nombre_curso', 'Cálculo I - Grupo 2')
            ->count());
    }

    public function test_el_mismo_nombre_en_otra_gestion_o_anio_no_es_duplicado(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO_PROPIO), $this->datosValidos([
            'materia' => 'Otro Curso',
            'grupo' => '9',
        ]))
            ->assertOk()
            ->assertJsonPath('datos.nombre', 'Otro Curso - Grupo 9');
    }

    public function test_actualiza_nombre_y_conserva_relaciones_del_curso(): void
    {
        $this->putJson(route('api.cursos.actualizar', self::CURSO_PROPIO), $this->datosValidos([
            'materia' => 'Calculo II',
            'grupo' => '3',
            'anio' => now()->year + 2,
        ]))
            ->assertOk()
            ->assertJsonPath('datos.materia', 'Cálculo II')
            ->assertJsonPath('datos.grupo', '3')
            ->assertJsonPath('datos.anio', now()->year + 2)
            ->assertJsonPath('datos.nombre', 'Cálculo II - Grupo 3');

        $this->assertDatabaseHas('curso', [
            'id_curso' => self::CURSO_PROPIO,
            'nombre_curso' => 'Cálculo II - Grupo 3',
            'sis_doc' => self::DOCENTE,
            'estado' => 'EnCurso',
            'fecha_creacion' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('curso_tipo_gestion', [
            'id_curso' => self::CURSO_PROPIO,
            'id_tg' => $this->gestiones()[1],
            'anio' => now()->year + 2,
        ]);
        $this->assertDatabaseHas('estudiante_curso', [
            'sis_estudiante' => self::ESTUDIANTE,
            'id_curso' => self::CURSO_PROPIO,
        ]);
        $this->assertDatabaseHas('examen', [
            'id_examen' => self::EXAMEN,
            'duracion' => 90,
        ]);
        $this->assertDatabaseHas('examen_curso', [
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_PROPIO,
        ]);
        $this->assertDatabaseHas('central_riesgo', [
            'id_registro' => self::INCIDENCIA,
            'id_curso' => self::CURSO_PROPIO,
            'id_examen' => self::EXAMEN,
        ]);
    }

    /** @param  array<string, mixed>  $sobrescribir  @return array<string, mixed> */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'materia' => 'Calculo I',
            'grupo' => '2',
            'gestion' => $this->gestiones()[1],
            'anio' => now()->year + 1,
        ], $sobrescribir);
    }

    private function materiaId(string $nombre): int
    {
        return (int) Materia::query()->firstOrCreate(
            ['nombre_normalizado' => Validadores::normalizarNombreMateria($nombre)],
            ['nombre' => $nombre, 'origen' => Materia::ORIGEN_CATALOGO, 'fecha_creacion' => now()],
        )->id_materia;
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
