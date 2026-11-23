<?php

namespace App\Services\Curso;

use App\Enums\EstadoEstudianteExamen;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\ConfiguracionExamenDocente;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Support\DTO\CargarListaEstudiantesResultado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * @file    CargarListaEstudiantesService.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @updated 2026-11-23
 *
 * @description
 * Carga una lista de estudiantes a un curso a partir de un archivo Excel o CSV.
 * Detecta la columna del código SIS por su encabezado (o, como respaldo, por los
 * datos), crea o actualiza los estudiantes, los inscribe en el curso y crea su
 * fila de habilitación en `estudiante_examen` para los exámenes del curso. Los
 * estudiantes reportados como tramposos en la central de riesgos se inscriben
 * como «deshabilitado»; el resto, como «habilitado».
 *
 * El formato oficial del archivo es:
 * `#,Estudiante,Apellidos,Nombres,Carrera,1er PARCIAL,...,Resul. Final`, donde
 * «Estudiante» es el código SIS. Las columnas de notas se ignoran.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: carga de lista de estudiantes por Excel/CSV (HU 25).
 * - 2026-11-21  [Equipo T1]  perf: carga por lotes para reducir round-trips a la base.
 * - 2026-11-23  [Equipo T1]  fix: lectura nativa de CSV (fgetcsv + limpieza de BOM
 *   UTF-8), detección de la columna SIS por encabezado normalizado
 *   (estudiante/codigo_sis/cod_sis/sis) y escritura envuelta en transacción.
 * - 2026-11-23  [Equipo T1]  feat: tramposos de la central de riesgos quedan
 *   «deshabilitado» y modo reemplazo que vacía la lista previa del curso (HU 25).
 *
 * @see  App\Livewire\Cursos\CargarCsv
 */
class CargarListaEstudiantesService
{
    /**
     * Encabezados (ya normalizados) que identifican la columna del código SIS.
     *
     * @var list<string>
     */
    private const ENCABEZADOS_SIS = ['estudiante', 'codigosis', 'codsis', 'sis', 'codigo'];

    /**
     * Motivo con el que se deshabilita a un estudiante reportado como tramposo.
     */
    private const MOTIVO_TRAMPOSO = 'Reportado en la central de riesgos.';

    public function __construct(
        private readonly AsegurarCursoEditableService $editable,
    ) {}

    /**
     * Ejecuta la carga.
     *
     * @param  Curso  $curso  Curso destino (ya autorizado por el caller).
     * @param  UploadedFile  $archivo  Archivo .xlsx/.xls/.csv.
     * @param  ?int  $idUsuarioModificador  Docente que realiza la carga.
     * @param  bool  $reemplazar  Si vacía la lista previa del curso antes de inscribir la nueva.
     * @return CargarListaEstudiantesResultado Resumen de la operación.
     *
     * @throws InvalidArgumentException Si el archivo es inválido o no contiene estudiantes o columna SIS.
     * @throws ValidationException Si el curso está archivado.
     *
     * @author Equipo T1
     *
     * @since  2026-11-21
     */
    public function ejecutar(
        Curso $curso,
        UploadedFile $archivo,
        ?int $idUsuarioModificador = null,
        bool $reemplazar = false,
    ): CargarListaEstudiantesResultado {
        set_time_limit(300);

        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw new InvalidArgumentException('Formato de archivo no válido. Suba un archivo Excel o CSV.');
        }

        $this->editable->asegurarCursoEditable($curso, 'curso');

        [$encabezado, $filasDatos] = $this->leerArchivo($archivo, $extension);

        if ($filasDatos === []) {
            throw new InvalidArgumentException('El archivo no contiene estudiantes.');
        }

        $columnas = $this->detectarColumnas($encabezado, $filasDatos);

        $validos = $this->clasificarFilas($filasDatos, $columnas['sis']);
        $sisList = array_keys($validos['validos']);

        if ($sisList === []) {
            throw new InvalidArgumentException('El archivo no contiene estudiantes.');
        }

