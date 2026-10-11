<?php

/**
 * @file    NotificacionService.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description
 * Servicio de dominio de las notificaciones "Posible Tramposo" (HU 12).
 *
 * - `notificarPosibleTramposo()` crea el aviso para el docente cuando un
 *   auxiliar registra a un estudiante como sospechoso. El aviso referencia el
 *   caso (`central_riesgo`) y el curso del docente; el resto de los datos
 *   (auxiliar, estudiante, examen, materia, fecha/hora) se derivan de esas
 *   relaciones al listar, así que no se duplican ni se desincronizan.
 * - `listarPara()` devuelve las notificaciones no ocultas del usuario
 *   autenticado, de la más reciente a la más antigua, con el tiempo transcurrido
 *   en español. Un auxiliar nunca recibe notificaciones "Posible Tramposo".
 * - `ocultar()` hace el borrado lógico de una notificación propia.
 *
 * @see  App\Services\Notificacion\TiempoTranscurrido
 * @see  App\Policies\NotificacionDocentePolicy
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: creación del servicio (#HU12).
 */

namespace App\Services\Notificacion;

use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\NotificacionDocente;
use App\Models\Rol;
use App\Models\Usuario;
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
                'estado' => self::ESTADO_RECIBIDO,
            ]);
        });
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
            ->whereHas('curso', fn (Builder $query): Builder => $query->where('sis_doc', $usuario->id_usuario))
            ->with([
                'curso',
                'centralRiesgo.estudiante',
                'centralRiesgo.examen.tipoExamen',
                'centralRiesgo.registrador',
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
            ->sortByDesc(fn (NotificacionDocente $notificacion): int => $notificacion->centralRiesgo?->fecha_registro?->getTimestamp() ?? 0)
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
        $notificacion = NotificacionDocente::query()->with('curso')->findOrFail($idNotificacion);

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
        $registro = $notificacion->centralRiesgo;
        $fecha = $registro?->fecha_registro;
        $auxiliar = $registro?->registrador;
        $estudiante = $registro?->estudiante;
        $examen = $registro?->examen;
        $materia = (string) ($notificacion->curso->nombre_curso ?? $registro?->materia() ?? '');
        $nombreExamen = $examen?->tipoExamen->nombre_tipo_examen ?? ($examen !== null ? 'Examen #'.$examen->id_examen : '');

        return [
            'id' => (int) $notificacion->id_notificacion,
            'titulo' => self::TITULO_POSIBLE_TRAMPOSO,
            'auxiliar' => $this->nombreCompleto($auxiliar),
            'estudiante' => $this->nombreCompleto($estudiante),
            'examen' => $nombreExamen,
            'materia' => $materia,
            'fecha_registro' => $fecha?->toDateTimeString(),
            'hace' => $fecha !== null ? $this->tiempo->enEspanol($fecha) : '',
            'referencia' => (int) $registro->id_registro,
            'detalle_href' => $registro !== null ? route('incidencias.detalle', $registro->id_registro) : '#',
            'descripcion' => sprintf(
                'El auxiliar %s registró al estudiante %s como posible tramposo en el examen de %s de la materia %s.',
                $this->nombreCompleto($auxiliar),
                $this->nombreCompleto($estudiante),
                $nombreExamen,
                $materia,
            ),
        ];
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
