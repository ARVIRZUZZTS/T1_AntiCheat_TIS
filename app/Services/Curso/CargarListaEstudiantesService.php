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
 * @updated 2026-11-21
 *
 * @description
 * Carga una lista de estudiantes a un curso a partir de un archivo Excel o CSV.
 * Detecta automáticamente la columna del código SIS, crea los estudiantes que
 * no existan, los inscribe en el curso y crea su fila de habilitación en
 * `estudiante_examen` para todos los exámenes vinculados al curso.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: carga de lista de estudiantes por Excel/CSV (HU 25).
 * - 2026-11-21  [Equipo T1]  perf: carga por lotes para reducir round-trips a la base.
 *
 * @see  App\Livewire\Cursos\CargarCsv
 */
class CargarListaEstudiantesService
{
    public function __construct(
        private readonly AsegurarCursoEditableService $editable,
    ) {
    }

    /**
     * Ejecuta la carga.
     *
     * @param  Curso  $curso  Curso destino (ya autorizado por el caller).
     * @param  UploadedFile  $archivo  Archivo .xlsx/.xls/.csv.
     * @param  ?int  $idUsuarioModificador  Docente que realiza la carga.
     * @return CargarListaEstudiantesResultado  Resumen de la operación.
     *
     * @throws InvalidArgumentException Si el archivo es inválido o no contiene estudiantes o columna SIS.
     * @throws ValidationException Si el curso está archivado.
     */
    public function ejecutar(
        Curso $curso,
        UploadedFile $archivo,
        ?int $idUsuarioModificador = null,
    ): CargarListaEstudiantesResultado {
        set_time_limit(300);

        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw new InvalidArgumentException('Formato de archivo no válido. Suba un archivo Excel o CSV.');
        }

        $this->editable->asegurarCursoEditable($curso, 'curso');

        $hoja = IOFactory::load($archivo->getRealPath())->getActiveSheet();
        $ultimaColumna = $hoja->getHighestColumn();
        $ultimaFila = (int) $hoja->getHighestRow();

        // Encabezado.
        $encabezado = [];
        foreach (range('A', $ultimaColumna) as $col) {
            $encabezado[$col] = trim((string) $hoja->getCell($col.'1')->getValue());
        }

        $columnas = $this->detectarColumnas($encabezado, $hoja, $ultimaFila, $ultimaColumna);
        $sisCol = $columnas['sis'];
        $nombreCol = $columnas['nombre'];
        $apellidoCol = $columnas['apellido'];
        $carreraCol = $columnas['carrera'];

        // Filas de datos.
        $filasDatos = [];
        for ($fila = 2; $fila <= $ultimaFila; $fila++) {
            $row = [];
            $vacia = true;
            foreach (range('A', $ultimaColumna) as $col) {
                $valor = $hoja->getCell($col.$fila)->getValue();
                $row[$col] = $valor;
                if ($valor !== null && trim((string) $valor) !== '') {
                    $vacia = false;
                }
            }
            if (! $vacia) {
                $filasDatos[$fila] = $row;
            }
        }

        if ($filasDatos === []) {
            throw new InvalidArgumentException('El archivo no contiene estudiantes.');
        }

        $examenes = $curso->examenes()->with('configuracionesDocentes')->get();

        // Primera pasada: clasificar SIS (válido, inválido, duplicado en archivo).
        $sisInvalidos = [];
        $sisDuplicadosEnArchivo = [];
        $vistos = [];
        $validos = []; // sis => row

        foreach ($filasDatos as $numeroFila => $row) {
            $sis = trim((string) ($row[$sisCol] ?? ''));

            if ($sis === '' || ! preg_match('/^\d{9}$/', $sis)) {
                $sisInvalidos[] = 'Fila '.$numeroFila.': "'.$sis.'"';
                continue;
            }

            if (isset($vistos[$sis])) {
                $sisDuplicadosEnArchivo[] = $sis;
                continue;
            }

            $vistos[$sis] = true;
            $validos[$sis] = $row;
        }

        $sisList = array_keys($validos);
        $totalFilas = count($sisList);

        $estudiantesNuevos = 0;
        $inscripcionesNuevas = 0;
        $yaInscritos = 0;
        $tramposos = 0;
        $estudianteExamenesCreados = 0;

        if ($sisList === []) {
            return new CargarListaEstudiantesResultado(
                totalFilas: 0,
                estudiantesNuevos: 0,
                inscripcionesNuevas: 0,
                yaInscritos: 0,
                tramposos: 0,
                estudianteExamenesCreados: 0,
                sisDuplicadosEnArchivo: array_values(array_unique($sisDuplicadosEnArchivo)),
                sisInvalidos: $sisInvalidos,
            );
        }

        // Carga en memoria de lo existente (un solo round-trip por tabla).
        $existentesEstudiantes = Estudiante::query()
            ->whereIn('sis_estudiante', $sisList)
            ->pluck('sis_estudiante')
            ->all();
        $existentesEstudiantesSet = array_flip($existentesEstudiantes);

