<?php

/**
 * @file    ListarAuxiliaresDocenteServiceTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 *
 * @description
 * Pruebas de integración de `ListarAuxiliaresDocenteService` contra la base
 * local de pruebas: estado derivado de las asignaciones activas, inclusión de
 * las cuentas sin curso (INACTIVO), dos consultas (sin N+1) y orden por estado
 * y apellido.
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresDocenteService
 * @see  App\Repositories\AuxiliarCursoRepository
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  test: creación inicial con los cuatro
 *   criterios de aceptación del ticket.
 */

namespace Tests\Feature\Auxiliares;

use App\Models\Rol;
use App\Services\Auxiliar\ListarAuxiliaresDocenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListarAuxiliaresDocenteServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** IDs dedicados para no chocar con los datos sembrados. */
    private const DOCENTE = 9100001;

    private const OTRO_DOCENTE = 9100002;

    private const AUX_ALFA = 9100011;

    private const AUX_BETA = 9100012;

    private const AUX_GAMMA = 9100013;

    private const AUX_ZUNIGA = 9100014;

    private const AUX_AJENO = 9100015;

    private const AUX_SIN_CURSO = 9100016;

    private const CURSO_ZOOLOGIA = 9101001;

    private const CURSO_ALGORITMOS = 9101002;

    private const CURSO_AJENO = 9101003;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sembrar();
    }

    public function test_el_auxiliar_activo_se_devuelve_como_activo(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $zuniga = $this->buscarPorSis($filas, '9100014');

        $this->assertSame('ACTIVO', $zuniga['estado']);
        $this->assertSame('Ana', $zuniga['nombre']);
        $this->assertSame('Zúñiga', $zuniga['apellido']);
        $this->assertSame('Zoología', $zuniga['curso']);
    }

    public function test_el_auxiliar_sin_asignaciones_activas_se_devuelve_como_inactivo(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $beta = $this->buscarPorSis($filas, '9100012');

        $this->assertSame('INACTIVO', $beta['estado']);
        $this->assertSame('', $beta['curso']);
    }

    public function test_las_cuentas_sin_curso_siguen_apareciendo_en_la_lista(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $sinCurso = $this->buscarPorSis($filas, '9100016');

        $this->assertSame('INACTIVO', $sinCurso['estado']);
        $this->assertCount(6, $filas);
    }

    public function test_consulta_en_tres_queries_sin_n_mas_uno(): void
    {
        $consultas = 0;
        DB::listen(function () use (&$consultas): void {
            $consultas++;
        });

        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $this->assertCount(6, $filas);
        // Cuentas, asignaciones a cursos y asignaciones a examenes: tres
        // consultas constantes, sin N+1 por auxiliar.
        $this->assertSame(3, $consultas, 'Cuentas y asignaciones salen en tres consultas, sin N+1.');
    }

    public function test_el_orden_es_activos_luego_inactivos_y_apellido(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        // Primero los activos por apellido (Alfa, Gamma, Zúñiga) y después los
        // inactivos por apellido (Ajeno, Beta, Sin curso).
        $this->assertSame(
            ['9100011', '9100013', '9100014', '9100015', '9100012', '9100016'],
            array_column($filas, 'codigoSis'),
        );
        $this->assertSame(
            ['Algoritmos', 'Algoritmos', 'Zoología', '', '', ''],
            array_column($filas, 'curso'),
        );
        $this->assertSame(
            ['ACTIVO', 'ACTIVO', 'ACTIVO', 'INACTIVO', 'INACTIVO', 'INACTIVO'],
            array_column($filas, 'estado'),
        );
    }

    public function test_las_asignaciones_de_otro_docente_no_cuentan_como_activas(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $ajeno = $this->buscarPorSis($filas, '9100015');

        $this->assertSame('INACTIVO', $ajeno['estado']);
        $this->assertSame('', $ajeno['curso']);
    }

    public function test_la_gestion_del_curso_aparece_junto_al_nombre(): void
    {
        $idGestion = (int) DB::table('tipo_gestion')->value('id_tipo_gestion');
        $nombreGestion = Str::ucfirst((string) DB::table('tipo_gestion')
            ->where('id_tipo_gestion', $idGestion)
            ->value('nombre_tipo_gestion'));
        $anio = now()->year;

        DB::table('curso_tipo_gestion')->insert([
            'id_curso' => self::CURSO_ALGORITMOS,
            'id_tg' => $idGestion,
            'anio' => $anio,
        ]);

        $filas = $this->servicio()->ejecutar(self::DOCENTE);
        $alfa = $this->buscarPorSis($filas, '9100011');
        $gamma = $this->buscarPorSis($filas, '9100013');
        $etiqueta = 'Algoritmos - '.$nombreGestion.' '.$anio;

        $this->assertSame($etiqueta, $alfa['cursos'][0]);
        $this->assertSame($etiqueta, $alfa['curso']);
        $this->assertSame($etiqueta, $gamma['cursos'][0]);
    }

    public function test_el_tipo_de_auxiliar_va_en_la_fila(): void
    {
        DB::table('usuario')->where('id_usuario', self::AUX_ZUNIGA)->update(['tipo_auxiliar' => 'externo']);

        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $this->assertSame('Externo', $this->buscarPorSis($filas, '9100014')['tipo']);
        $this->assertSame('', $this->buscarPorSis($filas, '9100012')['tipo']);
    }

    private function servicio(): ListarAuxiliaresDocenteService
    {
        return app(ListarAuxiliaresDocenteService::class);
    }

    /**
     * @param  list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>  $filas
     * @return array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}
     */
    private function buscarPorSis(array $filas, string $sis): array
    {
        foreach ($filas as $fila) {
            if ($fila['codigoSis'] === $sis) {
                return $fila;
            }
        }

        $this->fail('No se encontró el auxiliar con SIS '.$sis.'.');
    }

    /**
     * Datos en desorden deliberado: la salida debe ordenarla el servicio.
     * La fila ajena pertenece a otro docente y nunca debe aparecer.
     */
    private function sembrar(): void
    {
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'cod_sis' => '9100001', 'password' => 'secreto1', 'nombre_usuario' => 'Roberto', 'apellido' => 'Docente'],
            ['id_usuario' => self::OTRO_DOCENTE, 'cod_sis' => '9100002', 'password' => 'secreto2', 'nombre_usuario' => 'Patricia', 'apellido' => 'Otra'],
            ['id_usuario' => self::AUX_ZUNIGA, 'cod_sis' => '9100014', 'password' => 'secreto3', 'nombre_usuario' => 'Ana', 'apellido' => 'Zúñiga'],
            ['id_usuario' => self::AUX_BETA, 'cod_sis' => '9100012', 'password' => 'secreto4', 'nombre_usuario' => 'Marco', 'apellido' => 'Beta'],
            ['id_usuario' => self::AUX_ALFA, 'cod_sis' => '9100011', 'password' => 'secreto5', 'nombre_usuario' => 'Zoe', 'apellido' => 'Alfa'],
            ['id_usuario' => self::AUX_GAMMA, 'cod_sis' => '9100013', 'password' => 'secreto6', 'nombre_usuario' => 'Alicia', 'apellido' => 'Gamma'],
            ['id_usuario' => self::AUX_AJENO, 'cod_sis' => '9100015', 'password' => 'secreto7', 'nombre_usuario' => 'Luis', 'apellido' => 'Ajeno'],
            ['id_usuario' => self::AUX_SIN_CURSO, 'cod_sis' => '9100016', 'password' => 'secreto8', 'nombre_usuario' => 'Nora', 'apellido' => 'Sin curso'],
        ]);

        DB::table('rol')->insertOrIgnore([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => Rol::NOMBRE_AUXILIAR],
        ]);

        $rolAuxiliar = DB::table('rol')->where('nombre_rol', Rol::NOMBRE_AUXILIAR)->value('id_rol');

        foreach ([self::AUX_ZUNIGA, self::AUX_BETA, self::AUX_ALFA, self::AUX_GAMMA, self::AUX_AJENO, self::AUX_SIN_CURSO] as $idAuxiliar) {
            DB::table('rol_usuario')->insert(['id_usuario' => $idAuxiliar, 'id_rol' => $rolAuxiliar]);
        }

        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_ZOOLOGIA, 'nombre_curso' => 'Zoología', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => '2026-11-08', 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_ALGORITMOS, 'nombre_curso' => 'Algoritmos', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => '2026-11-08', 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_AJENO, 'nombre_curso' => 'Química', 'sis_doc' => self::OTRO_DOCENTE, 'fecha_creacion' => '2026-11-08', 'estado' => 'EnCurso'],
        ]);

        DB::table('auxiliar_curso')->insert([
            ['id_auxiliar' => self::AUX_ZUNIGA, 'id_curso' => self::CURSO_ZOOLOGIA, 'estado' => 'Activo'],
            ['id_auxiliar' => self::AUX_BETA, 'id_curso' => self::CURSO_ALGORITMOS, 'estado' => 'Baja'],
            ['id_auxiliar' => self::AUX_ALFA, 'id_curso' => self::CURSO_ALGORITMOS, 'estado' => 'Activo'],
            ['id_auxiliar' => self::AUX_GAMMA, 'id_curso' => self::CURSO_ALGORITMOS, 'estado' => 'Activo'],
            ['id_auxiliar' => self::AUX_AJENO, 'id_curso' => self::CURSO_AJENO, 'estado' => 'Activo'],
        ]);
    }
}
