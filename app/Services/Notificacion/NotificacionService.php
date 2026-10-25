<?php

/**
 * @file    NotificacionService.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description
 * Servicio de dominio de las notificaciones del docente.
 *
 * - `notificarPosibleTramposo()` crea el aviso "Posible Tramposo" (HU 12)
 *   cuando un auxiliar registra a un estudiante como sospechoso. El aviso
 *   referencia el caso (`central_riesgo`) y el curso del docente; el resto de
 *   los datos (auxiliar, estudiante, examen, materia, fecha/hora) se derivan
 *   de esas relaciones al listar, así que no se duplican ni se desincronizan.
 * - `notificarCentralRiesgo()` crea el aviso "Estudiante ingresado a central de
 *   riesgo" (HU 6) cuando un estudiante queda registrado o confirmado como
 *   tramposo: un aviso por cada materia activa del estudiante, para que cada
 *   docente vea solo el de su curso.
 * - `listarPara()` devuelve las notificaciones no ocultas del usuario
 *   autenticado, de la más reciente a la más antigua, con el tiempo transcurrido
 *   en español. Un auxiliar nunca recibe notificaciones.
 * - `ocultar()` hace el borrado lógico de una notificación propia.
 *
 * @see  App\Services\Notificacion\TiempoTranscurrido
 * @see  App\Policies\NotificacionDocentePolicy
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: creación del servicio (#HU12).
 * - 2026-10-11  [David E. Chavez T.]  feat: aviso "Estudiante ingresado a
 *   central de riesgo" al registrar/confirmar un tramposo, con un aviso por
 *   materia activa del estudiante (#HU6).
 */

namespace App\Services\Notificacion;

