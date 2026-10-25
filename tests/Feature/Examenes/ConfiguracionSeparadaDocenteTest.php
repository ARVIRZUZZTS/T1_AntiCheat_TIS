<?php

namespace Tests\Feature\Examenes;

use App\Enums\EstadoEstudianteExamen;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\EstudianteExamen;
use App\Models\Usuario;
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ConfiguracionSeparadaDocenteTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE_A = 995001;

    private const DOCENTE_B = 995002;

    private const ROL_DOCENTE = 995001;

    private const CURSO_A = 995001;

    private const CURSO_B = 995002;

    private const EXAMEN = 995001;

    private const TIPO_EXAMEN = 995001;

    private const CONFIG_A = 995001;

    private const CONFIG_B = 995002;

    private const MATERIAL_A = 995001;

    private const MATERIAL_B = 995002;

    private const NORMA_A = 995001;

    private const NORMA_B = 995002;

    private const ESTUDIANTE_A = '99500001';

    private const ESTUDIANTE_B = '99500002';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        DB::table('rol')->insert([
            'id_rol' => self::ROL_DOCENTE,
            'nombre_rol' => 'docente',
        ]);
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'cod_sis' => 'DOC995001', 'password' => 'x', 'nombre_usuario' => 'Docente', 'apellido' => 'A'],
            ['id_usuario' => self::DOCENTE_B, 'cod_sis' => 'DOC995002', 'password' => 'x', 'nombre_usuario' => 'Docente', 'apellido' => 'B'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'id_rol' => self::ROL_DOCENTE],
            ['id_usuario' => self::DOCENTE_B, 'id_rol' => self::ROL_DOCENTE],
        ]);
        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::TIPO_EXAMEN,
            'nombre_tipo_examen' => 'examen parcial',
        ]);
        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_A, 'nombre_curso' => 'Curso A', 'sis_doc' => self::DOCENTE_A, 'fecha_creacion' => now()->toDateString(), 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_B, 'nombre_curso' => 'Curso B', 'sis_doc' => self::DOCENTE_B, 'fecha_creacion' => now()->toDateString(), 'estado' => 'EnCurso'],
        ]);
        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '08:00',
            'duracion' => 90,
            'creador' => self::DOCENTE_A,
            'tipo_examen' => self::TIPO_EXAMEN,
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_A],
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_B],
        ]);
        DB::table('material')->insert([
            ['id_material' => self::MATERIAL_A, 'descripcion' => 'Calculadora A'],
            ['id_material' => self::MATERIAL_B, 'descripcion' => 'Formulario B'],
        ]);
        DB::table('norma')->insert([
            ['id_norma' => self::NORMA_A, 'detalle_norma' => 'Norma A'],
            ['id_norma' => self::NORMA_B, 'detalle_norma' => 'Norma B'],
        ]);
        DB::table('configuracion_examen_docente')->insert([
            ['id_configuracion_examen' => self::CONFIG_A, 'id_examen' => self::EXAMEN, 'id_docente' => self::DOCENTE_A, 'created_at' => now(), 'updated_at' => now()],
            ['id_configuracion_examen' => self::CONFIG_B, 'id_examen' => self::EXAMEN, 'id_docente' => self::DOCENTE_B, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('configuracion_examen_material')->insert([
            ['id_configuracion_examen' => self::CONFIG_A, 'id_material' => self::MATERIAL_A],
            ['id_configuracion_examen' => self::CONFIG_B, 'id_material' => self::MATERIAL_B],
        ]);
        DB::table('configuracion_examen_norma')->insert([
            ['id_configuracion_examen' => self::CONFIG_A, 'id_norma' => self::NORMA_A],
            ['id_configuracion_examen' => self::CONFIG_B, 'id_norma' => self::NORMA_B],
        ]);
        DB::table('configuracion_norma_personalizada')->insert([
            ['id_configuracion_examen' => self::CONFIG_A, 'numero_norma' => 1, 'descripcion_norma' => 'Personalizada A'],
            ['id_configuracion_examen' => self::CONFIG_B, 'numero_norma' => 1, 'descripcion_norma' => 'Personalizada B'],
        ]);
        DB::table('estudiante')->insert([
            ['sis_estudiante' => self::ESTUDIANTE_A, 'nombre_estudiante' => 'Estudiante', 'apellido_estudiante' => 'A', 'carrera' => 'Sistemas'],
            ['sis_estudiante' => self::ESTUDIANTE_B, 'nombre_estudiante' => 'Estudiante', 'apellido_estudiante' => 'B', 'carrera' => 'Sistemas'],
        ]);
        DB::table('estudiante_curso')->insert([
            ['sis_estudiante' => self::ESTUDIANTE_A, 'id_curso' => self::CURSO_A],
            ['sis_estudiante' => self::ESTUDIANTE_B, 'id_curso' => self::CURSO_B],
        ]);
        DB::table('estudiante_examen')->insert([
            ['id_estudiante_examen' => 995001, 'sis_estudiante' => self::ESTUDIANTE_A, 'id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_A, 'id_configuracion_examen' => self::CONFIG_A, 'estado' => 'habilitado'],
            ['id_estudiante_examen' => 995002, 'sis_estudiante' => self::ESTUDIANTE_B, 'id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_B, 'id_configuracion_examen' => self::CONFIG_B, 'estado' => 'habilitado'],
        ]);
    }

    public function test_cada_docente_consulta_y_modifica_solo_su_configuracion(): void
    {
        $docenteA = Usuario::query()->findOrFail(self::DOCENTE_A);
        $docenteB = Usuario::query()->findOrFail(self::DOCENTE_B);

        $this->actingAs($docenteA)
            ->getJson(route('api.examenes.configuracion.mostrar', self::EXAMEN))
            ->assertOk()
            ->assertJsonPath('datos.materiales.0', self::MATERIAL_A)
            ->assertJsonPath('datos.normas_personalizadas.0', 'Personalizada A');

        $this->actingAs($docenteA)
            ->putJson(route('api.examenes.configuracion.actualizar', self::EXAMEN), [
                'materiales' => [self::MATERIAL_A],
                'normas' => [self::NORMA_A],
                'normas_personalizadas' => ['A modificada'],
                'materiales_personalizados' => ['Material propio A'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.normas_personalizadas.0', 'A modificada');

        $this->actingAs($docenteB)
            ->getJson(route('api.examenes.configuracion.mostrar', self::EXAMEN))
            ->assertOk()
            ->assertJsonPath('datos.materiales.0', self::MATERIAL_B)
            ->assertJsonPath('datos.normas_personalizadas.0', 'Personalizada B');
    }

    public function test_el_informe_del_monitor_filtra_estudiantes_y_configuracion_por_docente(): void
    {
        $servicio = app(GenerarReporteAsistenciaService::class);

        $informeA = $servicio->ejecutar(self::EXAMEN, idDocente: self::DOCENTE_A);
        $informeB = $servicio->ejecutar(self::EXAMEN, idDocente: self::DOCENTE_B);

        $this->assertSame([self::ESTUDIANTE_A], $informeA['estudiantes']->pluck('sis')->all());
        $this->assertSame([self::ESTUDIANTE_B], $informeB['estudiantes']->pluck('sis')->all());
        $this->assertSame(['Calculadora A'], $informeA['examen']->materialesPermitidos->pluck('descripcion')->all());
        $this->assertSame(['Formulario B'], $informeB['examen']->materialesPermitidos->pluck('descripcion')->all());
    }

    public function test_la_cache_del_monitor_no_se_comparte_entre_docentes(): void
    {
        $docenteA = Usuario::query()->findOrFail(self::DOCENTE_A);
        $docenteB = Usuario::query()->findOrFail(self::DOCENTE_B);

        $monitorA = Livewire::actingAs($docenteA)->test(MonitorEnVivo::class);
        $monitorB = Livewire::actingAs($docenteB)->test(MonitorEnVivo::class);

        $this->assertSame([self::ESTUDIANTE_A], $monitorA->instance()->informe()['estudiantes']->pluck('sis')->all());
        $this->assertSame([self::ESTUDIANTE_B], $monitorB->instance()->informe()['estudiantes']->pluck('sis')->all());
    }

    public function test_un_docente_no_puede_cambiar_el_estado_del_estudiante_del_otro(): void
    {
        $docenteA = Usuario::query()->findOrFail(self::DOCENTE_A);

        Livewire::actingAs($docenteA)
            ->test(MonitorEnVivo::class)
            ->call('rechazarIngreso', self::ESTUDIANTE_B)
            ->assertSet('mensajeError', 'El estudiante no está inscrito en este examen.');

        $this->assertSame(
            EstadoEstudianteExamen::Habilitado,
            EstudianteExamen::query()->findOrFail(995002)->estado,
        );
    }

    public function test_un_examen_no_compartido_conserva_su_unica_configuracion_y_estudiantes(): void
    {
        $idExamen = 995003;
        $idConfiguracion = 995003;
        $idInscripcion = 995003;

        DB::table('examen')->insert([
            'id_examen' => $idExamen,
            'fecha' => now()->addDays(2)->toDateString(),
            'hora_inicio' => '10:00',
            'duracion' => 60,
            'creador' => self::DOCENTE_A,
            'tipo_examen' => self::TIPO_EXAMEN,
        ]);
        DB::table('examen_curso')->insert(['id_examen' => $idExamen, 'id_curso' => self::CURSO_A]);
        DB::table('configuracion_examen_docente')->insert([
            'id_configuracion_examen' => $idConfiguracion,
            'id_examen' => $idExamen,
            'id_docente' => self::DOCENTE_A,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('configuracion_examen_material')->insert([
            'id_configuracion_examen' => $idConfiguracion,
            'id_material' => self::MATERIAL_A,
        ]);
        DB::table('estudiante_examen')->insert([
            'id_estudiante_examen' => $idInscripcion,
            'sis_estudiante' => self::ESTUDIANTE_A,
            'id_examen' => $idExamen,
            'id_curso' => self::CURSO_A,
            'id_configuracion_examen' => $idConfiguracion,
            'estado' => 'habilitado',
        ]);

        $informe = app(GenerarReporteAsistenciaService::class)
            ->ejecutar($idExamen, idDocente: self::DOCENTE_A);

        $this->assertSame([self::ESTUDIANTE_A], $informe['estudiantes']->pluck('sis')->all());
        $this->assertSame(['Calculadora A'], $informe['examen']->materialesPermitidos->pluck('descripcion')->all());
    }
}
