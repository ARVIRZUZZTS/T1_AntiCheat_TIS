<?php

namespace Tests\Feature\Cursos;

use App\Livewire\Cursos\CargarCsv;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * @file    CargarListaEstudiantesTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-23
 *
 * @description
 * Prueba de extremo a extremo de «Cargar CSV» (HU 25): carga de CSV con el
 * formato oficial y de Excel, cruce con la central de riesgos, validaciones del
 * archivo, confirmación de reemplazo y bloqueo por curso archivado o sin permisos.
 *
 * @changelog
 * - 2026-11-23  [Equipo T1]  test: cobertura de la carga de lista de estudiantes (HU 25).
 */
class CargarListaEstudiantesTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 920301;

    private const OTRO_DOCENTE = 920302;

    private const CURSO = 920301;

    private const EXAMEN = 920301;

    private const TIPO_EXAMEN = 920301;

    private const CONFIG = 920301;

    private const ENCABEZADO_OFICIAL = [
        '#', 'Estudiante', 'Apellidos', 'Nombres', 'Carrera',
        '1er PARCIAL', '2do PARCIAL', 'Examen Final', '2da Instancia', 'Nota Final', 'Resul. Final',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::TIPO_EXAMEN,
            'nombre_tipo_examen' => 'examen primer parcial',
        ]);

        foreach ([self::DOCENTE, self::OTRO_DOCENTE] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => 'Docente '.$id,
                'apellido' => 'Prueba',
            ]);
            DB::table('rol_usuario')->insert([
                'id_usuario' => $id,
                'id_rol' => DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol'),
            ]);
        }

        Curso::create([
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Curso Prueba',
            'sis_doc' => self::DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'duracion' => 120,
            'creador' => self::DOCENTE,
            'tipo_examen' => self::TIPO_EXAMEN,
        ]);
        DB::table('examen_curso')->insert(['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO]);
        DB::table('configuracion_examen_docente')->insert([
            'id_configuracion_examen' => self::CONFIG,
            'id_examen' => self::EXAMEN,
            'id_docente' => self::DOCENTE,
        ]);
    }

    private function docente(): Usuario
    {
        return Usuario::findOrFail(self::DOCENTE);
    }

    /**
     * Construye un CSV con el formato oficial.
     *
     * @param  list<array<int, string>>  $filas  Filas de datos en orden de columnas oficiales.
     */
    private function csvOficial(array $filas, bool $conBom = true): UploadedFile
    {
        $lineas = [implode(',', self::ENCABEZADO_OFICIAL)];
        foreach ($filas as $fila) {
            $lineas[] = implode(',', $fila);
        }
        $contenido = ($conBom ? "\xEF\xBB\xBF" : '').implode("\n", $lineas)."\n";

        return UploadedFile::fake()->createWithContent('lista.csv', $contenido);
    }

    /**
     * Construye un CSV simple a partir de encabezado y filas arbitrarias.
     *
     * @param  list<string>  $encabezado  Encabezado.
     * @param  list<array<int, string>>  $filas  Filas de datos.
     */
    private function csv(array $encabezado, array $filas): UploadedFile
    {
        $lineas = [implode(',', $encabezado)];
        foreach ($filas as $fila) {
            $lineas[] = implode(',', $fila);
        }

        return UploadedFile::fake()->createWithContent('lista.csv', implode("\n", $lineas)."\n");
    }

    /**
     * Construye un .xlsx con encabezado y filas.
     *
     * @param  list<string>  $encabezado  Encabezado.
     * @param  list<array<int, string>>  $filas  Filas de datos.
     */
    private function xlsx(array $encabezado, array $filas): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($encabezado as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $h);
        }
        foreach ($filas as $r => $fila) {
            foreach ($fila as $i => $v) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($i + 1).($r + 2),
                    $v,
                    DataType::TYPE_STRING,
                );
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent('lista.xlsx', (string) file_get_contents($path));
    }

    public function test_csv_oficial_en_curso_vacio_se_queda_en_cargar_csv(): void
    {
        $archivo = $this->csvOficial([
            ['1', '202600001', 'Quispe', 'Juan', 'Ingenieria de Sistemas', '', '', '', '', '', ''],
            ['2', '202600002', 'Lopez', 'Maria', 'Ingenieria de Sistemas', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('mensajeExito', 'Se inscribieron 2 estudiante(s): 2 habilitado(s), '
                .'0 deshabilitado(s) por la central de riesgos. Duplicados en el archivo: 0. Filas omitidas: 0.');

        $this->assertDatabaseHas('estudiante', [
            'sis_estudiante' => '202600001',
            'nombre_estudiante' => 'Juan',
            'apellido_estudiante' => 'Quispe',
            'carrera' => 'Ingenieria de Sistemas',
        ]);
        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '202600001', 'id_curso' => self::CURSO]);
        $this->assertDatabaseHas('estudiante_examen', [
            'sis_estudiante' => '202600001',
            'id_examen' => self::EXAMEN,
            'estado' => 'habilitado',
        ]);
    }

    public function test_cargar_csv_muestra_el_banner_de_resumen(): void
    {
        $archivo = $this->csvOficial([
            ['1', '202600001', 'Quispe', 'Juan', 'Ingenieria de Sistemas', '', '', '', '', '', ''],
            ['2', '202600002', 'Lopez', 'Maria', 'Ingenieria de Sistemas', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertNoRedirect()
            ->assertSee('Carga completada')
            ->assertSee('Se inscribieron 2 estudiante(s)');
    }

    public function test_xlsx_se_carga_con_phpspreadsheet(): void
    {
        $archivo = $this->xlsx(['SIS', 'Apellidos', 'Nombres', 'Carrera'], [
            ['202600010', 'Flores', 'Sofia', 'Civil'],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertDatabaseHas('estudiante', ['sis_estudiante' => '202600010', 'nombre_estudiante' => 'Sofia']);
        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '202600010', 'id_curso' => self::CURSO]);
    }

    public function test_cruce_central_riesgos_deshabilita_reportados(): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => '202600020',
            'nombre_estudiante' => 'Carlos',
            'apellido_estudiante' => 'Rojas',
        ]);
        DB::table('central_riesgo')->insert([
            'id_registro' => 920301,
            'sis_estudiante' => '202600020',
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
            'id_registrador' => self::DOCENTE,
            'fecha_registro' => now()->toDateTimeString(),
            'tipo_infraccion' => 'tramposo',
        ]);

        $archivo = $this->csvOficial([
            ['1', '202600020', 'Rojas', 'Carlos', 'Sistemas', '', '', '', '', '', ''],
            ['2', '202600021', 'Vargas', 'Elena', 'Sistemas', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertDatabaseHas('estudiante_examen', [
            'sis_estudiante' => '202600020',
            'id_examen' => self::EXAMEN,
            'estado' => 'deshabilitado',
        ]);
        $this->assertDatabaseHas('estudiante_examen', [
            'sis_estudiante' => '202600021',
            'id_examen' => self::EXAMEN,
            'estado' => 'habilitado',
        ]);
    }

    public function test_extension_invalida_muestra_error(): void
    {
        $archivo = UploadedFile::fake()->create('lista.txt', 1, 'text/plain');

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasErrors(['archivo']);
    }

    public function test_sin_columna_sis_muestra_mensaje(): void
    {
        $archivo = $this->csv(['Apellidos', 'Nombres'], [['Perez', 'Ana']]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertSet('mensajeError', 'No se encontró la columna de código SIS.');
    }

    public function test_archivo_vacio_muestra_mensaje(): void
    {
        $archivo = $this->csv(self::ENCABEZADO_OFICIAL, []);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertSet('mensajeError', 'El archivo no contiene estudiantes.');
    }

    public function test_sis_no_de_nueve_digitos_se_omite(): void
    {
        $archivo = $this->csvOficial([
            ['1', '123', 'Corta', 'Fila', 'Sistemas', '', '', '', '', '', ''],
            ['2', '202600030', 'Valida', 'Fila', 'Sistemas', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('estudiante', ['sis_estudiante' => '202600030']);
        $this->assertDatabaseMissing('estudiante', ['sis_estudiante' => '123']);
    }

    public function test_duplicados_en_archivo_se_inscriben_una_sola_vez(): void
    {
        $archivo = $this->csvOficial([
            ['1', '202600040', 'Mamani', 'Luis', 'Sistemas', '', '', '', '', '', ''],
            ['2', '202600040', 'Mamani', 'Luis', 'Sistemas', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors();

        $this->assertSame(1, DB::table('estudiante_curso')
            ->where('id_curso', self::CURSO)
            ->where('sis_estudiante', '202600040')
            ->count());
    }

    public function test_reemplazo_pide_confirmacion_y_sustituye_la_lista(): void
    {
        // Lista previa del curso.
        DB::table('estudiante')->insert([
            'sis_estudiante' => '202600050',
            'nombre_estudiante' => 'Previo',
            'apellido_estudiante' => 'Alumno',
        ]);
        DB::table('estudiante_curso')->insert(['sis_estudiante' => '202600050', 'id_curso' => self::CURSO]);

        $archivo = $this->csvOficial([
            ['1', '202600051', 'Nueva', 'Lista', 'Sistemas', '', '', '', '', '', ''],
        ]);

        $componente = Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertSet('mostrarModalReemplazo', true)
            ->assertNoRedirect();

        // Antes de confirmar, nada cambió.
        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '202600050', 'id_curso' => self::CURSO]);

        $componente->call('confirmarReemplazo')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertDatabaseMissing('estudiante_curso', ['sis_estudiante' => '202600050', 'id_curso' => self::CURSO]);
        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '202600051', 'id_curso' => self::CURSO]);
    }

    public function test_cancelar_reemplazo_no_toca_la_base(): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => '202600060',
            'nombre_estudiante' => 'Previo',
            'apellido_estudiante' => 'Alumno',
        ]);
        DB::table('estudiante_curso')->insert(['sis_estudiante' => '202600060', 'id_curso' => self::CURSO]);

        $archivo = $this->csvOficial([
            ['1', '202600061', 'Nueva', 'Lista', 'Sistemas', '', '', '', '', '', ''],
        ]);

        Livewire::actingAs($this->docente())
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertSet('mostrarModalReemplazo', true)
            ->call('cancelarReemplazo')
            ->assertSet('mostrarModalReemplazo', false)
            ->assertNoRedirect();

        $this->assertDatabaseHas('estudiante_curso', ['sis_estudiante' => '202600060', 'id_curso' => self::CURSO]);
        $this->assertDatabaseMissing('estudiante_curso', ['sis_estudiante' => '202600061', 'id_curso' => self::CURSO]);
    }

    public function test_curso_archivado_bloquea_la_carga(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO)->update(['estado' => 'Archivado']);

        $this->actingAs($this->docente())
            ->get(route('cursos.cargar-csv', ['curso' => self::CURSO]))
            ->assertSessionHasErrors('curso');
    }

    public function test_docente_sin_permisos_no_puede_abrir_la_pagina(): void
    {
        $this->actingAs(Usuario::findOrFail(self::OTRO_DOCENTE))
            ->get(route('cursos.cargar-csv', ['curso' => self::CURSO]))
            ->assertForbidden();
    }
}