        return $this->persistir($curso, $validos, $sisList, $columnas, $idUsuarioModificador, $reemplazar);
    }

    /**
     * Lee el archivo y devuelve el encabezado y las filas de datos no vacías,
     * ambos indexados por número de columna (base 0). El CSV se lee de forma
     * nativa (limpieza de BOM UTF-8 y detección de separador); el Excel con
     * PhpSpreadsheet.
     *
     * @param  UploadedFile  $archivo  Archivo subido.
     * @param  string  $extension  Extensión en minúsculas (csv/xlsx/xls).
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>}
     */
    private function leerArchivo(UploadedFile $archivo, string $extension): array
    {
        $ruta = (string) $archivo->getRealPath();

        $matriz = $extension === 'csv'
            ? $this->leerCsv($ruta)
            : $this->leerExcel($ruta);

        $encabezado = [];
        foreach ($matriz[0] ?? [] as $col => $valor) {
            $encabezado[$col] = trim((string) $valor);
        }

        $filasDatos = [];
        $totalFilas = count($matriz);
        for ($fila = 1; $fila < $totalFilas; $fila++) {
            $row = [];
            $vacia = true;
            foreach ($matriz[$fila] as $col => $valor) {
                $texto = trim((string) $valor);
                $row[$col] = $texto;
                if ($texto !== '') {
                    $vacia = false;
                }
            }
            if (! $vacia) {
                $filasDatos[] = $row;
            }
        }

        return [$encabezado, $filasDatos];
    }

    /**
     * Lee un CSV de forma nativa: quita el BOM UTF-8, detecta el separador y
     * usa `fgetcsv` para respetar comillas y saltos de línea dentro de celda.
     *
     * @param  string  $ruta  Ruta del archivo en disco.
     * @return array<int, array<int, string>> Filas con sus celdas (base 0).
     */
    private function leerCsv(string $ruta): array
    {
        $contenido = (string) file_get_contents($ruta);
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? $contenido;

        $primeraLinea = strtok($contenido, "\r\n");
        $separador = $this->detectarSeparador($primeraLinea === false ? '' : $primeraLinea);

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return [];
        }

        fwrite($handle, $contenido);
        rewind($handle);

        $filas = [];
        while (($campos = fgetcsv($handle, 0, $separador)) !== false) {
            // Una línea en blanco llega como [null]; se descarta más adelante.
            $filas[] = array_map(static fn ($c): string => $c === null ? '' : (string) $c, $campos);
        }
        fclose($handle);

        return $filas;
    }

    /**
     * Lee un Excel (.xlsx/.xls) con PhpSpreadsheet y lo devuelve como matriz.
     *
     * @param  string  $ruta  Ruta del archivo en disco.
     * @return array<int, array<int, string>> Filas con sus celdas (base 0).
     */
    private function leerExcel(string $ruta): array
    {
        $hoja = IOFactory::load($ruta)->getActiveSheet();

        /** @var array<int, array<int, string>> $matriz */
        $matriz = $hoja->toArray(null, true, false, false);

        return $matriz;
    }

    /**
     * Detecta el separador de un CSV comparando las ocurrencias en el encabezado.
     *
     * @param  string  $linea  Primera línea del archivo.
     * @return string Separador detectado (coma por defecto).
     */
    private function detectarSeparador(string $linea): string
    {
        $candidatos = [',' => substr_count($linea, ','), ';' => substr_count($linea, ';'), "\t" => substr_count($linea, "\t")];
        arsort($candidatos);
        $mejor = (string) array_key_first($candidatos);

        return $candidatos[$mejor] > 0 ? $mejor : ',';
    }

    /**
     * Detecta los índices de columna de SIS, nombre, apellido y carrera.
     *
     * @param  array<int, string>  $encabezado  Encabezado indexado por columna.
     * @param  array<int, array<int, string>>  $filasDatos  Filas de datos.
     * @return array{sis: int, nombre: ?int, apellido: ?int, carrera: ?int}
     *
     * @throws InvalidArgumentException Si no se encuentra la columna del código SIS.
     */
    private function detectarColumnas(array $encabezado, array $filasDatos): array
    {
        $sisCol = null;
        $nombreCol = null;
        $apellidoCol = null;
        $carreraCol = null;

        foreach ($encabezado as $col => $valor) {
            $norm = $this->normalizar($valor);

            if ($sisCol === null && in_array($norm, self::ENCABEZADOS_SIS, true)) {
                $sisCol = $col;
            }
            if ($nombreCol === null && str_contains($norm, 'nombre') && ! str_contains($norm, 'apellido')) {
                $nombreCol = $col;
            }
            if ($apellidoCol === null && str_contains($norm, 'apellido')) {
                $apellidoCol = $col;
            }
            if ($carreraCol === null && str_contains($norm, 'carrera')) {
                $carreraCol = $col;
            }
        }

        if ($sisCol === null) {
            $sisCol = $this->detectarColumnaSisPorDatos($filasDatos);
        }

        if ($sisCol === null) {
            throw new InvalidArgumentException('No se encontró la columna de código SIS.');
        }

        return [
            'sis' => $sisCol,
            'nombre' => $nombreCol,
            'apellido' => $apellidoCol,
            'carrera' => $carreraCol,
        ];
    }

    /**
     * Respaldo: cuando ningún encabezado identifica el SIS, elige la columna con
     * más valores de 9 dígitos.
     *
     * @param  array<int, array<int, string>>  $filasDatos  Filas de datos.
     * @return ?int Índice de columna o null si ninguna tiene códigos de 9 dígitos.
     */
    private function detectarColumnaSisPorDatos(array $filasDatos): ?int
    {
        $conteos = [];
        foreach ($filasDatos as $row) {
            foreach ($row as $col => $valor) {
                if (preg_match('/^\d{9}$/', trim($valor)) === 1) {
                    $conteos[$col] = ($conteos[$col] ?? 0) + 1;
                }
            }
        }

        if ($conteos === []) {
            return null;
        }

        arsort($conteos);

        return (int) array_key_first($conteos);
    }

    /**
     * Clasifica las filas en válidas (SIS de 9 dígitos, sin duplicar), inválidas
     * y duplicadas dentro del archivo.
     *
     * @param  array<int, array<int, string>>  $filasDatos  Filas de datos.
     * @param  int  $sisCol  Índice de la columna del código SIS.
     * @return array{validos: array<string, array<int, string>>, invalidos: list<string>, duplicados: list<string>}
     */
    private function clasificarFilas(array $filasDatos, int $sisCol): array
    {
        $invalidos = [];
        $duplicados = [];
        $vistos = [];
        $validos = [];

        foreach ($filasDatos as $indice => $row) {
            $numeroFila = $indice + 2; // +1 por el encabezado, +1 por base 1.
            $sis = trim((string) ($row[$sisCol] ?? ''));

            if ($sis === '' || preg_match('/^\d{9}$/', $sis) !== 1) {
                $invalidos[] = 'Fila '.$numeroFila.': "'.$sis.'"';

                continue;
            }

            if (isset($vistos[$sis])) {
                $duplicados[] = $sis;

                continue;
            }

            $vistos[$sis] = true;
            $validos[$sis] = $row;
        }

        return [
            'validos' => $validos,
            'invalidos' => $invalidos,
            'duplicados' => array_values(array_unique($duplicados)),
        ];
    }

    /**
     * Escribe la lista en la base dentro de una transacción. En modo reemplazo,
     * primero desvincula a los estudiantes previos del curso.
     *
     * @param  Curso  $curso  Curso destino.
     * @param  array{validos: array<string, array<int, string>>, invalidos: list<string>, duplicados: list<string>}  $clasificadas  Filas clasificadas.
     * @param  list<string>  $sisList  Códigos SIS válidos únicos.
     * @param  array{sis: int, nombre: ?int, apellido: ?int, carrera: ?int}  $columnas  Índices de columnas.
     * @param  ?int  $idUsuarioModificador  Docente que realiza la carga.
     * @param  bool  $reemplazar  Si vacía la lista previa del curso.
     * @return CargarListaEstudiantesResultado Resumen de la operación.
     */
    private function persistir(
        Curso $curso,
        array $clasificadas,
        array $sisList,
        array $columnas,
        ?int $idUsuarioModificador,
        bool $reemplazar,
    ): CargarListaEstudiantesResultado {
        $validos = $clasificadas['validos'];
        $examenes = $curso->examenes()->with('configuracionesDocentes')->get();

        return DB::transaction(function () use (
            $curso,
            $validos,
            $sisList,
            $columnas,
            $idUsuarioModificador,
            $reemplazar,
            $examenes,
            $clasificadas,
        ): CargarListaEstudiantesResultado {
            if ($reemplazar) {
                EstudianteExamen::query()->where('id_curso', $curso->id_curso)->delete();
                DB::table('estudiante_curso')->where('id_curso', $curso->id_curso)->delete();
            }

            $existentesEstudiantes = array_flip(
                Estudiante::query()->whereIn('sis_estudiante', $sisList)->pluck('sis_estudiante')->all()
            );

            $existentesCurso = array_flip(
                DB::table('estudiante_curso')
                    ->where('id_curso', $curso->id_curso)
                    ->whereIn('sis_estudiante', $sisList)
                    ->pluck('sis_estudiante')
                    ->all()
            );

            $trampososSet = array_flip(
                CentralRiesgo::query()
                    ->whereIn('sis_estudiante', $sisList)
                    ->where('tipo_infraccion', TipoInfraccion::Tramposo->value)
                    ->pluck('sis_estudiante')
                    ->all()
            );

            $estudiantesNuevos = $this->insertarEstudiantes($validos, $sisList, $existentesEstudiantes, $columnas);
            [$inscripcionesNuevas, $yaInscritos] = $this->insertarInscripciones($curso, $sisList, $existentesCurso);
            $estudianteExamenesCreados = $this->insertarEstudianteExamenes(
                $curso,
                $examenes,
                $sisList,
                $trampososSet,
                $idUsuarioModificador,
            );

            $deshabilitados = count(array_intersect($sisList, array_keys($trampososSet)));

            return new CargarListaEstudiantesResultado(
                totalFilas: count($sisList),
                estudiantesNuevos: $estudiantesNuevos,
                inscripcionesNuevas: $inscripcionesNuevas,
                yaInscritos: $yaInscritos,
                tramposos: $deshabilitados,
                estudianteExamenesCreados: $estudianteExamenesCreados,
                habilitados: count($sisList) - $deshabilitados,
                deshabilitados: $deshabilitados,
                reemplazo: $reemplazar,
                sisDuplicadosEnArchivo: $clasificadas['duplicados'],
                sisInvalidos: $clasificadas['invalidos'],
            );
        });
    }

    /**
     * Inserta en lote los estudiantes que aún no existen en `estudiante`.
     *
     * @param  array<string, array<int, string>>  $validos  Filas válidas por SIS.
     * @param  list<string>  $sisList  Códigos SIS válidos únicos.
     * @param  array<string, int>  $existentes  SIS ya presentes en `estudiante`.
     * @param  array{sis: int, nombre: ?int, apellido: ?int, carrera: ?int}  $columnas  Índices de columnas.
     * @return int Cantidad de estudiantes creados.
     */
    private function insertarEstudiantes(array $validos, array $sisList, array $existentes, array $columnas): int
    {
        $nuevos = [];
        foreach ($sisList as $sis) {
            if (isset($existentes[$sis])) {
                continue;
            }

            $row = $validos[$sis];
            $nuevos[] = [
                'sis_estudiante' => $sis,
                'nombre_estudiante' => $this->valorColumna($row, $columnas['nombre']),
                'apellido_estudiante' => $this->valorColumna($row, $columnas['apellido']),
                'carrera' => $this->valorColumna($row, $columnas['carrera']),
                'verificado' => false,
            ];
        }

        if ($nuevos === []) {
            return 0;
        }

        Estudiante::query()->insertOrIgnore($nuevos);

        return count($nuevos);
    }

    /**
     * Inserta en lote las inscripciones en `estudiante_curso`.
     *
     * @param  Curso  $curso  Curso destino.
     * @param  list<string>  $sisList  Códigos SIS válidos únicos.
     * @param  array<string, int>  $existentes  SIS ya inscritos en el curso.
     * @return array{0: int, 1: int} Inscripciones nuevas y ya inscritos.
     */
    private function insertarInscripciones(Curso $curso, array $sisList, array $existentes): array
    {
        $nuevas = [];
        $yaInscritos = 0;

        foreach ($sisList as $sis) {
            if (isset($existentes[$sis])) {
                $yaInscritos++;

                continue;
            }

            $nuevas[] = [
                'sis_estudiante' => $sis,
                'id_curso' => $curso->id_curso,
            ];
        }

        if ($nuevas !== []) {
            DB::table('estudiante_curso')->insertOrIgnore($nuevas);
        }

        return [count($nuevas), $yaInscritos];
    }

    /**
     * Crea las filas de habilitación en `estudiante_examen` para cada examen del
     * curso. Un estudiante reportado como tramposo queda «deshabilitado».
     *
     * @param  Curso  $curso  Curso destino.
     * @param  Collection<int, Examen>  $examenes  Exámenes del curso con sus configuraciones.
     * @param  list<string>  $sisList  Códigos SIS válidos únicos.
     * @param  array<string, int>  $trampososSet  SIS reportados como tramposos.
     * @param  ?int  $idUsuarioModificador  Docente que realiza la carga.
     * @return int Cantidad de filas creadas en `estudiante_examen`.
     */
    private function insertarEstudianteExamenes(
        Curso $curso,
        $examenes,
        array $sisList,
        array $trampososSet,
        ?int $idUsuarioModificador,
    ): int {
        $idsExamenes = $examenes->pluck('id_examen')->all();
        $existentesExamen = [];
        if ($idsExamenes !== []) {
            $filasExistentes = EstudianteExamen::query()
                ->whereIn('id_examen', $idsExamenes)
                ->whereIn('sis_estudiante', $sisList)
                ->get(['sis_estudiante', 'id_examen', 'id_configuracion_examen']);

            foreach ($filasExistentes as $ee) {
                $existentesExamen[$ee->sis_estudiante.'|'.$ee->id_examen.'|'.$ee->id_configuracion_examen] = true;
            }
        }

        $ahora = now()->toDateTimeString();
        $filasExamen = [];
        foreach ($sisList as $sis) {
            $esTramposo = isset($trampososSet[$sis]);

            foreach ($examenes as $examen) {
                $idConfiguracion = $this->idConfiguracionDelDocente($examen, (int) $curso->sis_doc);
                if ($idConfiguracion === null) {
                    continue;
                }

                $clave = $sis.'|'.$examen->id_examen.'|'.$idConfiguracion;
                if (isset($existentesExamen[$clave])) {
                    continue;
                }

                $filasExamen[] = [
                    'sis_estudiante' => $sis,
                    'id_examen' => $examen->id_examen,
                    'id_curso' => $curso->id_curso,
                    'id_configuracion_examen' => $idConfiguracion,
                    'estado' => $esTramposo
                        ? EstadoEstudianteExamen::Deshabilitado->value
                        : EstadoEstudianteExamen::Habilitado->value,
                    'motivo' => $esTramposo ? self::MOTIVO_TRAMPOSO : null,
                    'modificado_por' => $esTramposo ? $idUsuarioModificador : null,
                    'fecha_modificacion' => $esTramposo ? $ahora : null,
                ];
            }
        }

        if ($filasExamen === []) {
            return 0;
        }

        DB::statement('LOCK TABLE estudiante_examen IN SHARE ROW EXCLUSIVE MODE');
        $primerId = (int) EstudianteExamen::query()->max('id_estudiante_examen') + 1;
        foreach ($filasExamen as $indice => $fila) {
            $fila['id_estudiante_examen'] = $primerId + $indice;
            $filasExamen[$indice] = $fila;
        }
        EstudianteExamen::query()->insert($filasExamen);

        return count($filasExamen);
    }

    /**
     * Devuelve el id de la configuración del examen para el docente del curso,
     * o null si el docente no tiene una.
     *
     * @param  Examen  $examen  Examen con sus configuraciones precargadas.
     * @param  int  $idDocente  Docente responsable del curso.
     * @return ?int Id de la configuración del examen, o null.
     */
    private function idConfiguracionDelDocente($examen, int $idDocente): ?int
    {
        foreach ($examen->configuracionesDocentes as $config) {
            if ($config instanceof ConfiguracionExamenDocente && (int) $config->id_docente === $idDocente) {
                return (int) $config->id_configuracion_examen;
            }
        }

        return null;
    }

    /**
     * Normaliza un encabezado: minúsculas, sin tildes y sin caracteres que no
     * sean letras o dígitos (ignora espacios y signos como `#`).
     *
     * @param  string  $texto  Texto del encabezado.
     * @return string Texto normalizado.
     */
    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);

        return preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    }

    /**
     * Valor de una columna opcional de una fila (cadena vacía si no existe).
     *
     * @param  array<int, string>  $row  Fila de datos.
     * @param  ?int  $col  Índice de columna o null.
     * @return string Valor saneado.
     */
    private function valorColumna(array $row, ?int $col): string
    {
        if ($col === null) {
            return '';
        }

        return trim((string) ($row[$col] ?? ''));
    }
}
