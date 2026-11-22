<?php

namespace Tests\Unit\Services\Curso;

use App\Models\Curso;
use App\Models\Examen;
use App\Services\Curso\AsegurarCursoEditableService;
use App\Services\Curso\CargarListaEstudiantesService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CargarListaEstudiantesServiceTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 910101;

    private const CURSO = 910101;

    private const EXAMEN = 910101;

    private const TIPO_EXAMEN = 910101;

    private const CONFIG = 910101;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::TIPO_EXAMEN,
            'nombre_tipo_examen' => 'examen parcial',
        ]);

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'DOC910101',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Prueba',
        ]);

        Curso::create([
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Curso Prueba',
            'sis_doc' => self::DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        Examen::create([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'duracion' => 120,
            'creador' => self::DOCENTE,
            'tipo_examen' => self::TIPO_EXAMEN,
        ]);

        DB::table('examen_curso')->insert([
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
        ]);

        DB::table('configuracion_examen_docente')->insert([
            'id_configuracion_examen' => self::CONFIG,
            'id_examen' => self::EXAMEN,
            'id_docente' => self::DOCENTE,
        ]);
    }

    private function archivo(array $filas, array $encabezado = ['SIS', 'Nombres', 'Apellidos', 'Carrera']): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($encabezado as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'1', $h);
        }
        foreach ($filas as $r => $fila) {
            foreach ($fila as $i => $v) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col.($r + 2), $v);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'lista.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function servicio(): CargarListaEstudiantesService
    {
        return new CargarListaEstudiantesService(app(AsegurarCursoEditableService::class));
    }

    private function curso(): Curso
    {
        return Curso::findOrFail(self::CURSO);
    }

    public function test_archivo_valido_crea_estudiantes_e_inscripciones(): void
    {
        $archivo = $this->archivo([
            ['100000001', 'Ana', 'Perez', 'Sistemas'],
            ['100000002', 'Luis', 'Gomez', 'Industrial'],
        ]);

        $resultado = $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertSame(2, $resultado->totalFilas);
        $this->assertSame(2, $resultado->estudiantesNuevos);
        $this->assertSame(2, $resultado->inscripcionesNuevas);
        $this->assertSame(2, $resultado->estudianteExamenesCreados);
        $this->assertDatabaseHas('estudiante', ['sis_estudiante' => '100000001', 'nombre_estudiante' => 'Ana']);
        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '100000001', 'id_curso' => self::CURSO]);
        $this->assertDatabaseHas('estudiante_examen', ['sis_estudiante' => '100000001', 'id_examen' => self::EXAMEN]);
    }

    public function test_tramposo_global_se_cuenta_pero_no_deshabilita(): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => '100000003',
            'nombre_estudiante' => 'Juan',
            'apellido_estudiante' => 'Lopez',
        ]);
        DB::table('central_riesgo')->insert([
            'id_registro' => 910101,
            'sis_estudiante' => '100000003',
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
            'id_registrador' => self::DOCENTE,
            'fecha_registro' => now()->toDateTimeString(),
            'tipo_infraccion' => 'tramposo',
        ]);

        $archivo = $this->archivo([['100000003', 'Juan', 'Lopez', 'Sistemas']]);
        $resultado = $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertSame(1, $resultado->tramposos);
        $this->assertDatabaseHas('estudiante_examen', [
            'sis_estudiante' => '100000003',
            'id_examen' => self::EXAMEN,
            'estado' => 'habilitado',
        ]);
    }

    public function test_archivo_vacio_lanza_excepcion(): void
    {
        $archivo = $this->archivo([]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El archivo no contiene estudiantes.');
        $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);
    }

    public function test_sin_columna_sis_lanza_excepcion(): void
    {
        $archivo = $this->archivo([['Ana', 'Perez']], ['Nombres', 'Apellidos']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se encontró la columna de código SIS.');
        $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);
    }

    public function test_sis_invalido_se_omite_y_reporta(): void
    {
        $archivo = $this->archivo([
            ['123', 'Ana', 'Perez', 'Sistemas'],
            ['100000004', 'Luis', 'Gomez', 'Industrial'],
        ]);

        $resultado = $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertSame(1, $resultado->totalFilas);
        $this->assertCount(1, $resultado->sisInvalidos);
        $this->assertDatabaseHas('estudiante', ['sis_estudiante' => '100000004']);
        $this->assertDatabaseMissing('estudiante', ['sis_estudiante' => '123']);
    }

    public function test_duplicados_en_archivo_se_reportan(): void
    {
        $archivo = $this->archivo([
            ['100000005', 'Ana', 'Perez', 'Sistemas'],
            ['100000005', 'Ana', 'Perez', 'Sistemas'],
        ]);

        $resultado = $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertSame(1, $resultado->totalFilas);
        $this->assertSame(['100000005'], $resultado->sisDuplicadosEnArchivo);
    }

    public function test_ya_inscrito_se_cuenta_y_no_duplica(): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => '100000006',
            'nombre_estudiante' => 'Ana',
            'apellido_estudiante' => 'Perez',
        ]);
        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => '100000006',
            'id_curso' => self::CURSO,
        ]);

        $archivo = $this->archivo([['100000006', 'Ana', 'Perez', 'Sistemas']]);
        $resultado = $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertSame(1, $resultado->yaInscritos);
        $this->assertSame(0, $resultado->inscripcionesNuevas);
    }

    public function test_sin_examenes_no_crea_estudiante_examen(): void
    {
        $cursoSinExamen = Curso::create([
            'id_curso' => 910102,
            'nombre_curso' => 'Curso Sin Examen',
            'sis_doc' => self::DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        $archivo = $this->archivo([['100000007', 'Ana', 'Perez', 'Sistemas']]);
        $resultado = $this->servicio()->ejecutar($cursoSinExamen, $archivo, self::DOCENTE);

        $this->assertSame(0, $resultado->estudianteExamenesCreados);
        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '100000007', 'id_curso' => 910102]);
    }

    public function test_curso_archivado_lanza_validacion(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO)->update(['estado' => 'Archivado']);
        $archivo = $this->archivo([['100000008', 'Ana', 'Perez', 'Sistemas']]);

        $this->expectException(ValidationException::class);
        $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);
    }

    public function test_ya_inscrito_tramposo_se_cuenta(): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => '100000010',
            'nombre_estudiante' => 'Ana',
            'apellido_estudiante' => 'Perez',
        ]);
        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => '100000010',
            'id_curso' => self::CURSO,
        ]);
        DB::table('central_riesgo')->insert([
            'id_registro' => 910102,
            'sis_estudiante' => '100000010',
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
            'id_registrador' => self::DOCENTE,
            'fecha_registro' => now()->toDateTimeString(),
            'tipo_infraccion' => 'tramposo',
        ]);

        $archivo = $this->archivo([['100000010', 'Ana', 'Perez', 'Sistemas']]);
        $resultado = $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertSame(1, $resultado->yaInscritos);
        $this->assertSame(1, $resultado->tramposos);
    }

    public function test_columnas_opcionales_se_guardan_en_estudiante_nuevo(): void
    {
        $archivo = $this->archivo([['100000009', 'Maria', 'Quispe', 'Civil']]);
        $this->servicio()->ejecutar($this->curso(), $archivo, self::DOCENTE);

        $this->assertDatabaseHas('estudiante', [
            'sis_estudiante' => '100000009',
            'nombre_estudiante' => 'Maria',
            'apellido_estudiante' => 'Quispe',
            'carrera' => 'Civil',
        ]);
    }
}
