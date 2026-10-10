<?php

/**
 * @file    generate-deploy-seeder.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-06
 *
 * @updated 2026-10-06
 *
 * @description
 * Regenera `database/seeders/DeploySeeder.php` a partir de la base de
 * desarrollo local, con TODOS los datos que hay en ese momento (usuarios,
 * estudiantes, examenes, asistencias, etc.).
 *
 * El seeder generado es un snapshot: sirve para cargar la misma base en el
 * servidor de la UMSS, donde no hay `php artisan` ni `pg_dump`. El schema no
 * lo toca, eso va por `database/sql/000_deploy_completo.sql`.
 *
 * Uso:
 *
 *     php tools/generate-deploy-seeder.php
 *
 * Lee las credenciales del `.env` de la raiz del proyecto.
 *
 * @changelog
 * - 2026-10-06  [T1]  feat: generador del seeder de deploy.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

// ---------------------------------------------------------------------------
// Credenciales de la base local (archivo .env de la raiz)
// ---------------------------------------------------------------------------

$envPath = $root.DIRECTORY_SEPARATOR.'.env';

if (! is_file($envPath)) {
    fwrite(STDERR, "No existe {$envPath}\n");
    exit(1);
}

$env = [];

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $value = trim($value);

    if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
        $value = substr($value, 1, -1);
    }

    $env[trim($key)] = $value;
}

if (($env['DB_DATABASE'] ?? '') === '') {
    fwrite(STDERR, "El .env no define DB_DATABASE\n");
    exit(1);
}

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '5432',
    $env['DB_DATABASE']
);

$pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// ---------------------------------------------------------------------------
// Tablas del esquema public, en orden topologico de dependencia por FK
// ---------------------------------------------------------------------------

$tables = $pdo->query(
    "SELECT table_name
       FROM information_schema.tables
      WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
      ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$dependeDe = array_fill_keys($tables, []);

$fks = $pdo->query(
    "SELECT con.conrelid::regclass::text  AS hijo,
            con.confrelid::regclass::text AS padre
       FROM pg_constraint con
      WHERE con.contype = 'f'
        AND con.connamespace = 'public'::regnamespace"
)->fetchAll();

foreach ($fks as $fk) {
    $hijo = $fk['hijo'];
    $padre = $fk['padre'];

    if (
        isset($dependeDe[$hijo], $dependeDe[$padre])
        && ! in_array($padre, $dependeDe[$hijo], true)
    ) {
        $dependeDe[$hijo][] = $padre;
    }
}

// Kahn: primero las tablas cuyas dependencias ya estan resueltas.
$ordenada = [];
$pendientes = $tables;

while ($pendientes !== []) {
    $listos = [];

    foreach ($pendientes as $t) {
        $faltan = array_diff($dependeDe[$t], $ordenada);

        if (array_intersect($faltan, $pendientes) === []) {
            $listos[] = $t;
        }
    }

    if ($listos === []) {
        // Ciclo de FKs: se rompe eligiendo la primera que queda.
        $listos = [$pendientes[0]];
    }

    foreach ($listos as $t) {
        $ordenada[] = $t;
    }

    $pendientes = array_values(array_diff($pendientes, $listos));
}

// ---------------------------------------------------------------------------
// Columnas por tabla
// ---------------------------------------------------------------------------

$columnas = [];

foreach ($pdo->query(
    "SELECT table_name, column_name, data_type, ordinal_position
       FROM information_schema.columns
      WHERE table_schema = 'public'
      ORDER BY table_name, ordinal_position"
) as $col) {
    $columnas[$col['table_name']][] = $col;
}

// ---------------------------------------------------------------------------
// Volcado de datos
// ---------------------------------------------------------------------------

/**
 * Convierte el valor crudo de pdo_pgsql a su literal PHP equivalente.
 *
 * pdo_pgsql devuelve tipos nativos (int, float, bool, null) para las columnas
 * conocidas y string para el resto, asi que el caster es defensivo.
 */
