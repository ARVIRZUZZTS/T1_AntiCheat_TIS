<?php

namespace Tests\Feature\Cursos;

use App\Livewire\Cursos\CargarCsv;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CargarCsvTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 910201;

    private const CURSO = 910201;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'DOC910201',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Prueba',
        ]);
        DB::table('rol_usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'id_rol' => DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol'),
        ]);

        Curso::create([
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Curso Prueba',
            'sis_doc' => self::DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);
    }

    private function archivoXlsx(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'SIS');
        $sheet->setCellValue('A2', '910200001');
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return UploadedFile::fake()->createWithContent('lista.xlsx', file_get_contents($path));
    }

    public function test_get_autorizado_renderiza_componente(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $this->actingAs($docente)
            ->get(route('cursos.cargar-csv', ['curso' => self::CURSO]))
            ->assertOk()
            ->assertSeeLivewire(CargarCsv::class);
    }

    public function test_get_curso_archivado_bloquea(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO)->update(['estado' => 'Archivado']);
        $docente = Usuario::findOrFail(self::DOCENTE);
        $this->actingAs($docente)
            ->get(route('cursos.cargar-csv', ['curso' => self::CURSO]))
            ->assertSessionHasErrors('curso');
    }

    public function test_cancelar_redirige_a_detalle(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->call('cancelar')
            ->assertRedirect(route('cursos.detalle', ['curso' => self::CURSO]));
    }

    public function test_carga_exitosa_redirige_con_flash(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $archivo = $this->archivoXlsx();

        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors()
            ->assertRedirect(route('cursos.ver-estudiantes', ['curso' => self::CURSO]));

        $this->assertNotNull(session('mensaje'));
    }

    public function test_archivo_invalido_muestra_error(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $archivo = UploadedFile::fake()->create('lista.txt', 1, 'text/plain');

        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasErrors(['archivo']);
    }

    public function test_upload_sin_archivo_muestra_error_validacion(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->call('cargar')
            ->assertHasErrors(['archivo']);
    }
}
