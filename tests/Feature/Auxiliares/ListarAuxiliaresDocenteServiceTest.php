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
 * local de pruebas: normalización del estado a ACTIVO/BAJA, inclusión de los
 * dados de baja, una sola consulta (sin N+1) y orden por curso y luego por
 * apellido.
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresDocenteService
 * @see  App\Repositories\AuxiliarCursoRepository
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  test: creación inicial con los cuatro
 *   criterios de aceptación del ticket.
 */

namespace Tests\Feature\Auxiliares;

use App\Services\Auxiliar\ListarAuxiliaresDocenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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

    public function test_el_auxiliar_dado_de_baja_se_devuelve_como_baja(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $beta = $this->buscarPorSis($filas, '9100012');

        $this->assertSame('BAJA', $beta['estado']);
    }

    public function test_los_auxiliares_dados_de_baja_siguen_apareciendo_en_la_lista(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $this->assertContains('9100012', array_column($filas, 'codigoSis'));
        $this->assertCount(4, $filas);
    }

    public function test_consulta_en_una_sola_query_sin_n_mas_uno(): void
    {
        $consultas = 0;
        DB::listen(function () use (&$consultas): void {
            $consultas++;
        });

        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $this->assertCount(4, $filas);
        $this->assertSame(1, $consultas, 'La lista debe salir en una sola consulta.');
    }

    public function test_el_orden_es_curso_luego_apellido(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        // Algoritmos (Alfa, Beta, Gamma) antes que Zoología (Zúñiga), y dentro
        // de Algoritmos los apellidos en orden alfabético.
        $this->assertSame(
            ['9100011', '9100012', '9100013', '9100014'],
            array_column($filas, 'codigoSis'),
        );
        $this->assertSame(
            ['Algoritmos', 'Algoritmos', 'Algoritmos', 'Zoología'],
            array_column($filas, 'curso'),
        );
        $this->assertSame(
            ['Alfa', 'Beta', 'Gamma', 'Zúñiga'],
            array_column($filas, 'apellido'),
        );
    }

    public function test_no_incluye_auxiliares_de_cursos_de_otro_docente(): void
    {
        $filas = $this->servicio()->ejecutar(self::DOCENTE);

        $this->assertNotContains('9100015', array_column($filas, 'codigoSis'));
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
        ]);

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
