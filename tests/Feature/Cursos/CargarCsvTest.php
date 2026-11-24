<?php

namespace Tests\Feature\Cursos;

use App\Livewire\Cursos\CargarCsv;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
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

    private function archivoCsv(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('lista.csv', "SIS\n910200001\n");
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

    public function test_get_curso_finalizado_bloquea(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO)->update(['estado' => 'Finalizo']);
        $docente = Usuario::findOrFail(self::DOCENTE);
        $this->actingAs($docente)
            ->get(route('cursos.cargar-csv', ['curso' => self::CURSO]))
            ->assertSessionHasErrors('curso');
    }

    public function test_cancelar_no_sale_de_cargar_csv(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $this->archivoCsv())
            ->call('cancelar')
            ->assertNoRedirect()
            ->assertSet('archivo', null)
            ->assertSet('mensajeError', '')
            ->assertSet('mensajeExito', '');
    }

    public function test_cambiar_curso_limpia_archivo_y_avisos(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $this->archivoCsv())
            ->set('mensajeError', 'error previo')
            ->set('mensajeExito', 'exito previo')
            ->set('cursoId', null)
            ->assertSet('archivo', null)
            ->assertSet('mensajeError', '')
            ->assertSet('mensajeExito', '');
    }

    public function test_carga_exitosa_muestra_resumen_en_la_misma_vista(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $archivo = $this->archivoCsv();

        Livewire::actingAs($docente)
            ->test(CargarCsv::class, ['curso' => Curso::findOrFail(self::CURSO)])
            ->set('archivo', $archivo)
            ->call('cargar')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('mensajeExito', 'Se inscribieron 1 estudiante(s): 1 habilitado(s), '
                .'0 deshabilitado(s) por la central de riesgos. Duplicados en el archivo: 0. Filas omitidas: 0.');
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