        $existentesCurso = DB::table('estudiante_curso')
            ->where('id_curso', $curso->id_curso)
            ->whereIn('sis_estudiante', $sisList)
            ->pluck('sis_estudiante')
            ->all();
        $existentesCursoSet = array_flip($existentesCurso);

        $trampososSet = array_flip(
            CentralRiesgo::query()
                ->whereIn('sis_estudiante', $sisList)
                ->where('tipo_infraccion', TipoInfraccion::Tramposo->value)
                ->pluck('sis_estudiante')
                ->all()
        );

        // Existentes en estudiante_examen para los exámenes del curso.
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

        // Estudiantes nuevos: batch insert.
        $nuevosEstudiantes = [];
        foreach ($sisList as $sis) {
            if (! isset($existentesEstudiantesSet[$sis])) {
                $row = $validos[$sis];
                $nuevosEstudiantes[] = [
                    'sis_estudiante' => $sis,
                    'nombre_estudiante' => $this->valorColumna($row, $nombreCol),
                    'apellido_estudiante' => $this->valorColumna($row, $apellidoCol),
                    'carrera' => $this->valorColumna($row, $carreraCol),
                    'verificado' => false,
                ];
            }
        }

        if ($nuevosEstudiantes !== []) {
            Estudiante::query()->insertOrIgnore($nuevosEstudiantes);
            $estudiantesNuevos = count($nuevosEstudiantes);
        }

        // Inscripciones nuevas en estudiante_curso: batch.
        $nuevasInscripciones = [];
        foreach ($sisList as $sis) {
            if (isset($existentesCursoSet[$sis])) {
                $yaInscritos++;
                continue;
            }

            $nuevasInscripciones[] = [
                'sis_estudiante' => $sis,
                'id_curso' => $curso->id_curso,
            ];
        }

        if ($nuevasInscripciones !== []) {
            DB::table('estudiante_curso')->insertOrIgnore($nuevasInscripciones);
            $inscripcionesNuevas = count($nuevasInscripciones);
        }

        // Tramposos (global) para todos los válidos.
        foreach ($sisList as $sis) {
            if (isset($trampososSet[$sis])) {
                $tramposos++;
            }
        }

        // Acumular filas de estudiante_examen para todos los exámenes.
        $filasExamen = [];
        foreach ($sisList as $sis) {
            foreach ($examenes as $examen) {
                $idConfiguracion = null;
                foreach ($examen->configuracionesDocentes as $config) {
                    if ($config instanceof ConfiguracionExamenDocente && (int) $config->id_docente === (int) $curso->sis_doc) {
                        $idConfiguracion = $config->id_configuracion_examen;
                        break;
                    }
                }

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
                    'estado' => EstadoEstudianteExamen::Habilitado->value,
                ];
                $estudianteExamenesCreados++;
            }
        }

        if ($filasExamen !== []) {
            DB::statement('LOCK TABLE estudiante_examen IN SHARE ROW EXCLUSIVE MODE');
            $primerId = (int) EstudianteExamen::query()->max('id_estudiante_examen') + 1;
            foreach ($filasExamen as $indice => $fila) {
                $fila['id_estudiante_examen'] = $primerId + $indice;
                $filasExamen[$indice] = $fila;
            }
            EstudianteExamen::query()->insert($filasExamen);
        }

        return new CargarListaEstudiantesResultado(
            totalFilas: $totalFilas,
            estudiantesNuevos: $estudiantesNuevos,
            inscripcionesNuevas: $inscripcionesNuevas,
            yaInscritos: $yaInscritos,
            tramposos: $tramposos,
            estudianteExamenesCreados: $estudianteExamenesCreados,
            sisDuplicadosEnArchivo: array_values(array_unique($sisDuplicadosEnArchivo)),
            sisInvalidos: $sisInvalidos,
        );
    }

    /**
     * Detecta las columnas de SIS, nombre, apellido y carrera.
     *
     * @return array{sis: string, nombre: ?string, apellido: ?string, carrera: ?string}
     */
    private function detectarColumnas(array $encabezado, $hoja, int $ultimaFila, string $ultimaColumna): array
    {
        $sisCol = null;
        $nombreCol = null;
        $apellidoCol = null;
        $carreraCol = null;

        foreach ($encabezado as $col => $valor) {
            $norm = mb_strtolower(trim((string) $valor));
            if ($sisCol === null && (str_contains($norm, 'sis') || $norm === 'codigo' || $norm === 'codigo_sis')) {
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

        // Fallback por datos: columna con más valores de 9 dígitos.
        if ($sisCol === null) {
            $conteos = [];
            foreach (range('A', $ultimaColumna) as $col) {
                $conteos[$col] = 0;
                for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                    $v = trim((string) $hoja->getCell($col.$fila)->getValue());
                    if (preg_match('/^\d{9}$/', $v) === 1) {
                        $conteos[$col]++;
                    }
                }
            }
            $maximo = max($conteos);
            if ($maximo > 0) {
                $sisCol = (string) array_search($maximo, $conteos, true);
            }
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

    private function valorColumna(array $row, ?string $col): string
    {
        if ($col === null) {
            return '';
        }

        return trim((string) ($row[$col] ?? ''));
    }
}
