<?php

/**
 * @file    ActualizarExamenTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-07
 *
 * @description
 * Pruebas de integracion del guardado de un examen editado (Task #261, HU-22):
 * PUT /api/examenes/{examen}/edicion. Igual que RegistrarExamenTest, corren
 * contra el esquema real de desarrollo con DatabaseTransactions y datos propios
 * (rango 900400+) que se revierten al terminar, sin tocar los datos reales.
 *
 * Cubren que los cambios se persistan sobre el MISMO examen (sin crear uno
 * nuevo ni cambiar el curso), que las relaciones de catalogo se sincronicen,
 * que las validaciones del alta sigan rigiendo al editar, que solo el creador
 * pueda editar y que un error no deje una actualizacion a medias.
 *
 * @see  App\Http\Controllers\Api\ExamenEdicionController
 * @see  App\Http\Requests\UpdateExamenRequest
 * @see  App\Services\Examen\ActualizarExamenService
 *
 * @changelog
 * - 2026-11-07  [Diego Tejerina]  test: creacion inicial de las pruebas.
 */

namespace Tests\Feature\Examenes;

use App\Models\Examen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Examen\ActualizarExamenService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActualizarExamenTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_DOCENTE = 900401;

    private const ID_OTRO_DOCENTE = 900402;

    private const ID_CURSO = 900401;

    private const ID_EXAMEN = 900401;

    private const ID_AMBIENTE_1 = 900401;

    private const ID_AMBIENTE_2 = 900402;

    private const ID_MATERIAL_1 = 900401;

    private const ID_MATERIAL_2 = 900402;

    private const ID_NORMA_1 = 900401;

    private const ID_NORMA_2 = 900402;

    protected function setUp(): void
    {
        parent::setUp();

        $idRolDocente = DB::table('rol')->where('nombre_rol', Rol::NOMBRE_DOCENTE)->value('id_rol');

        foreach ([self::ID_DOCENTE, self::ID_OTRO_DOCENTE] as $indice => $idDocente) {
            DB::table('usuario')->insert([
                'id_usuario' => $idDocente,
                'cod_sis' => 'DOC'.$idDocente,
                'password' => 'x',
                'nombre_usuario' => 'Docente '.$indice,
                'apellido' => 'De Prueba Edicion',
            ]);
            DB::table('rol_usuario')->insert([
                'id_usuario' => $idDocente,
                'id_rol' => $idRolDocente,
            ]);
        }

        DB::table('curso')->insert([
            'id_curso' => self::ID_CURSO,
            'nombre_curso' => 'Curso de Edicion',
            'sis_doc' => self::ID_DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        foreach ([self::ID_AMBIENTE_1, self::ID_AMBIENTE_2] as $id) {
            DB::table('ambiente')->insert([
                'id_ambiente' => $id,
                'nombre_ambiente' => 'Aula '.$id,
            ]);
        }

        foreach ([self::ID_MATERIAL_1, self::ID_MATERIAL_2] as $id) {
            DB::table('material')->insert([
                'id_material' => $id,
                'descripcion' => 'Material '.$id,
            ]);
        }

        foreach ([self::ID_NORMA_1, self::ID_NORMA_2] as $id) {
            DB::table('norma')->insert([
                'id_norma' => $id,
                'detalle_norma' => 'Norma '.$id,
            ]);
        }

        $this->crearExamenBase();

        $this->actingAs(Usuario::query()->findOrFail(self::ID_DOCENTE), 'web');
    }

    /**
     * El guardado actualiza los datos base y sincroniza todas las relaciones
     * sobre el mismo examen.
     */
    public function test_actualiza_el_examen_con_todo_lo_elegido(): void
    {
        $fecha = now()->addMonth();

        $respuesta = $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'tipo_examen' => 'examen final',
            'fecha' => $fecha->format('d/m/Y'),
            'hora_inicio' => '10:15',
            'duracion' => 120,
            'ambientes' => [self::ID_AMBIENTE_2],
            'materiales' => [self::ID_MATERIAL_2],
            'normas' => [self::ID_NORMA_2],
            'materiales_personalizados' => "Calculadora cientifica\nRegla",
            'normas_personalizadas' => 'Prohibido salir del aula',
        ]));

        $respuesta->assertOk()->assertJson(['mensaje' => 'Examen actualizado con exito']);

        $examen = Examen::query()->findOrFail(self::ID_EXAMEN);

        $this->assertSame($fecha->format('Y-m-d'), $examen->fecha);
        $this->assertSame('10:15:00', $examen->hora_inicio);
        $this->assertSame(120, (int) $examen->duracion);
        $this->assertSame($this->idTipo('examen final'), (int) $examen->tipo_examen);

        // Las relaciones de catalogo quedan exactamente con lo nuevo.
        $this->assertSame(
            [self::ID_AMBIENTE_2],
            $examen->ambientes()->pluck('ambiente.id_ambiente')->map(fn ($id): int => (int) $id)->all()
        );
        $this->assertSame(
            [self::ID_MATERIAL_2],
            $examen->materialesPermitidos()->pluck('material.id_material')->map(fn ($id): int => (int) $id)->all()
        );
        $this->assertSame(
            [self::ID_NORMA_2],
            $examen->normas()->pluck('norma.id_norma')->map(fn ($id): int => (int) $id)->all()
        );

        // Lo personalizado se reemplaza por lo que llega, una fila por renglon.
        $materiales = DB::table('material_personalizado')->where('id_examen', self::ID_EXAMEN)
            ->orderBy('numero_material')->pluck('descripcion_material')->all();
        $this->assertSame(['Calculadora cientifica', 'Regla'], $materiales);

        $normas = DB::table('norma_personalizada')->where('id_examen', self::ID_EXAMEN)
            ->orderBy('numero_norma')->pluck('descripcion_norma')->all();
        $this->assertSame(['Prohibido salir del aula'], $normas);
    }

    /** Editar no crea un examen nuevo: sigue habiendo uno solo. */
    public function test_no_crea_un_examen_nuevo(): void
    {
        $antes = Examen::query()->count();

        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos())
            ->assertOk();

        $this->assertSame($antes, Examen::query()->count());
    }

    /** El curso asociado es de solo lectura: el guardado no lo cambia. */
    public function test_conserva_el_curso_asociado(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'ambientes' => [self::ID_AMBIENTE_2],
        ]))->assertOk();

        $cursos = DB::table('examen_curso')->where('id_examen', self::ID_EXAMEN)
            ->pluck('id_curso')->map(fn ($id): int => (int) $id)->all();

        $this->assertSame([self::ID_CURSO], $cursos);
    }

    /** sync() agrega lo nuevo y quita lo viejo sin duplicar filas. */
    public function test_sincroniza_los_ambientes(): void
    {
        // De un solo ambiente a los dos.
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'ambientes' => [self::ID_AMBIENTE_1, self::ID_AMBIENTE_2],
        ]))->assertOk();

        $this->assertSame(2, DB::table('examen_ambiente')->where('id_examen', self::ID_EXAMEN)->count());

        // Y de vuelta a un unico ambiente, el segundo.
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'ambientes' => [self::ID_AMBIENTE_2],
        ]))->assertOk();

        $ambientes = DB::table('examen_ambiente')->where('id_examen', self::ID_EXAMEN)
            ->pluck('id_ambiente')->map(fn ($id): int => (int) $id)->all();
        $this->assertSame([self::ID_AMBIENTE_2], $ambientes);
    }

    /** Un texto personalizado de mas de 300 caracteres se rechaza. */
    public function test_rechaza_personalizados_mayores_a_300(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'normas_personalizadas' => str_repeat('a', 301),
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'normas_personalizadas' => 'La norma personalizada no debe superar los 300 caracteres.',
        ]);
    }

    /** La duracion no puede superar el tope de 300 minutos. */
    public function test_rechaza_una_duracion_mayor_al_tope(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'duracion' => 301,
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'duracion' => 'La duracion no debe superar los 300 minutos.',
        ]);
    }

    /** Una hora fuera de la ventana 06:00-22:00 se rechaza. */
    public function test_rechaza_una_hora_fuera_de_ventana(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'hora_inicio' => '05:59',
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'hora_inicio' => 'La hora de inicio no puede ser anterior a las 06:00.',
        ]);
    }

    /** Una fecha que no existe se rechaza. */
    public function test_rechaza_una_fecha_invalida(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'fecha' => '31/02/2026',
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'fecha' => 'Ingrese una fecha valida.',
        ]);
    }

    /** No se puede guardar sin al menos un ambiente. */
    public function test_exige_al_menos_un_ambiente(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'ambientes' => [],
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'ambientes' => 'Seleccione al menos un ambiente.',
        ]);
    }

    /** Un docente que no es el creador no puede editar el examen (403). */
    public function test_un_docente_ajeno_no_puede_editar(): void
    {
        $this->actingAs(Usuario::query()->findOrFail(self::ID_OTRO_DOCENTE), 'web');

        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'duracion' => 200,
        ]))->assertForbidden();

        // Nada se toco: la duracion sigue siendo la original.
        $this->assertSame(60, (int) Examen::query()->findOrFail(self::ID_EXAMEN)->duracion);
    }

    /** Si falla una validacion no se persiste nada: quedan los valores previos. */
    public function test_conserva_los_valores_previos_si_falla_una_validacion(): void
    {
        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'duracion' => 301,
            'hora_inicio' => '11:45',
        ]))->assertStatus(422);

        $examen = Examen::query()->findOrFail(self::ID_EXAMEN);
        $this->assertSame('2026-12-01', $examen->fecha);
        $this->assertSame('08:00:00', $examen->hora_inicio);
        $this->assertSame(60, (int) $examen->duracion);
    }

    /**
     * Guardar un examen final sin cambiarle el tipo no choca con la regla de
     * "un solo final por curso": el propio examen se excluye.
     */
    public function test_permite_guardar_el_mismo_final_sin_duplicar(): void
    {
        Examen::query()->where('id_examen', self::ID_EXAMEN)
            ->update(['tipo_examen' => $this->idTipo('examen final')]);

        $this->putJson(route('api.examenes.edicion.actualizar', self::ID_EXAMEN), $this->datosValidos([
            'tipo_examen' => 'examen final',
        ]))->assertOk();
    }

    /**
     * Si una operacion relacionada falla, la transaccion revierte todo: ni los
     * datos base ni las relaciones quedan a medias. Se fuerza el error llamando
     * al servicio con un ambiente inexistente (la FK de examen_ambiente lo
     * rechaza), despues de que ya se escribieron los campos del examen.
     */
    public function test_revierte_todo_si_falla_una_operacion(): void
    {
        $servicio = app(ActualizarExamenService::class);
        $examen = Examen::query()->findOrFail(self::ID_EXAMEN);

        try {
            $servicio->ejecutar($examen, $this->datosValidos([
                'fecha' => now()->addMonth()->format('d/m/Y'),
                'duracion' => 200,
                'ambientes' => [999999],
            ]));
            $this->fail('Se esperaba una QueryException por el ambiente inexistente.');
        } catch (QueryException) {
            // Esperado: el ambiente 999999 viola la FK y revierte la transaccion.
        }

        $fresco = Examen::query()->findOrFail(self::ID_EXAMEN);
        $this->assertSame('2026-12-01', $fresco->fecha);
        $this->assertSame(60, (int) $fresco->duracion);

        // El ambiente original sigue siendo el unico asociado.
        $ambientes = DB::table('examen_ambiente')->where('id_examen', self::ID_EXAMEN)
            ->pluck('id_ambiente')->map(fn ($id): int => (int) $id)->all();
        $this->assertSame([self::ID_AMBIENTE_1], $ambientes);
    }

    /**
     * Crea el examen inicial que las pruebas editan, con una relacion de cada
     * tipo y un renglon personalizado, para poder comprobar la sincronizacion.
     */
    private function crearExamenBase(): void
    {
        $examen = new Examen;
        $examen->id_examen = self::ID_EXAMEN;
        $examen->fecha = '2026-12-01';
        $examen->hora_inicio = '08:00:00';
        $examen->duracion = 60;
        $examen->creador = self::ID_DOCENTE;
        $examen->tipo_examen = $this->idTipo('examen parcial');
        $examen->save();

        $examen->cursos()->attach(self::ID_CURSO);
        $examen->ambientes()->attach(self::ID_AMBIENTE_1);
        $examen->normas()->attach(self::ID_NORMA_1);
        $examen->materialesPermitidos()->attach(self::ID_MATERIAL_1);
        $examen->materialesPersonalizados()->create([
            'numero_material' => 1,
            'descripcion_material' => 'Material original',
        ]);
        $examen->normasPersonalizadas()->create([
            'numero_norma' => 1,
            'descripcion_norma' => 'Norma original',
        ]);
    }

    /**
     * Datos validos de edicion. Por defecto cambian poco respecto del examen
     * base para que cada prueba sobrescriba solo lo que le interesa.
     *
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'tipo_examen' => 'examen parcial',
            'fecha' => now()->addMonth()->format('d/m/Y'),
            'hora_inicio' => '09:30',
            'duracion' => 90,
            'ambientes' => [self::ID_AMBIENTE_1],
            'materiales' => [self::ID_MATERIAL_1],
            'normas' => [self::ID_NORMA_1],
            'materiales_personalizados' => '',
            'normas_personalizadas' => '',
        ], $sobrescribir);
    }

    /** Id del catalogo tipo_examen por nombre; no se crean filas propias. */
    private function idTipo(string $nombre): int
    {
        return (int) DB::table('tipo_examen')
            ->where('nombre_tipo_examen', $nombre)
            ->value('id_tipo_examen');
    }
}
