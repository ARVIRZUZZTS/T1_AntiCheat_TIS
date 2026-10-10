<?php

/**
 * @file    ListarIncidenciasTramposoService.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Servicio de dominio que lista las incidencias de la central de riesgos con
 * tipo de infracción `tramposo`. Devuelve por registro el nombre y el código
 * SIS del estudiante, la materia, el motivo y la fecha/hora, con ordenamiento,
 * filtro por alcance, búsqueda y paginación de 8 en 8.
 *
 * El estudiante y el examen son columnas propias de `central_riesgo`
 * (`sis_estudiante`, `id_examen`), así que no hace falta pasar por
 * `registro_asistencia`. La materia se resuelve por la intersección de los
 * cursos del estudiante con los cursos del examen; si el estudiante no está
 * inscrito en ningún curso del examen, la materia queda en el primer curso del
 * examen (ordenado por id). El motivo se devuelve como string: el enum de la
 * base usa espacios y el enum de dominio guiones bajos, así que no se castea.
 *
 * El criterio de la búsqueda (modo, saneo y validación) lo aporta
 * {@see BusquedaEstudianteService}, la misma fuente que usa la lista de
 * estudiantes por materia; acá solo se aplica el predicado sobre el join con
 * `estudiante`, porque la consulta arranca desde `central_riesgo` y no desde
 * un `BelongsToMany` de cursos.
 *
 * @see  App\Services\Examen\BusquedaEstudianteService
 * @see  App\Http\Controllers\Api\CentralRiesgoController
 *
 * @changelog
 * - 2026-10-09  [T1]  feat: creación inicial del servicio.
 */

namespace App\Services\CentralRiesgo;

