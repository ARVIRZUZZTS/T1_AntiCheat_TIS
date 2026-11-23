<?php

/**
 * @file    RespaldarBaseDatosService.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-22
 *
 * @description
 * Servicio de dominio del respaldo automático de la base de datos (HU 33).
 * Genera un dump comprimido `.sql.gz` de la base PostgreSQL/Supabase y aplica
 * la política de retención (conserva solo los últimos N respaldos).
 *
 * El dump se obtiene con `pg_dump` sobre la conexión activa (en producción es
 * Supabase); se le pasan los datos de conexión por variables `PG*`, incluida
 * `PGSSLMODE`, porque Supabase exige SSL. Si `pg_dump` no está disponible o la
 * conexión no es PostgreSQL —el caso del entorno de pruebas, que corre sobre
 * SQLite— se cae a un volcado SQL portátil equivalente, para no acoplar las
 * pruebas a un binario externo ni a la base real.
 *
 * Ante cualquier error (conexión, fallo de `pg_dump`, escritura o compresión)
 * se limpia el archivo incompleto y se relanza la excepción: el comando que lo
 * invoca es el responsable de registrar el fallo y de NO notificar a nadie.
 *
 * @see  App\Console\Commands\RespaldarBaseDatosCommand
 * @see  App\Services\Backup\PlanificacionRespaldo
 *
 * @changelog
 * - 2026-11-22  [T1]  feat: respaldo comprimido con retención (#HU33).
 */

namespace App\Services\Backup;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class RespaldarBaseDatosService
{
    /** Prefijo de los archivos de respaldo; ayuda a filtrarlos en la retención. */
    private const PREFIJO = 'respaldo_';

    /** Extensión del dump comprimido. */
    private const EXTENSION = '.sql.gz';

    /** Tope de segundos que puede tardar `pg_dump` antes de abortar. */
    private const TIMEOUT_SEGUNDOS = 600;

    /**
     * Genera un respaldo comprimido y rota los antiguos.
     *
     * @param  CarbonInterface  $momento  Instante que fecha el respaldo; define el
     *                                    nombre del archivo y es la hora que se
     *                                    informa a los docentes.
     *
     * @return string  Ruta absoluta del archivo `.sql.gz` recién creado.
     *
     * @throws RuntimeException  Si falla el volcado, la compresión o la escritura.
     *
     * @author T1 - Tech One SRL
     *
     * @since  2026-11-22
     */
    public function ejecutar(CarbonInterface $momento): string
    {
        $directorio = $this->directorio();
        $ruta = $directorio.DIRECTORY_SEPARATOR.self::PREFIJO.$momento->format('Y-m-d_His').self::EXTENSION;

        try {
            $sql = $this->volcarSql();
            $this->escribirComprimido($ruta, $sql);
        } catch (\Throwable $error) {
            $this->limpiarParcial($ruta);

            throw new RuntimeException('No se pudo generar el respaldo de la base de datos.', 0, $error);
        }

        $this->aplicarRetencion($directorio);

        return $ruta;
    }

    /**
     * Devuelve el directorio de respaldos, creándolo si aún no existe.
     *
     * @return string  Ruta absoluta del directorio.
     *
     * @throws RuntimeException  Si no se puede crear el directorio.
     */
    private function directorio(): string
    {
        $directorio = (string) config('backup.directorio', storage_path('app/backups'));

        if (! is_dir($directorio) && ! mkdir($directorio, 0775, true) && ! is_dir($directorio)) {
            throw new RuntimeException("No se pudo crear el directorio de respaldos: {$directorio}");
        }

        return $directorio;
    }

    /**
     * Obtiene el volcado SQL de la base: con `pg_dump` si se puede, o portátil.
     *
     * @return string  Contenido SQL del respaldo.
     */
    private function volcarSql(): string
    {
        if (DB::connection()->getDriverName() === 'pgsql' && $this->hayPgDump()) {
            return $this->volcarConPgDump();
        }

        return $this->volcarPortable();
    }

    /** Indica si el binario `pg_dump` está disponible en el sistema. */
    private function hayPgDump(): bool
    {
        return (new ExecutableFinder)->find('pg_dump') !== null;
    }

