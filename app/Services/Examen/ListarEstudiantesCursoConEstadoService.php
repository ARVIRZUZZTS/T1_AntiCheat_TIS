<?php

/**
 * @file    ListarEstudiantesCursoConEstadoService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-25
 *
 * @description
 * Servicio de la feature Examen que lista los estudiantes de un curso con el
 * estado de habilitación en el examen actual (habilitado/deshabilitado) y su
 * observación de la central de riesgos (sospechoso/tramposo/pendiente/aula
 * equivocada). Soporta filtro por estado, búsqueda por nombre o código SIS y
 * paginación en la base. Es de solo lectura: nunca modifica estados.
 *
 * @see  App\Http\Controllers\Api\EstudianteExamenController
 * @see  App\Livewire\Examenes\EstudiantesCurso
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del servicio.
 * - 2026-09-25  [T1]  fix: permitir espacios en validarBusqueda para poder
 *   buscar por nombre y apellido juntos.
 */

namespace App\Services\Examen;

use App\Enums\EstadoEstudianteExamen;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class ListarEstudiantesCursoConEstadoService
{
    public const FILTRO_TODOS = 'todos';

    public const FILTRO_HABILITADOS = 'habilitados';

    public const FILTRO_DESHABILITADOS = 'deshabilitados';

    public const FILTRO_SOSPECHOSOS = 'sospechosos';

    public const FILTRO_TRAMPOSOS = 'tramposos';

    public const FILTROS_VALIDOS = [
        self::FILTRO_TODOS,
        self::FILTRO_HABILITADOS,
        self::FILTRO_DESHABILITADOS,
        self::FILTRO_SOSPECHOSOS,
        self::FILTRO_TRAMPOSOS,
    ];

    private const POR_PAGINA_DEFAULT = 10;

    /**
     * Lista paginada de estudiantes del curso con su estado en el examen actual.
     *
     * @param  int  $idCurso  ID del curso.
     * @param  ?string  $filtroEstado  Filtro por estado (ver FILTROS_VALIDOS).
     * @param  ?string  $busqueda  Texto de búsqueda por nombre/código SIS.
     * @param  int  $pagina  Número de página.
     * @param  int  $porPagina  Cantidad de resultados por página.
     * @return LengthAwarePaginator<int, array<string, mixed>> Colección paginada de estudiantes enriquecidos.
     *
     * @throws InvalidArgumentException Si el filtro es inválido o la búsqueda tiene caracteres no permitidos.
     */
    public function ejecutar(
        int $idCurso,
        ?string $filtroEstado,
        ?string $busqueda,
        int $pagina = 1,
        int $porPagina = self::POR_PAGINA_DEFAULT,
    ): LengthAwarePaginator {
        $this->validarBusqueda($busqueda);
        $filtro = $this->normalizarFiltro($filtroEstado);

        $curso = Curso::findOrFail($idCurso);
        $examen = $curso->examenActual();

        $query = $curso->estudiantes()
            ->with(['estudianteExamenes', 'registrosAsistencia.centralRiesgos']);

        if ($busqueda !== null && $busqueda !== '') {
            $query->where(function (Builder $q) use ($busqueda) {
                $q->where('estudiante.nombre_estudiante', 'ilike', "%{$busqueda}%")
                    ->orWhere('estudiante.apellido_estudiante', 'ilike', "%{$busqueda}%")
                    ->orWhere('estudiante.sis_estudiante', 'ilike', "%{$busqueda}%")
                    // nombre y apellido concatenados, para buscar "Nombre Apellido" junto
                    ->orWhereRaw(
                        "estudiante.nombre_estudiante || ' ' || estudiante.apellido_estudiante ilike ?",
                        ["%{$busqueda}%"]
                    );
            });
        }

        $idExamen = $examen?->id_examen;
        $this->aplicarFiltro($query, $filtro, $idExamen);

        $paginador = $query->paginate($porPagina, ['*'], 'page', $pagina);

        $datos = collect($paginador->items())
            ->map(fn (Estudiante $estudiante): array => $this->mapearEstudiante($estudiante, $idExamen));

        return new LengthAwarePaginator(
            $datos,
            $paginador->total(),
            $paginador->perPage(),
            $paginador->currentPage(),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Conteos de estudiantes por estado dentro del curso (para los botones de filtro).
     *
     * @param  int  $idCurso  ID del curso.
     * @return array<string, int> Conteos por clave de filtro.
     */
    public function conteosPorEstado(int $idCurso): array
    {
        $curso = Curso::findOrFail($idCurso);
        $examen = $curso->examenActual();
        $idExamen = $examen?->id_examen;

        return [
            self::FILTRO_TODOS => (int) $curso->estudiantes()->count(),
            self::FILTRO_HABILITADOS => $this->contarHabilitacion($curso, $idExamen, EstadoEstudianteExamen::Habilitado->value),
            self::FILTRO_DESHABILITADOS => $this->contarHabilitacion($curso, $idExamen, EstadoEstudianteExamen::Deshabilitado->value),
            self::FILTRO_SOSPECHOSOS => $this->contarInfraccion($curso, $idExamen, TipoInfraccion::Sospechoso->value),
            self::FILTRO_TRAMPOSOS => $this->contarInfraccion($curso, $idExamen, TipoInfraccion::Tramposo->value),
        ];
    }

    /**
     * Valida que la búsqueda solo contenga letras (castellano), dígitos o
     * espacios (para permitir buscar por nombre y apellido juntos).
     *
     * @param  ?string  $busqueda  Texto a validar.
     *
     * @throws InvalidArgumentException Si contiene caracteres no permitidos.
     */
    public function validarBusqueda(?string $busqueda): void
    {
        if ($busqueda === null || $busqueda === '') {
            return;
        }

        if (preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 ]+$/u', $busqueda) !== 1) {
            throw new InvalidArgumentException('Caracter no permitido (A-z, 0-9, espacio)');
        }
    }

    /**
     * Normaliza el filtro de estado recibido.
     *
     * @param  ?string  $filtro  Filtro recibido (puede ser null o "todos").
     * @return string Clave de filtro válida.
     *
     * @throws InvalidArgumentException Si el filtro no está en la lista permitida.
     */
    private function normalizarFiltro(?string $filtro): string
    {
        $filtro = $filtro ?? self::FILTRO_TODOS;

        if (! in_array($filtro, self::FILTROS_VALIDOS, true)) {
            throw new InvalidArgumentException('Estado de filtro no válido');
        }

        return $filtro;
    }

    /**
     * Aplica el filtro por estado a la consulta de estudiantes.
     *
     * @param  BelongsToMany  $query  Consulta de estudiantes del curso.
     * @param  string  $filtro  Clave de filtro (habilitados/deshabilitados/sospechosos/tramposos).
     * @param  ?int  $idExamen  ID del examen actual del curso.
     */
    private function aplicarFiltro(BelongsToMany $query, string $filtro, ?int $idExamen): void
    {
        if ($filtro === self::FILTRO_HABILITADOS || $filtro === self::FILTRO_DESHABILITADOS) {
            $estado = $filtro === self::FILTRO_HABILITADOS
                ? EstadoEstudianteExamen::Habilitado->value
                : EstadoEstudianteExamen::Deshabilitado->value;

            $query->whereHas('estudianteExamenes', function (Builder $q) use ($idExamen, $estado) {
                $q->where('id_examen', $idExamen)->where('estado', $estado);
            });

            return;
        }

        if ($filtro === self::FILTRO_SOSPECHOSOS || $filtro === self::FILTRO_TRAMPOSOS) {
            $infraccion = $filtro === self::FILTRO_SOSPECHOSOS
                ? TipoInfraccion::Sospechoso->value
                : TipoInfraccion::Tramposo->value;

            $query->whereHas('registrosAsistencia', function (Builder $q) use ($idExamen, $infraccion) {
                $q->where('id_examen', $idExamen)
                    ->whereHas('centralRiesgos', fn (Builder $q2) => $q2->where('tipo_infraccion', $infraccion));
            });
        }
    }

    /**
     * Cuenta estudiantes del curso con determinada habilitación en el examen.
     *
     * @param  Curso  $curso  Curso consultado.
     * @param  ?int  $idExamen  ID del examen actual.
     * @param  string  $estado  Valor de estado (habilitado/deshabilitado).
     * @return int Cantidad de estudiantes.
     */
    private function contarHabilitacion(Curso $curso, ?int $idExamen, string $estado): int
    {
        return (int) $curso->estudiantes()
            ->whereHas('estudianteExamenes', function (Builder $q) use ($idExamen, $estado) {
                $q->where('id_examen', $idExamen)->where('estado', $estado);
            })
            ->count();
    }

    /**
     * Cuenta estudiantes del curso con infracción en la central de riesgos.
     *
     * @param  Curso  $curso  Curso consultado.
     * @param  ?int  $idExamen  ID del examen actual.
     * @param  string  $infraccion  Valor del tipo de infracción.
     * @return int Cantidad de estudiantes.
     */
    private function contarInfraccion(Curso $curso, ?int $idExamen, string $infraccion): int
    {
        return (int) $curso->estudiantes()
            ->whereHas('registrosAsistencia', function (Builder $q) use ($idExamen, $infraccion) {
                $q->where('id_examen', $idExamen)
                    ->whereHas('centralRiesgos', fn (Builder $q2) => $q2->where('tipo_infraccion', $infraccion));
            })
            ->count();
    }

    /**
     * Convierte un estudiante en el arreglo de datos que consume la vista/API.
     *
     * @param  Estudiante  $estudiante  Estudiante con relaciones precargadas.
     * @param  ?int  $idExamen  ID del examen actual (para ubicar el estado).
     * @return array<string, mixed> Datos del estudiante con estado, motivo y observación.
     */
    private function mapearEstudiante(Estudiante $estudiante, ?int $idExamen): array
    {
        $estado = $estudiante->estudianteExamenes->first(
            fn (EstudianteExamen $ee) => $ee->id_examen === $idExamen
        );

        return [
            'sis' => $estudiante->sis_estudiante,
            'nombre' => $estudiante->nombre_estudiante,
            'apellido' => $estudiante->apellido_estudiante,
            'carrera' => $estudiante->carrera,
            'estado' => $estado?->estado?->value,
            'motivo' => $estado?->motivo,
            'observacion' => $this->ultimaInfraccionDe($estudiante, $idExamen),
        ];
    }

    /**
     * Obtiene el tipo de infracción más reciente de un estudiante en el examen.
     *
     * @param  Estudiante  $estudiante  Estudiante con la relación registrosAsistencia.
     * @param  ?int  $idExamen  ID del examen actual (para filtrar los registros).
     * @return string|null Valor del tipo de infracción o null si no tiene observaciones.
     */
    private function ultimaInfraccionDe(Estudiante $estudiante, ?int $idExamen): ?string
    {
        return $estudiante->registrosAsistencia
            ->filter(fn (RegistroAsistencia $registro) => $registro->id_examen === $idExamen)
            ->flatMap(fn (RegistroAsistencia $registro) => $registro->centralRiesgos)
            ->sortByDesc(fn (CentralRiesgo $riesgo) => $riesgo->id_registro)
            ->first()
            ?->tipo_infraccion
            ?->value;
    }
}