use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Examen;
use App\Services\Examen\BusquedaEstudianteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class ListarIncidenciasTramposoService
{
    public const ALCANCE_MIS_MATERIAS = 'mis-materias';

    public const ALCANCE_INSTITUCION = 'toda-la-institucion';

    public const ALCANCES_VALIDOS = [
        self::ALCANCE_MIS_MATERIAS,
        self::ALCANCE_INSTITUCION,
    ];

    private const POR_PAGINA = 8;

    /**
     * Criterio de búsqueda (modo, saneo y validación).
     */
    private readonly BusquedaEstudianteService $busqueda;

    public function __construct(?BusquedaEstudianteService $busqueda = null)
    {
        $this->busqueda = $busqueda ?? app(BusquedaEstudianteService::class);
    }

    /**
     * Lista paginada de incidencias `tramposo` de la central de riesgos.
     *
     * @param  ?int  $usuario  ID del usuario logueado, requerido con alcance mis-materias.
     * @param  ?string  $alcance  Alcance del listado (ver ALCANCES_VALIDOS).
     * @param  bool  $ordenAz  Orden alfabético por apellidos y nombre cuando es true.
     * @param  ?string  $busqueda  Texto de búsqueda por nombre/código SIS.
     * @param  int  $pagina  Número de página.
     * @return LengthAwarePaginator<int, array<string, mixed>> Página de incidencias mapeadas.
     *
     * @throws InvalidArgumentException Si el alcance, la búsqueda o el usuario no son válidos.
     */
    public function ejecutar(
        ?int $usuario,
        ?string $alcance,
        bool $ordenAz = false,
        ?string $busqueda = null,
        int $pagina = 1,
    ): LengthAwarePaginator {
        $this->validarAlcance($alcance, $usuario);

        $query = CentralRiesgo::query()
            ->join('estudiante', 'estudiante.sis_estudiante', '=', 'central_riesgo.sis_estudiante')
            ->where('central_riesgo.tipo_infraccion', TipoInfraccion::Tramposo->value)
            ->select('central_riesgo.*')
            ->with(['estudiante', 'examen.cursos', 'estudiante.cursos']);

        if ($alcance === self::ALCANCE_MIS_MATERIAS) {
            $this->filtrarPorMisMaterias($query, (int) $usuario);
        }

        $this->aplicarBusqueda($query, $busqueda);
        $this->aplicarOrden($query, $ordenAz);

        $paginador = $query->paginate(self::POR_PAGINA, ['*'], 'page', $pagina);

        $datos = collect($paginador->items())
            ->map(fn (CentralRiesgo $incidencia): array => $this->mapear($incidencia));

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
     * Valida el alcance recibido y que el usuario venga cuando se necesita.
     *
     * @param  ?string  $alcance  Alcance recibido (puede ser null).
     * @param  ?int  $usuario  Usuario recibido por query string.
     *
     * @throws InvalidArgumentException Si el alcance no está en la lista permitida o falta el usuario.
     */
    private function validarAlcance(?string $alcance, ?int $usuario): void
    {
        if ($alcance === null || $alcance === '') {
            return;
        }

        if (! in_array($alcance, self::ALCANCES_VALIDOS, true)) {
            throw new InvalidArgumentException('Alcance no válido');
        }

        if ($alcance === self::ALCANCE_MIS_MATERIAS && $usuario === null) {
            throw new InvalidArgumentException('El alcance mis-materias requiere el parámetro usuario');
        }
    }

    /**
     * Deja solo las incidencias cuyo examen tenga al menos un curso del usuario.
     *
     * @param  Builder<CentralRiesgo>  $query  Consulta de incidencias.
     * @param  int  $usuario  ID del usuario dueño de los cursos.
     */
    private function filtrarPorMisMaterias(Builder $query, int $usuario): void
    {
        $query->whereHas('examen.cursos', function (Builder $q) use ($usuario) {
            $q->where('curso.sis_doc', $usuario);
        });
    }

    /**
     * Aplica el predicado del término de búsqueda sobre el join con estudiante.
     *
     * El modo lo decide BusquedaEstudianteService: un término que empieza con
     * cifra busca solo por código SIS y uno que empieza con letra solo por
     * nombre o apellido.
     *
     * @param  Builder<CentralRiesgo>  $query  Consulta de incidencias.
     * @param  ?string  $busqueda  Término escrito por la persona que busca.
     *
     * @throws InvalidArgumentException Si el término no es válido para su modo.
     */
    private function aplicarBusqueda(Builder $query, ?string $busqueda): void
    {
        $modo = $this->busqueda->validar($busqueda);

        if ($modo === '') {
            return;
        }

        $patron = '%'.$busqueda.'%';

        if ($modo === BusquedaEstudianteService::MODO_SIS) {
            $query->where('estudiante.sis_estudiante', 'ilike', $patron);

            return;
        }

        $query->where(function (Builder $q) use ($patron) {
            $q->where('estudiante.nombre_estudiante', 'ilike', $patron)
                ->orWhere('estudiante.apellido_estudiante', 'ilike', $patron)
                ->orWhereRaw(
                    "estudiante.nombre_estudiante || ' ' || estudiante.apellido_estudiante ilike ?",
                    [$patron]
                );
        });
    }

    /**
     * Aplica el ordenamiento elegido a la consulta de incidencias.
     *
     * @param  Builder<CentralRiesgo>  $query  Consulta de incidencias.
     * @param  bool  $ordenAz  Verdadero para orden alfabético.
     */
    private function aplicarOrden(Builder $query, bool $ordenAz): void
    {
        if ($ordenAz) {
            // `apellido_estudiante` guarda los dos apellidos en un campo; el
            // orden pide primero el primer apellido, luego el segundo y después
            // el nombre, que es lo que resuelve split_part en cada posición.
            $query->orderByRaw("split_part(estudiante.apellido_estudiante, ' ', 1)")
                ->orderByRaw("split_part(estudiante.apellido_estudiante, ' ', 2)")
                ->orderBy('estudiante.nombre_estudiante');

            return;
        }

        $query->orderByDesc('central_riesgo.fecha_registro')
            ->orderByDesc('central_riesgo.id_registro');
    }

    /**
     * Convierte una incidencia en el arreglo de datos que consume la API.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia con relaciones precargadas.
     * @return array<string, mixed> Datos del estudiante, materia, motivo y fecha.
     */
    private function mapear(CentralRiesgo $incidencia): array
    {
        $estudiante = $incidencia->estudiante;

        return [
            'nombre' => $estudiante?->nombre_estudiante,
            'apellido' => $estudiante?->apellido_estudiante,
            'sis' => $estudiante?->sis_estudiante,
            'materia' => $this->resolverMateria($incidencia),
            'motivo' => $incidencia->motivo,
            'fecha' => $incidencia->fecha_registro->toDateTimeString(),
        ];
    }

    /**
     * Materia de la incidencia: el curso del estudiante dentro del examen.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia con estudiante y examen precargados.
     * @return ?string Nombre del curso, o null si el examen no tiene cursos.
     */
    private function resolverMateria(CentralRiesgo $incidencia): ?string
    {
        $examen = $incidencia->examen;

        if ($examen === null) {
            return null;
        }

        $estudiante = $incidencia->estudiante;
        $cursosExamen = $examen->cursos->sortBy('id_curso');
        $cursosEstudiante = $estudiante === null ? collect() : $estudiante->cursos;

        $principal = $cursosExamen->first(
            fn (Curso $curso) => $cursosEstudiante->contains('id_curso', $curso->id_curso)
        ) ?? $cursosExamen->first();

        return $principal?->nombre_curso;
    }
}
