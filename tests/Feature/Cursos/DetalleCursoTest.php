<?php

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DetalleCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 990001;

    private const OTRO_DOCENTE = 990002;

    private const CURSO_PROPIO = 990101;

    private const CURSO_AJENO = 990102;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');

        foreach ([self::DOCENTE => 'DetalleUno', self::OTRO_DOCENTE => 'DetalleDos'] as $id => $nombre) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => $nombre,
                'apellido' => 'Detalle Curso',
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        $gestiones = $this->gestiones();

        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_PROPIO, 'nombre_curso' => 'Calculo I - Grupo 2', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString()],
            ['id_curso' => self::CURSO_AJENO, 'nombre_curso' => 'Calculo II - Grupo 3', 'sis_doc' => self::OTRO_DOCENTE, 'estado' => 'EnCurso', 'fecha_creacion' => now()->toDateString()],
        ]);
        DB::table('curso_tipo_gestion')->insert([
            ['id_curso' => self::CURSO_PROPIO, 'id_tg' => $gestiones[1], 'anio' => now()->year + 1],
            ['id_curso' => self::CURSO_AJENO, 'id_tg' => $gestiones[0], 'anio' => now()->year],
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE), 'web');
    }

    public function test_devuelve_materia_grupo_gestion_y_anio_del_curso(): void
    {
        $gestiones = $this->gestiones();

        $this->getJson(route('api.cursos.detalle', self::CURSO_PROPIO))
            ->assertOk()
            ->assertExactJson(['datos' => [
                'id' => self::CURSO_PROPIO,
                'materia' => 'Calculo I',
                'grupo' => '2',
                'gestion' => $gestiones[1],
                'gestion_nombre' => $this->nombreGestion($gestiones[1]),
                'anio' => now()->year + 1,
                'nombre' => 'Calculo I - Grupo 2',
            ]]);
    }

    public function test_un_curso_inexistente_devuelve_404(): void
    {
        $this->getJson(route('api.cursos.detalle', 999999))->assertNotFound();
    }

    public function test_un_curso_de_otro_docente_devuelve_403(): void
    {
        $this->actingAs(Usuario::findOrFail(self::OTRO_DOCENTE), 'web')
            ->getJson(route('api.cursos.detalle', self::CURSO_PROPIO))
            ->assertForbidden();
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson(route('api.cursos.detalle', self::CURSO_PROPIO))->assertUnauthorized();
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

    private function nombreGestion(int $id): string
    {
        return Str::ucfirst((string) DB::table('tipo_gestion')
            ->where('id_tipo_gestion', $id)
            ->value('nombre_tipo_gestion'));
    }
}
