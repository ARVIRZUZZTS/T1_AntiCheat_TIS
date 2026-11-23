<?php

/**
 * @file    RespaldoAutomaticoTest.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-22
 *
 * @description
 * Pruebas de la HU 33: respaldo automático quincenal de la base de datos con
 * aviso de solo lectura a los docentes.
 *
 * Reglas cubiertas:
 * - el comando genera un respaldo comprimido `.sql.gz` fechado;
 * - la política de retención conserva como mucho 3 respaldos (borra el más viejo);
 * - la programación es quincenal: elegible el martes 24/11/2026, NO el 01/12/2026
 *   y de nuevo SÍ el 08/12/2026, a las 08:20 en `America/La_Paz`;
 * - el aviso va solo a los docentes (no a auxiliares), lleva la fecha y hora
 *   exactas y es de solo lectura (sin URLs ni botones de acción);
 * - ante un fallo del respaldo se registra en `Log::error` y no se envía ningún
 *   aviso.
 *
 * Igual que las demás pruebas de notificaciones, el esquema se arma sobre una
 * conexión SQLite en memoria dedicada: NO se toca la base compartida de
 * Supabase. El respaldo corre por su camino portátil (sin `pg_dump`) y escribe
 * en un directorio temporal, no en `storage/app/backups`.
 *
 * @see  App\Services\Backup\RespaldarBaseDatosService
 * @see  App\Services\Backup\PlanificacionRespaldo
 * @see  App\Services\Notificacion\NotificacionService
 *
 * @changelog
 * - 2026-11-22  [T1]  test: criterios de aceptación de la HU 33.
 */

namespace Tests\Feature\Backup;