use App\Enums\CursoEstado;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\InvitacionExamenCompartido;
use App\Models\NotificacionDocente;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NotificacionService
{
    public const TITULO_POSIBLE_TRAMPOSO = 'Posible Tramposo';

    /** Título del aviso de la HU 6: el estudiante ya está en la central. */
    public const TITULO_CENTRAL_RIESGO = 'Estudiante ingresado a central de riesgo';

    public const TITULO_INVITACION_EXAMEN = 'Invitación para compartir un examen';

    /**
     * Tipos de aviso (columna `tipo` de `notificacion_docente`). La pantalla
     * pinta una tarjeta distinta según el valor.
     */
    public const TIPO_POSIBLE_TRAMPOSO = 'posible_tramposo';

    public const TIPO_CENTRAL_RIESGO = 'central_riesgo';

    public const TIPO_INVITACION_EXAMEN = 'invitacion_examen';

    /** Estado inicial con el que nace un aviso, del enum `estado_notificacion`. */
    public const ESTADO_RECIBIDO = 'recibido';

    public function __construct(
        private readonly TiempoTranscurrido $tiempo = new TiempoTranscurrido,
    ) {}

    /**
     * Crea el aviso del docente cuando el caso es un posible tramposo.
     *
     * Solo aplica a incidencias de tipo `sospechoso` (las que registra un
     * auxiliar). El curso del aviso es el elegido en el caso y, si no hay, el
     * primer curso del examen; sin curso no hay a quién avisar y no se crea
     * nada. La columna `oculta` no se escribe: la base la deja en su default
     * (`false`), de modo que el alta no depende de que la migración de la T2 ya
     * esté ejecutada.
     *
     * @param  CentralRiesgo  $registro  Caso recién registrado.
     * @return ?NotificacionDocente El aviso creado, o null si no aplica.
     */
    public function notificarPosibleTramposo(CentralRiesgo $registro): ?NotificacionDocente
    {
        if ($registro->tipo_infraccion !== TipoInfraccion::Sospechoso) {
            return null;
        }

        $idCurso = $this->resolverCurso($registro);

        if ($idCurso === null) {
            return null;
        }

        return DB::transaction(function () use ($registro, $idCurso): NotificacionDocente {
            $this->bloquearTabla();

            return NotificacionDocente::create([
                'id_notificacion' => (int) NotificacionDocente::query()->max('id_notificacion') + 1,
                'id_central_riesgo' => $registro->id_registro,
                'id_curso' => $idCurso,
                'tipo' => self::TIPO_POSIBLE_TRAMPOSO,
                'estado' => self::ESTADO_RECIBIDO,
            ]);
        });
    }

    /**
     * Avisa del ingreso de un tramposo a la central de riesgos (HU 6 · T1).
     *
     * Se dispara cuando un estudiante queda registrado o confirmado como
     * "Tramposo". A diferencia del aviso "Posible Tramposo" (que va solo al
     * docente de la materia del caso), aquí se avisa a **todos los docentes
     * que tienen al estudiante en sus materias activas**: el hecho es relevante
     * para cada curso donde cursa, no únicamente para el del examen. Cada
     * aviso se vincula a su curso, así cada docente ve solamente el suyo y
     * nadie recibe de golpe estudiantes ajenos a sus materias.
     *
     * Es idempotente: si el caso ya tiene aviso `central_riesgo` para un curso,
     * no se duplica (p. ej. registrar tramposo y confirmar el mismo caso).
     *
     * @param  CentralRiesgo  $registro  Caso recién registrado o confirmado.
     * @param  bool  $porConfirmacion  True cuando el ingreso a la central se
     *                                 produce por la confirmación de un caso que el auxiliar reportó como
     *                                 sospechoso (sigue teniendo `tipo_infraccion = sospechoso`).
     * @return array<int, NotificacionDocente> Avisos creados (uno por curso activo).
     */
    public function notificarCentralRiesgo(CentralRiesgo $registro, bool $porConfirmacion = false): array
    {
        $esTramposo = $registro->tipo_infraccion === TipoInfraccion::Tramposo;

        // Un sospechoso reportado por el auxiliar todavía NO está en la central:
        // su aviso es el de "Posible Tramposo" (HU 12). El de la HU 6 espera a
        // que se registre como tramposo o a que el docente confirme el caso.
        if (! $esTramposo && ! $porConfirmacion) {
            return [];
        }

        $cursos = $this->cursosActivosDelEstudiante($registro);

        if ($cursos->isEmpty()) {
            return [];
        }

        return DB::transaction(function () use ($registro, $cursos): array {
            $this->bloquearTabla();

            $yaAvisados = NotificacionDocente::query()
                ->where('id_central_riesgo', $registro->id_registro)
                ->where('tipo', self::TIPO_CENTRAL_RIESGO)
                ->pluck('id_curso')
                ->map(fn ($idCurso): int => (int) $idCurso)
                ->all();

            $creados = [];

            foreach ($cursos as $idCurso) {
                if (in_array((int) $idCurso, $yaAvisados, true)) {
                    continue;
                }

                $creados[] = NotificacionDocente::create([
                    'id_notificacion' => (int) NotificacionDocente::query()->max('id_notificacion') + 1,
                    'id_central_riesgo' => $registro->id_registro,
                    'id_curso' => $idCurso,
                    'tipo' => self::TIPO_CENTRAL_RIESGO,
                    'estado' => self::ESTADO_RECIBIDO,
                ]);
            }

            return $creados;
        });
    }

    public function notificarInvitacionExamen(InvitacionExamenCompartido $invitacion): NotificacionDocente
    {
        return DB::transaction(function () use ($invitacion): NotificacionDocente {
            $this->bloquearTabla();

            return NotificacionDocente::create([
                'id_notificacion' => (int) NotificacionDocente::query()->max('id_notificacion') + 1,
                'id_central_riesgo' => null,
                'id_curso' => null,
                'id_docente_destinatario' => $invitacion->id_docente_invitado,
                'id_invitacion' => $invitacion->id_invitacion,
                'tipo' => self::TIPO_INVITACION_EXAMEN,
                'estado' => self::ESTADO_RECIBIDO,
            ]);
        });
    }

    /**
     * Cursos activos (`estado = EnCurso`) en los que cursa el estudiante del
     * caso, de más reciente a más antiguo para un reparto determinista.
     */
    private function cursosActivosDelEstudiante(CentralRiesgo $registro): Collection
    {
        $estudiante = $registro->estudiante;

        if ($estudiante === null) {
            return collect();
        }

        return $estudiante->cursos()
            ->where('curso.estado', CursoEstado::EnCurso->value)
            ->orderByDesc('curso.fecha_creacion')
            ->pluck('curso.id_curso');
    }

    /**
     * Notificaciones no ocultas del usuario autenticado, de la más reciente a la
     * más antigua.
     *
     * Un auxiliar no recibe notificaciones "Posible Tramposo": se corta antes de
     * consultar. Si no hay ninguna, devuelve una colección vacía.
     *
     * @param  Usuario  $usuario  Usuario de la sesión.
     * @return Collection<int, array<string, mixed>> Avisos mapeados para la API.
     */
    public function listarPara(Usuario $usuario): Collection
    {
        if ($this->esAuxiliar($usuario)) {
            return collect();
        }

        $notificaciones = NotificacionDocente::query()
            ->where('oculta', false)
            ->where(function (Builder $query) use ($usuario): void {
                $query->where('id_docente_destinatario', $usuario->id_usuario)
                    ->orWhere(function (Builder $notificacionLegacy) use ($usuario): void {
                        $notificacionLegacy
                            ->whereNull('id_docente_destinatario')
                            ->whereHas('curso', fn (Builder $curso): Builder => $curso->where('sis_doc', $usuario->id_usuario));
                    });
            })
            ->with([
                'curso',
                'centralRiesgo.estudiante',
                'centralRiesgo.examen.tipoExamen',
                'centralRiesgo.registrador',
                'invitacion.invitador',
                'invitacion.examen.tipoExamen',
                'invitacion.examen.cursos',
            ])
            ->get();

        return $this->preparar($notificaciones);
    }

    /**
     * Ordena y mapea una colección de avisos ya cargados.
     *
     * Se separa del acceso a datos para poder probar el orden (más reciente
     * primero) y el formato del tiempo sin tocar la base.
     *
     * @param  Collection<int, NotificacionDocente>  $notificaciones
     * @return Collection<int, array<string, mixed>>
     */
    public function preparar(Collection $notificaciones): Collection
    {
        return $notificaciones
            ->sortByDesc(fn (NotificacionDocente $notificacion): int => $this->fechaDe($notificacion)?->getTimestamp() ?? 0)
            ->values()
            ->map(fn (NotificacionDocente $notificacion): array => $this->mapear($notificacion));
    }

    /**
     * Oculta una notificación propia (borrado lógico). Lanza 404 si no existe y
     * 403 si es de otro docente, vía la policy.
     *
     * @param  int  $idNotificacion  Id del aviso.
     * @param  Usuario  $usuario  Docente de la sesión.
     *
     * @throws ModelNotFoundException Si no existe.
     * @throws AuthorizationException Si no es suya.
     */
    public function ocultar(int $idNotificacion, Usuario $usuario): void
    {
        $notificacion = NotificacionDocente::query()->with(['curso', 'invitacion'])->findOrFail($idNotificacion);

        Gate::forUser($usuario)->authorize('ocultar', $notificacion);

        $notificacion->oculta = true;
        $notificacion->save();
    }

    /** Un auxiliar nunca recibe notificaciones "Posible Tramposo". */
    public function esAuxiliar(Usuario $usuario): bool
    {
        return $usuario->roles->contains('nombre_rol', Rol::NOMBRE_AUXILIAR);
    }

    /**
     * Curso (materia) del aviso: el elegido en el caso o, si no, el primero del
     * examen.
     */
    private function resolverCurso(CentralRiesgo $registro): ?int
    {
        if ($registro->id_curso !== null) {
            return (int) $registro->id_curso;
        }

        return $registro->examen?->cursos->sortBy('id_curso')->first()?->id_curso;
    }

    /**
     * @param  NotificacionDocente  $notificacion  Aviso con sus relaciones cargadas.
     * @return array<string, mixed> Datos que consume la pantalla/API.
     */
    private function mapear(NotificacionDocente $notificacion): array
    {
        if ($notificacion->tipo === self::TIPO_INVITACION_EXAMEN) {
            return $this->mapearInvitacion($notificacion);
        }

        $registro = $notificacion->centralRiesgo;
        $fecha = $registro?->fecha_registro;
        $auxiliar = $registro?->registrador;
        $estudiante = $registro?->estudiante;
        $examen = $registro?->examen;
        $materia = (string) ($notificacion->curso->nombre_curso ?? $registro?->materia() ?? '');
        $nombreExamen = $examen?->tipoExamen->nombre_tipo_examen ?? ($examen !== null ? 'Examen #'.$examen->id_examen : '');
        $tipo = $notificacion->tipo ?? self::TIPO_POSIBLE_TRAMPOSO;
        $esCentral = $tipo === self::TIPO_CENTRAL_RIESGO;

        return [
            'id' => (int) $notificacion->id_notificacion,
            'tipo' => $tipo,
            'titulo' => $esCentral ? self::TITULO_CENTRAL_RIESGO : self::TITULO_POSIBLE_TRAMPOSO,
            'auxiliar' => $this->nombreCompleto($auxiliar),
            'estudiante' => $this->nombreCompleto($estudiante),
            'examen' => $nombreExamen,
            'materia' => $materia,
            'fecha_registro' => $fecha?->toDateTimeString(),
            'hace' => $fecha !== null ? $this->tiempo->enEspanol($fecha) : '',
            'referencia' => (int) $registro->id_registro,
            'detalle_href' => $esCentral
                ? '#'
                : ($registro !== null ? route('incidencias.detalle', $registro->id_registro) : '#'),
            'descripcion' => $esCentral
                ? sprintf(
                    'El estudiante %s fue ingresado a la central de riesgo en la materia de %s.',
                    $this->nombreCompleto($estudiante),
                    $materia,
                )
                : sprintf(
                    'El auxiliar %s registró al estudiante %s como posible tramposo en el examen de %s de la materia %s.',
                    $this->nombreCompleto($auxiliar),
                    $this->nombreCompleto($estudiante),
                    $nombreExamen,
                    $materia,
                ),
        ];
    }

    /** @return array<string, mixed> */
    private function mapearInvitacion(NotificacionDocente $notificacion): array
    {
        $invitacion = $notificacion->invitacion;
        $examen = $invitacion?->examen;
        $invitador = $invitacion?->invitador;
        $tipoExamen = $examen !== null
            ? $examen->tipoExamen->nombre_tipo_examen
            : '';
        $materias = $examen === null
            ? []
            : $examen->cursos->pluck('nombre_curso')->filter()->values()->all();
        $detalleMaterias = $materias !== [] ? ' de '.implode(', ', $materias) : '';
        $fecha = $invitacion?->created_at;

        return [
            'id' => (int) $notificacion->id_notificacion,
            'tipo' => self::TIPO_INVITACION_EXAMEN,
            'titulo' => self::TITULO_INVITACION_EXAMEN,
            'descripcion' => sprintf(
                '%s te invitó a compartir el examen %s%s.',
                $this->nombreCompleto($invitador),
                $tipoExamen,
                $detalleMaterias,
            ),
            'referencia' => (int) $invitacion?->id_invitacion,
            'estado' => $invitacion?->estado?->value,
            'fecha_registro' => $fecha?->toISOString(),
            'hace' => $fecha !== null ? $this->tiempo->enEspanol($fecha) : '',
            'datos' => [
                'invitacion_id' => (int) $invitacion?->id_invitacion,
                'examen_id' => (int) $examen?->id_examen,
                'examen' => $tipoExamen,
                'materias' => $materias,
                'invitador' => $this->nombreCompleto($invitador),
            ],
        ];
    }

    private function fechaDe(NotificacionDocente $notificacion): ?CarbonInterface
    {
        return $notificacion->tipo === self::TIPO_INVITACION_EXAMEN
            ? $notificacion->invitacion?->created_at
            : $notificacion->centralRiesgo?->fecha_registro;
    }

    /** Nombre completo de un estudiante o de un usuario, según sus columnas. */
    private function nombreCompleto(?Model $persona): string
    {
        if ($persona === null) {
            return '';
        }

        $nombre = $persona->getAttribute('nombre_usuario') ?? $persona->getAttribute('nombre_estudiante') ?? '';
        $apellido = $persona->getAttribute('apellido') ?? $persona->getAttribute('apellido_estudiante') ?? '';

        return trim($nombre.' '.$apellido);
    }

    /**
     * Bloquea la tabla para calcular `id_notificacion` sin carreras.
     *
     * `LOCK TABLE` es de PostgreSQL; en SQLite (pruebas) se omite.
     */
    private function bloquearTabla(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('LOCK TABLE notificacion_docente IN SHARE ROW EXCLUSIVE MODE');
        }
    }
}
