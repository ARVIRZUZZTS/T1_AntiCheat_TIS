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
 * @updated 2026-10-24
 * @updated 2026-10-25
 *
 * @description
 * Servicio de dominio que lista las incidencias de la central de riesgos con
 * tipo de infracción `tramposo` (que equivale a confirmada). Devuelve por
 * registro el id y `id_registro` (lo que abre el detalle), el nombre y el
 * código SIS del estudiante, la materia y el curso, el motivo y su etiqueta,
 * el estado y la fecha/hora, con filtro por alcance y búsqueda. El orden por
 * defecto es alfabético (A-Z) por apellidos y nombre; la pantalla de la
 * central pide además el orden por fecha, de la más reciente a la más antigua.
 * La respuesta es la lista completa, sin paginación.
 *
 * El estudiante y el curso son columnas propias de `central_riesgo`
 * (`sis_estudiante`, `id_curso`): la lista muestra lo que quedó registrado,
 * sin deducir el curso desde el examen. `curso` es el nombre completo
 * ("Calculo I - Grupo 2") y `materia` solo la materia ("Calculo I"). El
 * motivo se devuelve como string: el enum de la base usa espacios y el enum de
 * dominio guiones bajos, así que no se castea.
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
 * - 2026-10-11  [Valery D. Ortuno P]  perf: la lista pasa de cinco consultas a
 *   una: el estudiante se lee del join que ya existía y la materia se resuelve
 *   con una subconsulta (el curso guardado, o el primer curso del examen donde
 *   el estudiante está inscrito), en vez de los eager loads `estudiante`,
 *   `curso`, `examen.cursos` y `estudiante.cursos`. Contra un PostgreSQL remoto
 *   cada viaje de red pesa ~380 ms; el orden A-Z y el contrato de salida no
 *   cambian.
 * - 2026-10-24  [Valery D. Ortuno P]  refactor: la central lista por tipo de
 *   infracción (`tramposo` = confirmado) en vez de `estado_incidencia`, que se
 *   dejará de usar. El curso sale solo de `central_riesgo.id_curso` (sin
 *   deducirlo del examen), "Mis materias" filtra por el docente de ese curso y
 *   cada registro trae `curso` (nombre completo) y `materia` (sin el grupo).
 * - 2026-10-25  [Valery D. Ortuno P]  feat: cada registro trae `verificado`
 *   (false si el estudiante se dio de alta desde una incidencia, HU 5).
 */

namespace App\Services\CentralRiesgo;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Services\Examen\BusquedaEstudianteService;
use App\Support\NombreCurso;
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

        // Una sola consulta: el estudiante y el curso entran por join en vez de
        // precargarlos (cada viaje de red a la base remota pesa).
        $query = CentralRiesgo::query()
            ->join('estudiante', 'estudiante.sis_estudiante', '=', 'central_riesgo.sis_estudiante')
            ->leftJoin('curso', 'curso.id_curso', '=', 'central_riesgo.id_curso')
            // Tramposo equivale a confirmado (sospechoso a pendiente): la central
            // solo lista sanciones, no reportes que el docente aún no revisó.
            ->where('central_riesgo.tipo_infraccion', TipoInfraccion::Tramposo->value)
            ->select('central_riesgo.*')
            ->selectRaw('estudiante.nombre_estudiante as lista_est_nombre')
            ->selectRaw('estudiante.apellido_estudiante as lista_est_apellido')
            ->selectRaw('estudiante.verificado as lista_est_verificado')
            // El curso es el que quedó registrado en la incidencia.
            ->selectRaw('curso.nombre_curso as lista_curso');

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
     * Deja solo las incidencias registradas en un curso del usuario.
     *
     * @param  Builder<CentralRiesgo>  $query  Consulta de incidencias (con el join a `curso`).
     * @param  int  $usuario  ID del docente dueño de los cursos.
     */
    private function filtrarPorMisMaterias(Builder $query, int $usuario): void
    {
        $query->where('curso.sis_doc', $usuario);
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
            $apellidos = preg_split('/\s+/', trim((string) $incidencia->getAttribute('lista_est_apellido'))) ?: [];

            return [
                $this->claveDeOrden($apellidos[0] ?? ''),
                $this->claveDeOrden($apellidos[1] ?? ''),
                $this->claveDeOrden((string) $incidencia->getAttribute('lista_est_nombre')),
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
     * @param  CentralRiesgo  $incidencia  Incidencia con estudiante y curso del join.
     * @return array<string, mixed> Datos del estudiante, curso, materia, motivo, estado y fecha.
     */
    private function mapear(CentralRiesgo $incidencia): array
    {
        $curso = $incidencia->getAttribute('lista_curso');

        return [
            'id' => $incidencia->id_registro,
            'id_registro' => $incidencia->id_registro,
            'nombre' => $incidencia->getAttribute('lista_est_nombre'),
            'apellido' => $incidencia->getAttribute('lista_est_apellido'),
            // False si el estudiante se dio de alta desde una incidencia (HU 5).
            'verificado' => (bool) ($incidencia->getAttribute('lista_est_verificado') ?? true),
            'sis' => $incidencia->getAttribute('sis_estudiante'),
            'curso' => $curso,
            'materia' => $curso !== null ? NombreCurso::materia($curso) : null,
            'motivo' => $incidencia->motivo,
            'motivo_etiqueta' => $incidencia->motivo->etiqueta(),
            // La central solo lista tramposos, y tramposo equivale a confirmado.
            'estado' => EstadoIncidencia::Confirmado->value,
            'fecha' => $incidencia->fecha_registro->toDateTimeString(),
        ];
    }
}