use App\Services\Backup\PlanificacionRespaldo;
use App\Services\Backup\RespaldarBaseDatosService;
use App\Services\Notificacion\NotificacionService;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class RespaldoAutomaticoTest extends TestCase
{
    private const DOCENTE_A = 1;

    private const DOCENTE_B = 2;

    private const AUXILIAR = 3;

    private string $directorioBackups;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.backup_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('backup_pruebas');

        $this->directorioBackups = sys_get_temp_dir().'/backups_pruebas_'.uniqid();
        config(['backup.directorio' => $this->directorioBackups, 'backup.maximo' => 3]);

        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        $this->borrarDirectorio($this->directorioBackups);

        DB::purge('backup_pruebas');
        parent::tearDown();
    }

    public function test_el_comando_genera_un_respaldo_comprimido_con_fecha(): void
    {
        $this->artisan('db:respaldar-automatico')->assertExitCode(0);

        $archivos = $this->archivosRespaldo();

        $this->assertCount(1, $archivos);
        $this->assertStringEndsWith('.sql.gz', $archivos[0]);
        $this->assertMatchesRegularExpression('/respaldo_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/', $archivos[0]);

        // El archivo es un gzip válido y no vacío.
        $contenido = (string) file_get_contents($archivos[0]);
        $this->assertNotSame('', gzdecode($contenido));

        // Quedó registrado el aviso con la hora del respaldo.
        $this->assertDatabaseHas('notificacion_docente', [
            'tipo' => NotificacionService::TIPO_RESPALDO_BD,
            'id_docente_destinatario' => self::DOCENTE_A,
        ]);
    }

    public function test_flujo_completo_del_comando_respalda_y_avisa_a_los_docentes(): void
    {
        // Se fija "ahora" en un martes elegible para que la fecha del archivo y
        // la del aviso coincidan con el momento del respaldo.
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 11, 24, 8, 20, 0, PlanificacionRespaldo::ZONA));

        $this->artisan('db:respaldar-automatico')
            ->expectsOutputToContain('Respaldo generado en')
            ->assertExitCode(0);

        // 1. Se creó exactamente un respaldo comprimido y legible.
        $archivos = $this->archivosRespaldo();
        $this->assertCount(1, $archivos);
        $this->assertStringContainsString('respaldo_2026-11-24_082000.sql.gz', $archivos[0]);
        $this->assertNotSame('', gzdecode((string) file_get_contents($archivos[0])));

        // 2. Hay un aviso por docente (A y B) y ninguno para el auxiliar.
        $this->assertDatabaseCount('notificacion_docente', 2);
        $this->assertDatabaseMissing('notificacion_docente', ['id_docente_destinatario' => self::AUXILIAR]);

        // 3. El docente ve su aviso con la fecha/hora exactas y sin acciones.
        $aviso = app(NotificacionService::class)->listarPara(Usuario::findOrFail(self::DOCENTE_B))->first();
        $this->assertSame(NotificacionService::TIPO_RESPALDO_BD, $aviso['tipo']);
        $this->assertSame(
            'Se realizó un respaldo automático de la base de datos el 24/11/2026 a las 08:20.',
            $aviso['descripcion'],
        );
        $this->assertArrayNotHasKey('detalle_href', $aviso);
        $this->assertArrayNotHasKey('datos', $aviso);

        CarbonImmutable::setTestNow();
    }

    public function test_la_retencion_conserva_solo_los_tres_respaldos_mas_recientes(): void
    {
        $servicio = app(RespaldarBaseDatosService::class);
        $base = CarbonImmutable::create(2026, 11, 24, 8, 20, 0, PlanificacionRespaldo::ZONA);

        $rutas = [];
        foreach (range(0, 3) as $indice) {
            $rutas[] = $servicio->ejecutar($base->addMinutes($indice));
        }

        $archivos = $this->archivosRespaldo();

        $this->assertCount(3, $archivos);

        // El más antiguo (el primero generado) fue eliminado; los 3 recientes siguen.
        $this->assertFileDoesNotExist($rutas[0]);
        $this->assertFileExists($rutas[1]);
        $this->assertFileExists($rutas[2]);
        $this->assertFileExists($rutas[3]);
    }

    public function test_la_programacion_es_quincenal_los_martes_a_las_0820(): void
    {
        $planificacion = new PlanificacionRespaldo;

        $elegible = fn (string $fecha): bool => $planificacion->esElegible(
            CarbonImmutable::parse($fecha.' 08:20', PlanificacionRespaldo::ZONA)
        );

        $this->assertTrue($elegible('2026-11-24'), 'Debe correr el martes 24/11/2026.');
        $this->assertFalse($elegible('2026-12-01'), 'NO debe correr el martes 01/12/2026.');
        $this->assertTrue($elegible('2026-12-08'), 'Debe correr de nuevo el martes 08/12/2026.');

        $evento = $this->eventoRespaldo();

        $this->assertNotNull($evento, 'El respaldo debe estar programado en el Scheduler.');
        $this->assertSame('20 8 * * 2', $evento->expression);
        $this->assertSame(PlanificacionRespaldo::ZONA, $evento->timezone);
    }

    public function test_el_aviso_va_solo_a_docentes_con_fecha_exacta_y_sin_acciones(): void
    {
        $momento = CarbonImmutable::create(2026, 11, 24, 8, 20, 0, PlanificacionRespaldo::ZONA);

        $avisos = app(NotificacionService::class)->notificarRespaldoBaseDatos($momento);

        // Un aviso por docente (A y B), ninguno para el auxiliar.
        $this->assertCount(2, $avisos);
        $this->assertDatabaseHas('notificacion_docente', [
            'id_docente_destinatario' => self::DOCENTE_A, 'tipo' => NotificacionService::TIPO_RESPALDO_BD,
        ]);
        $this->assertDatabaseHas('notificacion_docente', [
            'id_docente_destinatario' => self::DOCENTE_B, 'tipo' => NotificacionService::TIPO_RESPALDO_BD,
        ]);
        $this->assertDatabaseMissing('notificacion_docente', [
            'id_docente_destinatario' => self::AUXILIAR,
        ]);

        $servicio = app(NotificacionService::class);
        $this->assertSame([], $servicio->listarPara(Usuario::findOrFail(self::AUXILIAR))->all());

        $delDocente = $servicio->listarPara(Usuario::findOrFail(self::DOCENTE_A));
        $this->assertCount(1, $delDocente);

        $aviso = $delDocente->first();
        $this->assertStringContainsString('24/11/2026', $aviso['descripcion']);
        $this->assertStringContainsString('08:20', $aviso['descripcion']);

        // Solo lectura: sin enlace de detalle, sin datos de acción, sin botones.
        $this->assertArrayNotHasKey('detalle_href', $aviso);
        $this->assertArrayNotHasKey('datos', $aviso);
    }

    public function test_un_fallo_se_registra_en_log_error_y_no_envia_avisos(): void
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->never();

        $this->mock(RespaldarBaseDatosService::class, function ($mock): void {
            $mock->shouldReceive('ejecutar')->andThrow(new RuntimeException('fallo simulado'));
        });

        $this->artisan('db:respaldar-automatico')->assertExitCode(1);

        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    /** @return list<string> Rutas de los `.sql.gz` del directorio de pruebas, ordenadas. */
    private function archivosRespaldo(): array
    {
        $archivos = glob($this->directorioBackups.'/respaldo_*.sql.gz');

        if ($archivos === false) {
            return [];
        }

        sort($archivos);

        return $archivos;
    }

    private function eventoRespaldo(): ?Event
    {
        foreach (app(Schedule::class)->events() as $evento) {
            if (str_contains((string) $evento->command, 'db:respaldar-automatico')) {
                return $evento;
            }
        }

        return null;
    }

    private function borrarDirectorio(string $directorio): void
    {
        if (! is_dir($directorio)) {
            return;
        }

        foreach ((array) glob($directorio.'/*') as $archivo) {
            if (is_string($archivo)) {
                @unlink($archivo);
            }
        }

        @rmdir($directorio);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('correo')->nullable();
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        // `listarPara` arma una rama legacy que referencia `curso` en el WHERE
        // (aunque el aviso de respaldo no la use); la tabla debe existir.
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('estado')->nullable();
        });
        Schema::create('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_notificacion')->primary();
            $tabla->integer('id_central_riesgo')->nullable();
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_docente_destinatario')->nullable();
            $tabla->integer('id_invitacion')->nullable();
            $tabla->string('tipo')->nullable();
            $tabla->string('estado');
            $tabla->boolean('oculta')->default(false);
            $tabla->timestamp('fecha_evento')->nullable();
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insertOrIgnore([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'cod_sis' => 'DOCA', 'password' => 'x', 'nombre_usuario' => 'Doc', 'apellido' => 'A'],
            ['id_usuario' => self::DOCENTE_B, 'cod_sis' => 'DOCB', 'password' => 'x', 'nombre_usuario' => 'Doc', 'apellido' => 'B'],
            ['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX', 'password' => 'x', 'nombre_usuario' => 'Aux', 'apellido' => 'Uno'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'id_rol' => 1],
            ['id_usuario' => self::DOCENTE_B, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
    }
}
