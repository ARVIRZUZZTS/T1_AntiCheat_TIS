<?php
/**
 * Exporta el esquema y los datos de Supabase (PostgreSQL) a archivos SQL.
 *
 * Genera dos artefactos en database/sql:
 *   - 000_deploy_completo.sql    : esquema completo + datos + sequences.
 *   - 005_incremental_deploy.sql : delta idempotente para produccion.
 *
 * No usa pg_dump ni cliente externo: introspecciona el catalogo de PostgreSQL
 * mediante PDO (pgsql) usando las credenciales del archivo .env.
 *
 * Uso: php tools/export-supabase-sql.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

/**
 * Carga un archivo .env simple a un arreglo asociativo.
 *
 * @param string $path Ruta al archivo .env
 * @return array<string,string>
 */
function cargarEnv(string $path): array
{
    $resultado = [];
    if (!is_file($path)) {
        return $resultado;
    }
    $lineas = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $resultado[trim($clave)] = trim($valor);
    }
    return $resultado;
}

/**
 * Ejecuta una consulta y devuelve todas las filas.
 *
 * @param PDO $pdo
 * @param string $sql
 * @param array<string,mixed> $params
 * @return array<int,array<string,mixed>>
 */
function consultar(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Escapa un identificador PostgreSQL con comillas dobles.
 */
function ident(string $nombre): string
{
    return '"' . str_replace('"', '""', $nombre) . '"';
}

/**
 * Determina si un tipo formateado es un tipo nativo de PostgreSQL.
 */
function esTipoNativo(string $tipo): bool
{
    $base = trim(preg_replace('/\([^)]*\)/', '', $tipo));
    $nativos = [
        'smallint', 'integer', 'bigint', 'numeric', 'real', 'double precision', 'money',
        'boolean', 'text', 'character varying', 'character', 'char', 'varchar',
        'date', 'time without time zone', 'time with time zone',
        'timestamp without time zone', 'timestamp with time zone',
        'interval', 'uuid', 'json', 'jsonb', 'bytea', 'xml', 'inet', 'cidr', 'macaddr',
    ];
    return in_array($base, $nativos, true);
}

/**
 * Formatea un valor PHP (string proveniente de PDO) para SQL.
 *
 * @param mixed $valor
 * @param array<string,mixed> $columna
 * @return string
 */
function formatearValor($valor, array $columna): string
{
    $tipo = (string) $columna['format_type'];
    if ($valor === null) {
        return 'NULL';
    }
    if ($tipo === 'boolean') {
        $v = strtolower((string) $valor);
        return in_array($v, ['t', 'true', '1'], true) ? 'TRUE' : 'FALSE';
    }
    $tiposNumericos = ['smallint', 'integer', 'bigint', 'numeric', 'real', 'double precision', 'money'];
    $base = trim(preg_replace('/\([^)]*\)/', '', $tipo));
    if (in_array($base, $tiposNumericos, true) && is_numeric($valor)) {
        return (string) $valor;
    }
    // Arrays
    if (str_ends_with($tipo, '[]')) {
        $escapado = str_replace("'", "''", (string) $valor);
        return "'" . $escapado . "'::" . $tipo;
    }
    // Enums / tipos definidos por el usuario
    if (!esTipoNativo($tipo)) {
        $escapado = str_replace("'", "''", (string) $valor);
        return "'" . $escapado . "'::" . $tipo;
    }
    // JSON
    if (in_array($base, ['json', 'jsonb'], true)) {
        $escapado = str_replace("'", "''", (string) $valor);
        return "'" . $escapado . "'::" . $base;
    }
    // Default: texto entre comillas
    $escapado = str_replace("'", "''", (string) $valor);
    return "'" . $escapado . "'";
}

/**
 * Obtiene las columnas de una tabla con su tipo formateado.
 *
 * @return array<int,array<string,mixed>>
 */
function obtenerColumnas(PDO $pdo, string $tabla): array
{
    $stmt = $pdo->prepare(
        "SELECT a.attname AS column_name,
                pg_catalog.format_type(a.atttypid, a.atttypmod) AS format_type,
                ic.is_nullable,
                ic.column_default
         FROM pg_attribute a
         JOIN pg_class c ON c.oid = a.attrelid
         JOIN pg_namespace n ON n.oid = c.relnamespace
         LEFT JOIN information_schema.columns ic
                ON ic.table_schema = 'public'
               AND ic.table_name = c.relname
               AND ic.column_name = a.attname
         WHERE n.nspname = 'public'
           AND c.relname = :tabla
           AND a.attnum > 0
           AND NOT a.attisdropped
         ORDER BY a.attnum"
    );
    $stmt->execute([':tabla' => $tabla]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnas = [];
    foreach ($filas as $f) {
        $columnas[] = [
            'name' => $f['column_name'],
            'format_type' => $f['format_type'],
            'is_nullable' => $f['is_nullable'],
            'column_default' => $f['column_default'],
        ];
    }
    return $columnas;
}

/**
 * Obtiene las constraints de una tabla.
 *
 * @return array<int,array<string,mixed>>
 */
function obtenerConstraints(PDO $pdo, string $tabla): array
{
    $stmt = $pdo->prepare(
        "SELECT con.conname AS name, con.contype AS type, pg_get_constraintdef(con.oid) AS def
         FROM pg_constraint con
         JOIN pg_class c ON c.oid = con.conrelid
         JOIN pg_namespace n ON n.oid = c.relnamespace
         WHERE n.nspname = 'public' AND c.relname = :tabla
         ORDER BY con.contype, con.conname"
    );
    $stmt->execute([':tabla' => $tabla]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Genera el CREATE TABLE para una tabla.
 *
 * @param array<int,array<string,mixed>> $columnas
 * @return string
 */
function generarCreateTable(string $tabla, array $columnas): string
{
    $lineas = [];
    foreach ($columnas as $col) {
        $def = '    ' . ident($col['name']) . ' ' . $col['format_type'];
        if ($col['column_default'] !== null && $col['column_default'] !== '') {
            $def .= ' DEFAULT ' . $col['column_default'];
        }
        if ($col['is_nullable'] === 'NO') {
            $def .= ' NOT NULL';
        }
        $lineas[] = $def;
    }
    $cuerpo = implode(",\n", $lineas);
    return "CREATE TABLE IF NOT EXISTS " . ident($tabla) . " (\n" . $cuerpo . "\n);";
}

/**
 * Verifica si una tabla tiene primary key.
 *
 * @param array<int,array<string,mixed>> $constraints
 */
function tienePrimaryKey(array $constraints): bool
{
    foreach ($constraints as $c) {
        if ($c['type'] === 'p') {
            return true;
        }
    }
    return false;
}

/**
 * Exporta los datos de una tabla como sentencias INSERT.
 *
 * @param array<int,array<string,mixed>> $columnas
 * @param array<int,array<string,mixed>> $constraints
 */
function exportarDatos(PDO $pdo, string $tabla, array $columnas, bool $idempotente): string
{
    $nombresColumnas = array_column($columnas, 'name');
    $mapaColumnas = [];
    foreach ($columnas as $c) {
        $mapaColumnas[$c['name']] = $c;
    }
    $stmt = $pdo->query('SELECT * FROM ' . ident($tabla));
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($filas) === 0) {
        return '';
    }
    $tienePk = false;
    $constraints = obtenerConstraints($pdo, $tabla);
    $tienePk = tienePrimaryKey($constraints);

    $salida = [];
    $chunks = array_chunk($filas, 50);
    foreach ($chunks as $chunk) {
        $tuplas = [];
        foreach ($chunk as $fila) {
            $valores = [];
            foreach ($nombresColumnas as $cn) {
                $valores[] = formatearValor($fila[$cn], $mapaColumnas[$cn]);
            }
            $tuplas[] = '(' . implode(',', $valores) . ')';
        }
        $sql = 'INSERT INTO ' . ident($tabla) . ' (' . implode(',', array_map('ident', $nombresColumnas)) . ') VALUES ' . implode(',', $tuplas);
        if ($idempotente && $tienePk) {
            $sql .= ' ON CONFLICT DO NOTHING';
        }
        $sql .= ';';
        $salida[] = $sql;
    }
    return implode("\n", $salida) . "\n";
}

// ---------- Conexion ----------

$env = cargarEnv($root . '/.env');
$host = $env['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$port = $env['DB_PORT'] ?? getenv('DB_PORT') ?: '5432';
$dbname = $env['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'postgres';
$user = $env['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'postgres';
$pass = $env['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

$pdo = new PDO(
    "pgsql:host=$host;port=$port;dbname=$dbname",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ---------- Introspeccion ----------

// Enums
$enumRows = consultar($pdo, "SELECT t.typname FROM pg_type t JOIN pg_namespace n ON n.oid = t.typnamespace WHERE t.typtype = 'e' AND n.nspname = 'public' ORDER BY t.typname");
$enums = [];
foreach ($enumRows as $r) {
    $nombre = $r['typname'];
    $labels = consultar($pdo, "SELECT e.enumlabel FROM pg_enum e JOIN pg_type t ON t.oid = e.enumtypid WHERE t.typname = :n ORDER BY e.enumsortorder", [':n' => $nombre]);
    $enums[$nombre] = array_column($labels, 'enumlabel');
}

// Sequences
$seqRows = consultar($pdo, "SELECT sequencename FROM pg_sequences WHERE schemaname = 'public' ORDER BY sequencename");
$sequences = array_column($seqRows, 'sequencename');

// Tables
$tableRows = consultar($pdo, "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
$allTables = array_column($tableRows, 'tablename');

// FK dependencies
$deps = [];
foreach ($allTables as $t) {
    $rows = consultar(
        $pdo,
        "SELECT cf.relname AS ref_table
         FROM pg_constraint con
         JOIN pg_class c ON c.oid = con.conrelid
         JOIN pg_class cf ON cf.oid = con.confrelid
         JOIN pg_namespace n ON n.oid = c.relnamespace
         WHERE n.nspname = 'public' AND c.relname = :t AND con.contype = 'f'",
        [':t' => $t]
    );
    $deps[$t] = array_values(array_unique(array_column($rows, 'ref_table')));
}

// Topological sort
function ordenarTopologicamente(array $tablas, array $deps): array
{
    $grado = [];
    $ady = [];
    foreach ($tablas as $t) {
        $grado[$t] = 0;
        $ady[$t] = [];
    }
    foreach ($tablas as $t) {
        foreach ($deps[$t] as $ref) {
            if (!isset($grado[$ref])) {
                continue;
            }
            $ady[$ref][] = $t;
            $grado[$t]++;
        }
    }
    // dedupe edges
    foreach ($ady as $k => $v) {
        $ady[$k] = array_values(array_unique($v));
    }
    // recompute grados with unique
    foreach ($tablas as $t) {
        $grado[$t] = 0;
    }
    foreach ($tablas as $t) {
        foreach ($deps[$t] as $ref) {
            if (!isset($grado[$ref])) {
                continue;
            }
        }
    }
    // simpler: recompute
    $grado = [];
    foreach ($tablas as $t) {
        $grado[$t] = 0;
    }
    foreach ($tablas as $t) {
        foreach (array_unique($deps[$t]) as $ref) {
            if (isset($grado[$ref])) {
                $grado[$t]++;
            }
        }
    }
    $cola = [];
    foreach ($tablas as $t) {
        if ($grado[$t] === 0) {
            $cola[] = $t;
        }
    }
    $ordenado = [];
    while (count($cola) > 0) {
        $n = array_shift($cola);
        $ordenado[] = $n;
        foreach ($ady[$n] as $m) {
            if (--$grado[$m] === 0) {
                $cola[] = $m;
            }
        }
    }
    foreach ($tablas as $t) {
        if (!in_array($t, $ordenado, true)) {
            $ordenado[] = $t;
        }
    }
    return $ordenado;
}

$tablasOrdenadas = ordenarTopologicamente($allTables, $deps);

// Sequence -> column map
$mapaSeqColumna = [];
foreach ($allTables as $t) {
    foreach (obtenerColumnas($pdo, $t) as $col) {
        $def = $col['column_default'] ?? '';
        if ($def && preg_match("/nextval\('([^']+)'/", $def, $m)) {
            $seqCompleta = $m[1];
            $seqBase = $seqCompleta;
            if (str_contains($seqBase, '.')) {
                $partes = explode('.', $seqBase);
                $seqBase = end($partes);
            }
            $seqBase = trim($seqBase, '"');
            $mapaSeqColumna[$seqBase] = [$t, $col['name']];
        }
    }
}

// ---------- Generar completo ----------

$salida = [];
$salida[] = '-- ============================================================================';
$salida[] = '--  DEPLOY COMPLETO - T1 AntiCheat TIS';
$salida[] = '--  Generado automaticamente por tools/export-supabase-sql.php';
$salida[] = '--  Fuente: Supabase (schema public)';
$salida[] = '-- ============================================================================';
$salida[] = '';
$salida[] = 'BEGIN;';
$salida[] = '';

// Enums
$salida[] = '-- Enums';
foreach ($enums as $nombre => $labels) {
    $labelSql = implode(', ', array_map(static fn (string $l): string => "'" . str_replace("'", "''", $l) . "'", $labels));
    $salida[] = "CREATE TYPE $nombre AS ENUM ($labelSql);";
}
$salida[] = '';

// Sequences
$salida[] = '-- Sequences';
foreach ($sequences as $seq) {
    $salida[] = 'CREATE SEQUENCE IF NOT EXISTS ' . ident($seq) . ';';
}
$salida[] = '';

// Tables
$salida[] = '-- Tables';
foreach ($tablasOrdenadas as $t) {
    $cols = obtenerColumnas($pdo, $t);
    $salida[] = generarCreateTable($t, $cols);
    $salida[] = '';
}

// Constraints
$salida[] = '-- Constraints';
foreach ($tablasOrdenadas as $t) {
    foreach (obtenerConstraints($pdo, $t) as $c) {
        if (in_array($c['type'], ['p', 'u', 'f', 'c'], true)) {
            $salida[] = 'ALTER TABLE ' . ident($t) . ' ADD CONSTRAINT ' . ident($c['name']) . ' ' . $c['def'] . ';';
        }
    }
}
$salida[] = '';

// Data
$salida[] = '-- Data';
foreach ($tablasOrdenadas as $t) {
    $cols = obtenerColumnas($pdo, $t);
    $data = exportarDatos($pdo, $t, $cols, false);
    if ($data !== '') {
        $salida[] = '-- ' . $t;
        $salida[] = rtrim($data);
        $salida[] = '';
    }
}

// Sequences setval
$salida[] = '-- Sequences setval';
foreach ($sequences as $seq) {
    if (isset($mapaSeqColumna[$seq])) {
        [$t, $col] = $mapaSeqColumna[$seq];
        $salida[] = "SELECT setval('" . str_replace("'", "''", $seq) . "'::regclass, COALESCE((SELECT MAX(" . ident($col) . ") FROM " . ident($t) . "), 1), EXISTS (SELECT 1 FROM " . ident($t) . "));";
    } else {
        $stmt = $pdo->query('SELECT last_value, is_called FROM ' . ident($seq));
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        $lv = (int) $info['last_value'];
        $called = filter_var($info['is_called'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        $salida[] = "SELECT setval('" . str_replace("'", "''", $seq) . "'::regclass, $lv, $called);";
    }
}
$salida[] = '';
$salida[] = 'COMMIT;';
$salida[] = '';

file_put_contents($root . '/database/sql/000_deploy_completo.sql', implode("\n", $salida));

// ---------- Generar incremental ----------

$tablasFaltantes = [
    'auxiliar_examen', 'central_riesgo_motivo', 'configuracion_examen_docente',
    'configuracion_examen_material', 'configuracion_examen_norma',
    'configuracion_material_personalizado', 'configuracion_norma_personalizada',
    'material_personalizado', 'norma_personalizada', 'validacion_registro',
];

$inc = [];
$inc[] = '-- ============================================================================';
$inc[] = '--  INCREMENTAL DEPLOY - T1 AntiCheat TIS';
$inc[] = '--  Delta idempotente para bases ya desplegadas (produccion).';
$inc[] = '--  Generado automaticamente por tools/export-supabase-sql.php';
$inc[] = '-- ============================================================================';
$inc[] = '';
$inc[] = 'BEGIN;';
$inc[] = '';

// motivo enum
$labelsMotivo = $enums['motivo'] ?? [];
$labelSql = implode(', ', array_map(static fn (string $l): string => "'" . str_replace("'", "''", $l) . "'", $labelsMotivo));
$inc[] = "DO \$\$ BEGIN CREATE TYPE motivo AS ENUM ($labelSql); EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;";
$inc[] = '';

// Sequences needed by missing tables
$seqsFaltantes = [];
foreach ($tablasFaltantes as $t) {
    foreach (obtenerColumnas($pdo, $t) as $col) {
        $def = $col['column_default'] ?? '';
        if ($def && preg_match("/nextval\('([^']+)'/", $def, $m)) {
            $seqCompleta = $m[1];
            $seqBase = $seqCompleta;
            if (str_contains($seqBase, '.')) {
                $partes = explode('.', $seqBase);
                $seqBase = end($partes);
            }
            $seqBase = trim($seqBase, '"');
            $seqsFaltantes[$seqBase] = true;
        }
    }
}
foreach (array_keys($seqsFaltantes) as $seq) {
    $inc[] = 'CREATE SEQUENCE IF NOT EXISTS ' . ident($seq) . ';';
}
if (count($seqsFaltantes) > 0) {
    $inc[] = '';
}

// Missing tables
foreach ($tablasFaltantes as $t) {
    $cols = obtenerColumnas($pdo, $t);
    $inc[] = generarCreateTable($t, $cols);
    foreach (obtenerConstraints($pdo, $t) as $c) {
        if (in_array($c['type'], ['p', 'u', 'f', 'c'], true)) {
            $inc[] = "DO \$\$ BEGIN ALTER TABLE " . ident($t) . " ADD CONSTRAINT " . ident($c['name']) . ' ' . $c['def'] . "; EXCEPTION WHEN OTHERS THEN NULL; END \$\$;";
        }
    }
    $data = exportarDatos($pdo, $t, $cols, true);
    if ($data !== '') {
        $inc[] = rtrim($data);
    }
    $inc[] = '';
    // setval for its sequences
    foreach ($mapaSeqColumna as $seq => [$tt, $col]) {
        if ($tt === $t) {
            $inc[] = "SELECT setval('" . str_replace("'", "''", $seq) . "'::regclass, COALESCE((SELECT MAX(" . ident($col) . ") FROM " . ident($t) . "), 1), EXISTS (SELECT 1 FROM " . ident($t) . "));";
        }
    }
}

// fecha_evento column
$inc[] = "DO \$\$ BEGIN ALTER TABLE notificacion_docente ADD COLUMN fecha_evento timestamp(0) without time zone; EXCEPTION WHEN duplicate_column THEN NULL; END \$\$;";
$inc[] = '';

// Cleanup obsolete estado_incidencia (migracion 2026_10_26, idempotente)
$inc[] = "DO \$\$ BEGIN";
$inc[] = "  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='central_riesgo' AND column_name='estado_incidencia') THEN";
$inc[] = "    UPDATE central_riesgo SET tipo_infraccion = 'tramposo' WHERE tipo_infraccion = 'sospechoso' AND estado_incidencia = 'Confirmado';";
$inc[] = "  END IF;";
$inc[] = "END \$\$;";
$inc[] = "ALTER TABLE central_riesgo DROP CONSTRAINT IF EXISTS fk_cr_confirmador;";
$inc[] = "ALTER TABLE central_riesgo DROP COLUMN IF EXISTS id_confirmador;";
$inc[] = "ALTER TABLE central_riesgo DROP COLUMN IF EXISTS estado_incidencia;";
$inc[] = '';
$inc[] = 'DROP TYPE IF EXISTS estado_incidencia;';
$inc[] = '';
$inc[] = 'COMMIT;';
$inc[] = '';

file_put_contents($root . '/database/sql/005_incremental_deploy.sql', implode("\n", $inc));

echo "Generados: database/sql/000_deploy_completo.sql y database/sql/005_incremental_deploy.sql\n";
echo "Tablas: " . count($allTables) . ", Enums: " . count($enums) . ", Sequences: " . count($sequences) . "\n";
