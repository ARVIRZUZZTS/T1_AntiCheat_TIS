<?php

/**
 * @file    RegistrarIncidenciaServiceTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-25
 *
 * @description
 * Pruebas de integración del registro de incidencias (HU 5) contra el esquema
 * real de desarrollo, con DatabaseTransactions e IDs dedicados (rango
 * 900500+), así cada prueba se revierte al terminar.
 *
 * Cubre el tipo según el rol, los motivos por origen, la descripción, el alta
 * del estudiante no verificado y el curso que se guarda (el de la inscripción
 * o el primero del registrador).
 *
 * @changelog
 * - 2026-10-25  [Valery D. Ortuno P]  test: creación inicial.
 *
 * @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
 */

namespace Tests\Feature\CentralRiesgo;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CentralRiesgo\RegistrarIncidenciaService;
use App\Support\DTO\RegistroIncidenciaDTO;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class RegistrarIncidenciaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 900501;

    private const AUXILIAR = 900502;

    private const CURSO_A = 900501;

    private const CURSO_B = 900502;

    private const EXAMEN = 900501;

    private const SIS_INSCRITO = '900500001';

    private const SIS_SIN_CURSO = '900500002';

    private const SIS_NUEVO = '900500003';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC900501', 'password' => 'x', 'nombre_usuario' => 'Docente', 'apellido' => 'Registro'],
            ['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX900502', 'password' => 'x', 'nombre_usuario' => 'Auxiliar', 'apellido' => 'Registro'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => $this->idRol(Rol::NOMBRE_DOCENTE)],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => $this->idRol(Rol::NOMBRE_AUXILIAR)],
        ]);

        // Examen compartido por dos cursos del mismo docente.
        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_A, 'nombre_curso' => 'Calculo I - Grupo 1', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => now()->toDateString(), 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_B, 'nombre_curso' => 'Calculo I - Grupo 2', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => now()->toDateString(), 'estado' => 'EnCurso'],
        ]);
        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'duracion' => 90,
            'creador' => self::DOCENTE,
            'tipo_examen' => DB::table('tipo_examen')->value('id_tipo_examen'),
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_A],
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_B],
        ]);
        DB::table('auxiliar_curso')->insert([
            'id_auxiliar' => self::AUXILIAR, 'id_curso' => self::CURSO_B, 'estado' => 'Activo',
        ]);

        $idConfiguracion = DB::table('configuracion_examen_docente')->insertGetId([
            'id_examen' => self::EXAMEN, 'id_docente' => self::DOCENTE, 'created_at' => now(), 'updated_at' => now(),
        ], 'id_configuracion_examen');

        // Inscrito en el curso B; el otro estudiante existe pero no está inscrito.
        DB::table('estudiante')->insert([
            ['sis_estudiante' => self::SIS_INSCRITO, 'nombre_estudiante' => 'Ana', 'apellido_estudiante' => 'Inscrita', 'carrera' => 'Sistemas'],
            ['sis_estudiante' => self::SIS_SIN_CURSO, 'nombre_estudiante' => 'Luis', 'apellido_estudiante' => 'Ajeno', 'carrera' => 'Sistemas'],
        ]);
        DB::table('estudiante_examen')->insert([
            'id_estudiante_examen' => (int) DB::table('estudiante_examen')->max('id_estudiante_examen') + 1,
            'sis_estudiante' => self::SIS_INSCRITO,
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_B,
            'id_configuracion_examen' => $idConfiguracion,
            'estado' => 'habilitado',
        ]);
    }

    public function test_un_docente_registra_tramposo_en_el_curso_de_la_inscripcion(): void
    {
        $registro = $this->registrar(self::DOCENTE, $this->datos(self::SIS_INSCRITO));

        $this->assertSame(TipoInfraccion::Tramposo, $registro->tipo_infraccion);
        $this->assertSame(self::CURSO_B, (int) $registro->id_curso);
        $this->assertNotNull($registro->id_estudiante_examen);
    }

    public function test_un_auxiliar_registra_sospechoso(): void
    {
        $registro = $this->registrar(self::AUXILIAR, $this->datos(self::SIS_INSCRITO));

        $this->assertSame(TipoInfraccion::Sospechoso, $registro->tipo_infraccion);
    }

    /** No inscrito: el primer curso del registrador en el examen. */
    public function test_un_no_inscrito_queda_en_el_primer_curso_del_registrador(): void
    {
        $delDocente = $this->registrar(self::DOCENTE, $this->datos(self::SIS_SIN_CURSO));
        $delAuxiliar = $this->registrar(self::AUXILIAR, $this->datos(self::SIS_SIN_CURSO));

        $this->assertSame(self::CURSO_A, (int) $delDocente->id_curso);
        $this->assertNull($delDocente->id_estudiante_examen);
        $this->assertSame(self::CURSO_B, (int) $delAuxiliar->id_curso);
    }

    public function test_un_sis_nuevo_se_da_de_alta_como_no_verificado(): void
    {
        $this->registrar(self::DOCENTE, $this->datos(self::SIS_NUEVO, nombre: ' Maria  Jose ', apellido: 'Lopez'));

        $this->assertDatabaseHas('estudiante', [
            'sis_estudiante' => self::SIS_NUEVO,
            'nombre_estudiante' => 'Maria Jose',
            'apellido_estudiante' => 'Lopez',
            'verificado' => false,
        ]);
    }

    public function test_un_sis_nuevo_exige_nombres_y_apellidos(): void
    {
        $this->assertRechaza('El estudiante no esta registrado: ingrese los apellidos.',
            $this->datos(self::SIS_NUEVO, nombre: 'Maria'));
        $this->assertDatabaseMissing('estudiante', ['sis_estudiante' => self::SIS_NUEVO]);
    }

    public function test_el_motivo_debe_ser_de_la_lista_del_origen(): void
    {
        $this->assertRechaza('Seleccione un motivo de la lista.',
            $this->datos(self::SIS_INSCRITO, origen: RegistroIncidenciaDTO::ORIGEN_INGRESO, motivo: Motivo::CopiaOIntercambioDeRespuestas));
    }

    public function test_otro_exige_descripcion_y_la_descripcion_tiene_tope(): void
    {
        $this->assertRechaza('Describa el hecho cuando el motivo es Otro.',
            $this->datos(self::SIS_INSCRITO, motivo: Motivo::Otro));
        $this->assertRechaza('La descripcion no debe superar los 300 caracteres.',
            $this->datos(self::SIS_INSCRITO, detalle: str_repeat('a', 301)));
    }

    public function test_el_sis_debe_tener_8_o_9_digitos(): void
    {
        $this->assertRechaza('Ingrese un codigo SIS valido (8 o 9 digitos).', $this->datos('12345'));
    }

    private function registrar(int $idUsuario, RegistroIncidenciaDTO $datos): \App\Models\CentralRiesgo
    {
        return app(RegistrarIncidenciaService::class)
            ->ejecutar($datos, Usuario::findOrFail($idUsuario), self::DOCENTE);
    }

    private function assertRechaza(string $mensaje, RegistroIncidenciaDTO $datos): void
    {
        try {
            $this->registrar(self::DOCENTE, $datos);
            $this->fail('Se esperaba que el registro se rechazara.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame($mensaje, $e->getMessage());
        }
    }

    private function datos(
        string $sis,
        string $origen = RegistroIncidenciaDTO::ORIGEN_MONITOR,
        Motivo $motivo = Motivo::CopiaOIntercambioDeRespuestas,
        ?string $detalle = null,
        ?string $nombre = null,
        ?string $apellido = null,
    ): RegistroIncidenciaDTO {
        return new RegistroIncidenciaDTO($sis, self::EXAMEN, $origen, $motivo->value, $detalle, $nombre, $apellido);
    }

    private function idRol(string $nombre): int
    {
        return (int) DB::table('rol')->where('nombre_rol', $nombre)->value('id_rol');
    }
}