function lit(mixed $value, string $type): string
{
    if ($value === null) {
        return 'null';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_int($value)) {
        return (string) $value;
    }

    if (is_float($value)) {
        return (string) $value;
    }

    if ($type === 'boolean') {
        return in_array($value, ['t', 'true', '1'], true) ? 'true' : 'false';
    }

    if (in_array($type, ['smallint', 'integer', 'bigint'], true)) {
        return (string) (int) $value;
    }

    if (in_array($type, ['real', 'double precision', 'numeric', 'money'], true)) {
        return (string) (float) $value;
    }

    return var_export($value, true);
}

$bloques = [];
$filasTotales = 0;
$tablasConDatos = 0;

foreach ($ordenada as $tabla) {
    $cols = $columnas[$tabla] ?? [];

    if ($cols === []) {
        continue;
    }

    $filas = $pdo->query(sprintf('SELECT * FROM public.%s', $tabla))->fetchAll();

    if ($filas === []) {
        continue;
    }

    $lineas = [];

    foreach ($filas as $fila) {
        $pares = [];

        foreach ($cols as $c) {
            $pares[] = sprintf(
                '%s => %s',
                var_export($c['column_name'], true),
                lit($fila[$c['column_name']], $c['data_type'])
            );
        }

        $lineas[] = '                ['.implode(', ', $pares).'],';
    }

    $bloques[] = sprintf(
        "        \$this->tabla(\n            '%s',\n            [\n%s\n            ]\n        );",
        $tabla,
        implode("\n", $lineas)
    );

    $filasTotales += count($filas);
    $tablasConDatos++;
}

$listaTruncate = implode(', ', array_map(
    fn (string $t): string => "'{$t}'",
    $ordenada
));

// ---------------------------------------------------------------------------
// Archivo generado
// ---------------------------------------------------------------------------

$cabecera = <<<'CAB'
<?php

/**
 * @file    DeploySeeder.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-06
 *
 * @updated 2026-10-06
 *
 * @description
 * Snapshot de la base de desarrollo local, con TODOS los datos que existian
 * cuando se genero. Sirve para levantar la misma base en el servidor de la
 * UMSS, donde no hay terminal y no se puede correr `php artisan db:seed`.
 *
 * Este seeder solo carga DATOS. El esquema lo crea
 * `database/sql/000_deploy_completo.sql` (o `php artisan migrate` en local):
 * si las tablas no existen, revienta.
 *
 * ADVERTENCIA: es destructivo. Antes de insertar hace un TRUNCATE ... CASCADE
 * de todas las tablas, asi que pisa cualquier dato posterior a la generacion.
 * Si el snapshot quedo viejo, volvelo a generar con:
 *
 *     php tools/generate-deploy-seeder.php
 *
 * Uso:
 *
 *     php artisan db:seed --class=DeploySeeder
 *
 * La base que apunta el `.env` es la que se pisa, revisala antes de correrlo.
 *
 * @changelog
 * - 2026-10-06  [T1]  feat: seeder con el snapshot completo de la base local.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeploySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
CAB;

$cola = <<<'COL'
    }

    /**
     * Vuelca una tabla completa. Los nombres de columna vienen explicitos, asi
     * que agregar una columna a la base sin regenerar el seeder no lo rompe.
     *
     * @param  array<int, array<string, mixed>>  $filas
     */
    private function tabla(string $tabla, array $filas): void
    {
        foreach (array_chunk($filas, 200) as $bloque) {
            DB::table($tabla)->insert($bloque);
        }
    }
}

COL;

$seeder = $cabecera."\n"
    ."        DB::statement('TRUNCATE '.implode(', ', [\n"
    ."            {$listaTruncate},\n"
    ."        ]).' RESTART IDENTITY CASCADE');\n\n"
    .implode("\n\n", $bloques)."\n\n"
    .$cola;

$destino = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'
    .DIRECTORY_SEPARATOR.'DeploySeeder.php';

file_put_contents($destino, $seeder, LOCK_EX);

printf("Listo: %s\n", $destino);
printf("Tablas: %d | con datos: %d | filas: %d\n", count($ordenada), $tablasConDatos, $filasTotales);
