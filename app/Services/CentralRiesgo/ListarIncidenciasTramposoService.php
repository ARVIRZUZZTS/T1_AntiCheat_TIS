<?php

/**
 * @file    ListarIncidenciasTramposoService.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-10
 * @updated 2026-10-11
 *
 * @description
 * Servicio de dominio que lista las incidencias de la central de riesgos con
 * tipo de infracción `tramposo` y estado `Confirmado`. Devuelve por registro el
 * id y `id_registro`
 * (lo que abre el detalle), el nombre y el código SIS del estudiante, la
 * materia, el motivo y su etiqueta, el estado y la fecha/hora, con filtro por
 * alcance y búsqueda. El orden por defecto es alfabético (A-Z) por apellidos y
 * nombre; la pantalla de la central pide además el orden por fecha, de la más
 * reciente a la más antigua. La respuesta es la lista completa, sin paginación.
 *
 * El estudiante y el examen son columnas propias de `central_riesgo`
 * (`sis_estudiante`, `id_examen`), así que no hace falta pasar por
 * `registro_asistencia`. La materia se resuelve por la intersección de los
 * cursos del estudiante con los cursos del examen; si el estudiante no está
 * inscrito en ningún curso del examen, la materia queda en el primer curso del
 * examen (ordenado por id). Cuando la incidencia guardó el curso elegido
 * (`id_curso`), ese manda sobre la intersección. El motivo se devuelve como string: el enum de la
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
 * - 2026-10-09  [T1]  refactor: quitar la paginación (la API devuelve la
 *   lista completa) y eliminar el parámetro de orden: el listado siempre es
 *   A-Z por apellidos y nombre.
 * - 2026-10-10  [Valery D. Ortuno P]  feat: orden por fecha (más reciente
 *   primero) para la lista de la central (HU 10); el A-Z sigue siendo el orden
 *   por defecto de la API. Cada registro trae `id_registro`, que es lo que
 *   abre el detalle, y `motivo_etiqueta`. La materia respeta `id_curso`.
 * - 2026-10-10  [Valery D. Ortuno P]  refactor: la búsqueda y el orden A-Z ya
 *   no dependen de `ilike` ni de `split_part`, exclusivos de PostgreSQL, para
 *   que el servicio se pueda probar con SQLite en memoria.
 * - 2026-10-11  [Alisson D. Alvarado]  fix: el mapeo agrega `id` (para el
 *   enlace "Ver detalle") y `estado` (columna Estado de la tabla).
 * - 2026-10-11  [Valery D. Ortuno P]  fix: la central solo lista incidencias
 *   `Confirmado`; una incidencia pendiente ya no aparece como tramposo (HU 10).
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Examen;
use App\Services\Examen\BusquedaEstudianteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ListarIncidenciasTramposoService
{
    public const ALCANCE_MIS_MATERIAS = 'mis-materias';

    public const ALCANCE_INSTITUCION = 'toda-la-institucion';

    public const ALCANCES_VALIDOS = [
        self::ALCANCE_MIS_MATERIAS,
        self::ALCANCE_INSTITUCION,
    ];

    public const ORDEN_ALFABETICO = 'alfabetico';

    public const ORDEN_RECIENTES = 'recientes';

    public const ORDENES_VALIDOS = [
        self::ORDEN_ALFABETICO,
        self::ORDEN_RECIENTES,
    ];

    /**
     * Criterio de búsqueda (modo, saneo y validación).
     */
    private readonly BusquedaEstudianteService $busqueda;

    public function __construct(?BusquedaEstudianteService $busqueda = null)
    {
        $this->busqueda = $busqueda ?? app(BusquedaEstudianteService::class);
    }

    /**
     * Lista completa de incidencias `tramposo` de la central de riesgos.
     *
     * @param  ?int  $usuario  ID del usuario logueado, requerido con alcance mis-materias.
     * @param  ?string  $alcance  Alcance del listado (ver ALCANCES_VALIDOS).
     * @param  ?string  $busqueda  Texto de búsqueda por nombre/código SIS.
     * @param  string  $orden  Orden del listado (ver ORDENES_VALIDOS).
     * @return Collection<int, array<string, mixed>> Incidencias mapeadas, en el orden pedido.
     *
     * @throws InvalidArgumentException Si el alcance, la búsqueda o el orden no son válidos.
     */
    public function ejecutar(
        ?int $usuario,
        ?string $alcance,
        ?string $busqueda = null,
        string $orden = self::ORDEN_ALFABETICO,
    ): Collection {
        $this->validarAlcance($alcance, $usuario);
        $this->validarOrden($orden);

        $query = CentralRiesgo::query()
            ->join('estudiante', 'estudiante.sis_estudiante', '=', 'central_riesgo.sis_estudiante')
            ->where('central_riesgo.tipo_infraccion', TipoInfraccion::Tramposo->value)
            // Solo las confirmadas entran a la central: una incidencia pendiente
            // todavía no es una sanción y no debe mostrarse como tramposo.
            ->where('central_riesgo.estado_incidencia', EstadoIncidencia::Confirmado->value)
            ->select('central_riesgo.*')
            ->with(['estudiante', 'curso', 'examen.cursos', 'estudiante.cursos']);

        if ($alcance === self::ALCANCE_MIS_MATERIAS) {
            $this->filtrarPorMisMaterias($query, (int) $usuario);
        }

        $this->aplicarBusqueda($query, $busqueda);

        // La fecha es el orden base: es el que pide la central por defecto y el
        // que desempata a dos estudiantes con el mismo nombre en el orden A-Z.
        $incidencias = $query
            ->orderByDesc('central_riesgo.fecha_registro')
            ->orderByDesc('central_riesgo.id_registro')
            ->get();

        if ($orden === self::ORDEN_ALFABETICO) {
            $incidencias = $this->ordenarAlfabeticamente($incidencias);
        }

        return $incidencias
            ->map(fn (CentralRiesgo $incidencia): array => $this->mapear($incidencia))
            ->values();
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
     * @param  string  $orden  Orden recibido.
     *
     * @throws InvalidArgumentException Si el orden no está en la lista permitida.
     */
    private function validarOrden(string $orden): void
    {
        if (! in_array($orden, self::ORDENES_VALIDOS, true)) {
            throw new InvalidArgumentException('Orden no válido');
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

        // whereLike sin distinguir mayúsculas equivale a `ilike` en PostgreSQL y
        // a `like` en SQLite, que es el motor de las pruebas.
        if ($modo === BusquedaEstudianteService::MODO_SIS) {
            $query->whereLike('estudiante.sis_estudiante', $patron);

            return;
        }

        $query->where(function (Builder $q) use ($patron) {
            $q->whereLike('estudiante.nombre_estudiante', $patron)
                ->orWhereLike('estudiante.apellido_estudiante', $patron)
                ->orWhereRaw(
                    "lower(estudiante.nombre_estudiante || ' ' || estudiante.apellido_estudiante) like ?",
                    [mb_strtolower($patron)]
                );
        });
    }

    /**
     * Ordena alfabéticamente: primer apellido, segundo apellido y luego nombre.
     *
     * `apellido_estudiante` guarda los dos apellidos en un campo, así que cada
     * posición se compara por separado. Se ordena acá y no en la consulta para
     * que tildes y mayúsculas no dependan de la intercalación de la base: "Álvarez"
     * va junto a "Alvarez" y no después de la "Z". El orden es estable, de modo
     * que los empates conservan el orden por fecha con el que llegan.
     *
     * @param  Collection<int, CentralRiesgo>  $incidencias  Incidencias con estudiante precargado.
     * @return Collection<int, CentralRiesgo> Las mismas incidencias, en orden A-Z.
     */
    private function ordenarAlfabeticamente(Collection $incidencias): Collection
    {
        return $incidencias->sortBy(function (CentralRiesgo $incidencia): array {
            $apellidos = preg_split('/\s+/', trim((string) $incidencia->estudiante?->apellido_estudiante)) ?: [];

            return [
                $this->claveDeOrden($apellidos[0] ?? ''),
                $this->claveDeOrden($apellidos[1] ?? ''),
                $this->claveDeOrden((string) $incidencia->estudiante?->nombre_estudiante),
            ];
        });
    }

    /**
     * @param  string  $texto  Apellido o nombre tal como está en la base.
     * @return string El texto sin tildes y en minúscula, listo para comparar.
     */
    private function claveDeOrden(string $texto): string
    {
        return mb_strtolower(Str::ascii(trim($texto)));
    }

    /**
     * Convierte una incidencia en el arreglo de datos que consume la API.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia con relaciones precargadas.
     * @return array<string, mixed> Datos del estudiante, materia, motivo, estado y fecha.
     */
    private function mapear(CentralRiesgo $incidencia): array
    {
        $estudiante = $incidencia->estudiante;

        return [
            'id' => $incidencia->id_registro,
            'id_registro' => $incidencia->id_registro,
            'nombre' => $estudiante?->nombre_estudiante,
            'apellido' => $estudiante?->apellido_estudiante,
            'sis' => $estudiante?->sis_estudiante,
            'materia' => $this->resolverMateria($incidencia),
            'motivo' => $incidencia->motivo,
            'motivo_etiqueta' => $incidencia->motivo->etiqueta(),
            // El modelo castea `estado_incidencia` a EstadoIncidencia; se
            // devuelve el string del enum para que el JSON y Blade lo muestren
            // igual que la columna Estado.
            'estado' => $incidencia->estado_incidencia->value,
            'fecha' => $incidencia->fecha_registro->toDateTimeString(),
        ];
    }

    /**
     * Materia de la incidencia: el curso guardado al registrarla o, si no hay,
     * el curso del estudiante dentro del examen.
     *
     * @param  CentralRiesgo  $incidencia  Incidencia con curso, estudiante y examen precargados.
     * @return ?string Nombre del curso, o null si el examen no tiene cursos.
     */
    private function resolverMateria(CentralRiesgo $incidencia): ?string
    {
        // Mismo criterio que CentralRiesgo::materia(), que es el que usa el
        // detalle: el curso elegido al registrar manda sobre el del examen.
        if ($incidencia->curso !== null) {
            return $incidencia->curso->nombre_curso;
        }

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
