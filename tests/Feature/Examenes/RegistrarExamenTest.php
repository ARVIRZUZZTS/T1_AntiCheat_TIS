<?php

/**
 * @file    RegistrarExamenTest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Pruebas de integración del alta de examen del modal de la materia
 * (POST /cursos/{curso}/examenes), contra el esquema real de desarrollo.
 *
 * El esquema de estas tablas viene de una migración con SQL crudo
 * (database/migrations/2026_09_27_000001_migracion_servidor_oficial.php),
 * por eso se usa DatabaseTransactions en vez de RefreshDatabase: cada test
 * crea sus propios datos con IDs dedicados (rango 900300+) y se revierten al
 * terminar, sin tocar los datos reales.
 *
 * Cubre dos cosas: que el examen y todo lo elegido se escriban de verdad
 * (incluidas `material_personalizado` y `norma_personalizada`, que el modal
 * daba por sentadas), y que las validaciones de fecha, hora y duración
 * devuelvan un mensaje claro.
 *
 * @see  App\Http\Controllers\ExamenController
 * @see  App\Http\Requests\StoreExamenRequest
 * @see  App\Services\Examen\RegistrarExamenService
 */

namespace Tests\Feature\Examenes;

use App\Models\Examen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrarExamenTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_DOCENTE = 900301;

    private const ID_CURSO = 900301;

    private const ID_AMBIENTE = 900301;

    private const ID_MATERIAL = 900301;

    private const ID_NORMA = 900301;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::ID_DOCENTE,
            'cod_sis' => 'DOC900301',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'De Prueba Alta',
        ]);

        DB::table('curso')->insert([
            'id_curso' => self::ID_CURSO,
            'nombre_curso' => 'Curso de Alta',
            'sis_doc' => self::ID_DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        DB::table('ambiente')->insert([
            'id_ambiente' => self::ID_AMBIENTE,
            'nombre_ambiente' => 'Aula 900301',
        ]);

        DB::table('material')->insert([
            'id_material' => self::ID_MATERIAL,
            'descripcion' => 'Tabla periódica',
        ]);

        DB::table('norma')->insert([
            'id_norma' => self::ID_NORMA,
            'detalle_norma' => 'No usar celular',
        ]);
    }

    /**
     * El alta escribe el examen, las relaciones del catálogo y lo escrito a mano.
     */
    public function test_crea_el_examen_con_todo_lo_elegido(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'materiales_personalizados' => "Calculadora simple\nFormulario de fórmulas",
            'normas_personalizadas' => 'No usar celular',
        ]))->assertRedirect(route('materias.detalle', self::ID_CURSO));

        $examen = Examen::query()->where('creador', self::ID_DOCENTE)->firstOrFail();

        $this->assertSame('2026-12-15', $examen->fecha);
        $this->assertSame('08:30:00', $examen->hora_inicio);
        $this->assertSame(90, (int) $examen->duracion);
        $this->assertSame($this->idTipoExamenParcial(), (int) $examen->tipo_examen);
        // La hora de fin se deriva: 08:30 más 90 minutos.
        $this->assertSame('10:00:00', $examen->hora_fin);
        $this->assertSame($examen->id_examen, $this->ultimoIdExamen());

        $this->assertDatabaseHas('examen_curso', [
            'id_examen' => $examen->id_examen,
            'id_curso' => self::ID_CURSO,
        ]);
        $this->assertDatabaseHas('examen_ambiente', [
            'id_examen' => $examen->id_examen,
            'id_ambiente' => self::ID_AMBIENTE,
        ]);
        $this->assertDatabaseHas('examen_norma', [
            'id_examen' => $examen->id_examen,
            'id_norma' => self::ID_NORMA,
        ]);
        $this->assertDatabaseHas('examen_material_permitido', [
            'id_examen' => $examen->id_examen,
            'id_material_permitido' => self::ID_MATERIAL,
        ]);

        // Lo personalizado se guarda en sus propias tablas, una fila por renglón.
        $materiales = DB::table('material_personalizado')
            ->where('id_examen', $examen->id_examen)
            ->orderBy('numero_material')
            ->get();
        $this->assertCount(2, $materiales);
        $this->assertSame('Calculadora simple', $materiales[0]->descripcion_material);
        $this->assertSame('Formulario de fórmulas', $materiales[1]->descripcion_material);

        $normas = DB::table('norma_personalizada')
            ->where('id_examen', $examen->id_examen)
            ->orderBy('numero_norma')
            ->get();
        $this->assertCount(1, $normas);
        $this->assertSame('No usar celular', $normas[0]->descripcion_norma);
    }

    /**
     * Un examen que cruza la medianoche termina al día siguiente, no el mismo.
     */
    public function test_la_hora_de_fin_cruza_la_medianoche(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'hora_inicio' => '23:30',
            'duracion' => 90,
        ]))->assertRedirect(route('materias.detalle', self::ID_CURSO));

        $examen = Examen::query()->where('creador', self::ID_DOCENTE)->firstOrFail();

        $this->assertSame('01:00:00', $examen->hora_fin);
    }

    /** Al menos un ambiente es obligatorio para poder monitorear el examen. */
    public function test_exige_al_menos_un_ambiente(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'ambientes' => [],
        ]))->assertSessionHasErrors(['ambientes' => 'Seleccione al menos un ambiente.']);
    }

    /** Una fecha que no existe da el mensaje de fecha inválida. */
    public function test_rechaza_una_fecha_que_no_existe(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'fecha' => '31/02/2026',
        ]))->assertSessionHasErrors(['fecha' => 'Ingrese una fecha válida.']);
    }

    /** Un año fuera de 19xx/20xx tiene su propio mensaje. */
    public function test_rechaza_un_anio_fuera_de_rango(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'fecha' => '10/12/1826',
        ]))->assertSessionHasErrors(['fecha' => 'Ingrese un año válido.']);
    }

    /** Una hora fuera de 00:00-23:59 da el mensaje de hora inválida. */
    public function test_rechaza_una_hora_invalida(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'hora_inicio' => '25:78',
        ]))->assertSessionHasErrors(['hora_inicio' => 'Ingrese una hora válida.']);
    }

    /** La duración no puede superar el tope del servicio. */
    public function test_rechaza_una_duracion_mayor_al_tope(): void
    {
        $this->post(route('cursos.examenes.store', self::ID_CURSO), $this->datosValidos([
            'duracion' => 301,
        ]))->assertSessionHasErrors(['duracion' => 'La duración no debe superar los 300 minutos.']);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'tipo_examen' => 'examen parcial',
            'fecha' => '15/12/2026',
            'hora_inicio' => '08:30',
            'duracion' => 90,
            'ambientes' => [self::ID_AMBIENTE],
            'materiales' => [self::ID_MATERIAL],
            'normas' => [self::ID_NORMA],
        ], $sobrescribir);
    }

    /** Id del examen insertado en este test, leído del pivote del curso. */
    private function ultimoIdExamen(): int
    {
        return (int) DB::table('examen_curso')
            ->where('id_curso', self::ID_CURSO)
            ->value('id_examen');
    }

    /**
     * Id del catálogo que el servicio resuelve por nombre: no se crea uno propio
     * para no duplicar una fila que ya existe en la base real.
     */
    private function idTipoExamenParcial(): int
    {
        return (int) DB::table('tipo_examen')
            ->where('nombre_tipo_examen', 'examen parcial')
            ->value('id_tipo_examen');
    }
}