    /**
     * Ejecuta `pg_dump` contra la conexión activa (Supabase en producción).
     *
     * @return string  SQL producido por `pg_dump`.
     *
     * @throws RuntimeException  Si `pg_dump` termina con error.
     */
    private function volcarConPgDump(): string
    {
        /** @var array<string, mixed> $config */
        $config = DB::connection()->getConfig();

        $entorno = [
            'PGHOST' => (string) ($config['host'] ?? '127.0.0.1'),
            'PGPORT' => (string) ($config['port'] ?? '5432'),
            'PGDATABASE' => (string) ($config['database'] ?? ''),
            'PGUSER' => (string) ($config['username'] ?? ''),
            'PGPASSWORD' => (string) ($config['password'] ?? ''),
            'PGSSLMODE' => (string) ($config['sslmode'] ?? 'prefer'),
        ];

        $proceso = new Process(
            ['pg_dump', '--no-owner', '--no-privileges', '--clean', '--if-exists'],
            null,
            $entorno,
            null,
            self::TIMEOUT_SEGUNDOS,
        );

        $proceso->run();

        if (! $proceso->isSuccessful()) {
            throw new RuntimeException('pg_dump falló: '.trim($proceso->getErrorOutput()));
        }

        return $proceso->getOutput();
    }

    /**
     * Volcado SQL portátil (sin binarios externos): recorre las tablas de la
     * conexión y emite los INSERT de sus filas. Es el camino del entorno de
     * pruebas, que no usa PostgreSQL ni Supabase.
     *
     * @return string  SQL con los datos de todas las tablas.
     */
    private function volcarPortable(): string
    {
        $driver = DB::connection()->getDriverName();
        $sql = "-- Respaldo portátil generado por la aplicación ({$driver}).\n";

        foreach ($this->tablas($driver) as $tabla) {
            foreach (DB::table($tabla)->get() as $fila) {
                $columnas = array_keys((array) $fila);
                $valores = array_map(fn ($valor): string => $this->valorSql($valor), array_values((array) $fila));

                $sql .= sprintf(
                    "INSERT INTO %s (%s) VALUES (%s);\n",
                    $tabla,
                    implode(', ', $columnas),
                    implode(', ', $valores),
                );
            }
        }

        return $sql;
    }

    /**
     * Nombres de las tablas de datos de la conexión, según el motor.
     *
     * @param  string  $driver  Driver activo (`pgsql`, `sqlite`, ...).
     *
     * @return list<string>  Tablas a volcar.
     */
    private function tablas(string $driver): array
    {
        if ($driver === 'sqlite') {
            $filas = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

            return array_map(fn (object $fila): string => (string) $fila->name, $filas);
        }

        $filas = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        return array_map(fn (object $fila): string => (string) $fila->tablename, $filas);
    }

    /** Serializa un valor para un INSERT del volcado portátil. */
    private function valorSql(mixed $valor): string
    {
        if ($valor === null) {
            return 'NULL';
        }

        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        if (is_bool($valor)) {
            return $valor ? 'TRUE' : 'FALSE';
        }

        return "'".str_replace("'", "''", (string) $valor)."'";
    }

    /**
     * Comprime el SQL y lo escribe en disco.
     *
     * @param  string  $ruta  Ruta absoluta del archivo `.sql.gz`.
     * @param  string  $sql   Contenido SQL a comprimir.
     *
     * @throws RuntimeException  Si la compresión o la escritura fallan.
     */
    private function escribirComprimido(string $ruta, string $sql): void
    {
        $comprimido = gzencode($sql, 9);

        if ($comprimido === false) {
            throw new RuntimeException('No se pudo comprimir el respaldo.');
        }

        if (file_put_contents($ruta, $comprimido) === false) {
            throw new RuntimeException("No se pudo escribir el respaldo en: {$ruta}");
        }
    }

    /**
     * Conserva solo los respaldos más recientes y borra los más antiguos.
     *
     * Los nombres llevan la fecha en formato ordenable (`Y-m-d_His`), así que
     * ordenarlos como texto equivale a ordenarlos por antigüedad.
     *
     * @param  string  $directorio  Carpeta de respaldos.
     */
    private function aplicarRetencion(string $directorio): void
    {
        $maximo = max(1, (int) config('backup.maximo', 3));

        $archivos = glob($directorio.DIRECTORY_SEPARATOR.self::PREFIJO.'*'.self::EXTENSION);

        if ($archivos === false || count($archivos) <= $maximo) {
            return;
        }

        rsort($archivos);

        foreach (array_slice($archivos, $maximo) as $viejo) {
            @unlink($viejo);
        }
    }

    /** Borra un archivo de respaldo incompleto tras un fallo, si llegó a crearse. */
    private function limpiarParcial(string $ruta): void
    {
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
