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
 * - `notificarCambioExamen()` crea el aviso "Cambios en un examen" cuando se
 *   editan los ambientes, la fecha o la hora. Va a los auxiliares asignados y,
 *   si el examen está compartido, a los docentes involucrados; el detalle de lo
 *   cambiado se guarda en `detalles` para no desincronizarlo con el examen.
 * - `notificarExamenEliminado()` crea el aviso "Examen eliminado" cuando un
 *   docente desvincula un examen de un curso: va a los auxiliares activos de
 *   ese curso y a los docentes que aceptaron compartir el examen. El nombre
 *   del examen, el curso y quien lo eliminó se guardan en `detalles`.
 * - `listarPara()` devuelve las notificaciones no ocultas del usuario
 *   autenticado (docente o auxiliar), de la más reciente a la más antigua, con
 *   el tiempo transcurrido en español. Un auxiliar ve solo los avisos que se le
 *   dirigen por su `id_usuario`.
 * - `contarNuevas()` y `marcarTodasLeidas()` separan el badge de avisos nuevos
 *   del historial: solo cuentan/marcan las filas `recibido` y no ocultas.
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
 * - 2026-11-23  [Valery D. Ortuno P]  perf: `notificarInvitacionExamen()`
 *   inserta el aviso con su id en el mismo INSERT y no vuelve a bloquear la
 *   tabla si quien llama ya la bloqueo (`$tablaBloqueada`).
 *   `notificarRespuestaInvitacion()` reapunta el aviso con un solo UPDATE y
 *   solo bloquea e inserta si no existia.
 * - 2026-11-24  [Equipo T1]  fix: el badge cuenta solo avisos `recibido` y al
 *   abrir la bandeja se marcan todos como `visto`.
 * - 2026-11-24  [Equipo T1]  feat: aviso de cambios de ambientes, fecha u hora
 *   de un examen, visible también para auxiliares asignados.
 * - 2026-11-24  [OchoaCesar]  feat: aviso «Examen eliminado» (`examen_eliminado`)
 *   para los auxiliares activos del curso y los docentes que comparten el
 *   examen, generado al desvincularlo de un curso.
 */

namespace App\Services\Notificacion;

use App\Enums\CursoEstado;
use App\Enums\InvitacionEstado;
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

    /** Título del aviso que recibe el invitador cuando responden su invitación. */
    public const TITULO_RESPUESTA_INVITACION = 'Respuesta a tu invitación de examen';

    /** Título del aviso informativo del respaldo automático de la base de datos (HU 33). */
    public const TITULO_RESPALDO_BD = 'Respaldo automático de la base de datos';

    /** Título del aviso de cambios de ambientes, fecha u hora de un examen. */
    public const TITULO_CAMBIO_EXAMEN = 'Cambios en un examen';

    /** Título del aviso cuando el docente elimina un examen de un curso. */
    public const TITULO_EXAMEN_ELIMINADO = 'Examen eliminado';

    /**
     * Tipos de aviso (columna `tipo` de `notificacion_docente`). La pantalla
     * pinta una tarjeta distinta según el valor.
     */
    public const TIPO_POSIBLE_TRAMPOSO = 'posible_tramposo';

    public const TIPO_CENTRAL_RIESGO = 'central_riesgo';

    public const TIPO_INVITACION_EXAMEN = 'invitacion_examen';

    /** Aviso para el invitador: el invitado aceptó o rechazó su invitación. */
    public const TIPO_RESPUESTA_INVITACION = 'respuesta_invitacion';

    /** Aviso informativo (solo lectura) del respaldo automático de la base de datos. */
    public const TIPO_RESPALDO_BD = 'respaldo_bd';

    /** Aviso de cambios de ambientes, fecha u hora de un examen ya programado. */
    public const TIPO_CAMBIO_EXAMEN = 'examen_modificado';

    /** Aviso cuando el docente elimina un examen de uno de sus cursos. */
    public const TIPO_EXAMEN_ELIMINADO = 'examen_eliminado';

    /** Estado inicial con el que nace un aviso, del enum `estado_notificacion`. */
    public const ESTADO_RECIBIDO = 'recibido';

    /** Estado de un aviso ya leído por el docente. */
    public const ESTADO_VISTO = 'visto';

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

    /**
     * Aviso al docente invitado. El id (max + 1) sale en el mismo INSERT: un
     * viaje a la base en vez de dos. Con `$tablaBloqueada` quien llama ya
     * bloqueo la tabla dentro de su transaccion y no se vuelve a bloquear.
     */
    public function notificarInvitacionExamen(InvitacionExamenCompartido $invitacion, bool $tablaBloqueada = false): NotificacionDocente
    {
        return DB::transaction(function () use ($invitacion, $tablaBloqueada): NotificacionDocente {
            if (! $tablaBloqueada) {
                $this->bloquearTabla();
            }

            $datos = [
                'id_central_riesgo' => null,
                'id_curso' => null,
                'id_docente_destinatario' => $invitacion->id_docente_invitado,
                'id_invitacion' => $invitacion->id_invitacion,
                'tipo' => self::TIPO_INVITACION_EXAMEN,
                'estado' => self::ESTADO_RECIBIDO,
            ];
            $fila = DB::selectOne(
                'insert into notificacion_docente (id_notificacion, id_central_riesgo, id_curso, id_docente_destinatario, id_invitacion, tipo, estado) '
                .'select coalesce(max(id_notificacion), 0) + 1, ?, ?, ?, ?, ?, ? from notificacion_docente '
                .'returning id_notificacion',
                array_values($datos),
            );

            $aviso = (new NotificacionDocente)->forceFill(['id_notificacion' => (int) $fila->id_notificacion] + $datos);
            $aviso->exists = true;

            return $aviso;
        });
    }

    /**
     * Avisa al docente que invitó que su invitación fue respondida (aceptada o
     * rechazada).
     *
     * Reutiliza el único aviso de la invitación (la tabla tiene un índice único
     * por `id_invitacion`): el aviso ya cumplió su fin con el invitado, así que
     * se reapunta al invitador y cambia de tipo. Si por algún motivo no hubiera
     * aviso previo, se crea uno.
     *
     * @param  InvitacionExamenCompartido  $invitacion  Invitación ya respondida.
     * @return NotificacionDocente Aviso dirigido al invitador.
     */
    public function notificarRespuestaInvitacion(InvitacionExamenCompartido $invitacion): NotificacionDocente
    {
        return DB::transaction(function () use ($invitacion): NotificacionDocente {
            $datos = [
                'id_central_riesgo' => null,
                'id_curso' => null,
                'id_docente_destinatario' => $invitacion->id_docente_invitador,
                'tipo' => self::TIPO_RESPUESTA_INVITACION,
                'estado' => self::ESTADO_RECIBIDO,
                'oculta' => false,
            ];

            // Lo normal es que el aviso exista (lo creo el envio): se reapunta
            // en un solo viaje, sin bloquear la tabla.
            $fila = DB::selectOne(
                'update notificacion_docente set id_central_riesgo = ?, id_curso = ?, id_docente_destinatario = ?, tipo = ?, estado = ?, oculta = ? '
                .'where id_invitacion = ? returning id_notificacion',
                [...array_values($datos), $invitacion->id_invitacion],
            );

            // Sin aviso previo se crea uno (el id max + 1 exige el bloqueo).
            if ($fila === null) {
                $this->bloquearTabla();
                $fila = DB::selectOne(
                    'insert into notificacion_docente (id_notificacion, id_invitacion, id_central_riesgo, id_curso, id_docente_destinatario, tipo, estado, oculta) '
                    .'select coalesce(max(id_notificacion), 0) + 1, ?, ?, ?, ?, ?, ?, ? from notificacion_docente '
                    .'returning id_notificacion',
                    [$invitacion->id_invitacion, ...array_values($datos)],
                );
            }

            $aviso = (new NotificacionDocente)->forceFill(
                ['id_notificacion' => (int) $fila->id_notificacion, 'id_invitacion' => $invitacion->id_invitacion] + $datos
            );
            $aviso->exists = true;

            return $aviso;
        });
    }

    /**
     * Avisa a todos los docentes que se realizó un respaldo automático de la
     * base de datos (HU 33).
     *
     * El aviso es exclusivo para el rol «Docente»: un auxiliar (u otro rol que no
     * sea docente) nunca lo recibe. Es puramente informativo y de solo lectura:
     * no lleva acción, enlace ni botón; solo el mensaje con la fecha y hora
     * exactas del respaldo, que se guarda en `fecha_evento`.
     *
     * Reutiliza la tabla y el cálculo de `id_notificacion` (max + 1 bajo bloqueo)
     * del resto de avisos, por lo que se integra sin tocar el esquema más allá de
     * la columna `fecha_evento`.
     *
     * @param  CarbonInterface  $momento  Fecha y hora de finalización del respaldo.
     * @return array<int, NotificacionDocente> Avisos creados, uno por docente.
     *
     * @author T1 - Tech One SRL
     *
     * @since  2026-11-22
     */
    public function notificarRespaldoBaseDatos(CarbonInterface $momento): array
    {
        $docentes = Usuario::query()
            ->whereHas('roles', fn (Builder $rol): Builder => $rol->where('nombre_rol', Rol::NOMBRE_DOCENTE))
            ->get();

        if ($docentes->isEmpty()) {
            return [];
        }

        return DB::transaction(function () use ($docentes, $momento): array {
            $this->bloquearTabla();

            $siguiente = (int) NotificacionDocente::query()->max('id_notificacion');
            $creados = [];

            foreach ($docentes as $docente) {
                $siguiente++;

                $creados[] = NotificacionDocente::create([
                    'id_notificacion' => $siguiente,
                    'id_docente_destinatario' => $docente->id_usuario,
                    'tipo' => self::TIPO_RESPALDO_BD,
                    'estado' => self::ESTADO_RECIBIDO,
                    'fecha_evento' => $momento,
                ]);
            }

            return $creados;
        });
    }

    /**
     * Avisa a los usuarios involucrados que cambiaron los ambientes, la fecha
     * o la hora de un examen ya programado.
     *
     * La tabla no tiene una entidad propia para este evento: `detalles` guarda
     * el examen, las materias, el editor y el texto de cada cambio, y
     * `fecha_evento` guarda cuándo se hizo. Así el historial muestra lo que se
     * modificó aunque después se vuelva a editar el examen. Se reutiliza
     * `id_docente_destinatario` como columna genérica de usuario (docente o
     * auxiliar), que es un FK a `usuario.id_usuario`.
     *
     * @param  Collection<int, Usuario>  $destinatarios  Usuarios que deben recibir el aviso.
     * @param  array<string, mixed>  $detalles  Examen, materias, editor y cambios.
     * @param  CarbonInterface  $momento  Momento en que se guardaron los cambios.
     * @return array<int, NotificacionDocente> Avisos creados, uno por destinatario.
     */
    public function notificarCambioExamen(
        Collection $destinatarios,
        array $detalles,
        CarbonInterface $momento,
    ): array {
        if ($destinatarios->isEmpty()) {
            return [];
        }

        return DB::transaction(function () use ($destinatarios, $detalles, $momento): array {
            $this->bloquearTabla();

            $siguiente = (int) NotificacionDocente::query()->max('id_notificacion');
            $creados = [];

            foreach ($destinatarios as $destinatario) {
                $siguiente++;

                $creados[] = NotificacionDocente::create([
                    'id_notificacion' => $siguiente,
                    'id_docente_destinatario' => $destinatario->id_usuario,
                    'tipo' => self::TIPO_CAMBIO_EXAMEN,
                    'estado' => self::ESTADO_RECIBIDO,
                    'oculta' => false,
                    'fecha_evento' => $momento,
                    'detalles' => $detalles,
                ]);
            }

            return $creados;
        });
    }

    /**
     * Crea el aviso "Examen eliminado" para cada destinatario. Se llama desde
     * `EliminarExamenCursoService` cuando un docente desvincula un examen de
     * uno de sus cursos, dentro de la transacción del borrado.
     *
     * La columna `id_curso` se guarda para que el aviso se limpie solo si
     * después se elimina el curso completo (`EliminarCursoService`). El texto
     * (examen, curso y quién eliminó) vive en `detalles` y el momento en
     * `fecha_evento`, igual que el aviso de cambios de examen. Se reutiliza
     * `id_docente_destinatario` como columna genérica de usuario (docente o
     * auxiliar), que es un FK a `usuario.id_usuario`.
     *
     * @param  Collection<int, Usuario>  $destinatarios  Auxiliares y docentes que deben recibir el aviso.
     * @param  array<string, mixed>  $detalles  Examen, curso y docente que eliminó.
     * @param  CarbonInterface  $momento  Momento en que se eliminó.
     * @return array<int, NotificacionDocente> Avisos creados, uno por destinatario.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-24
     */
    public function notificarExamenEliminado(
        Collection $destinatarios,
        array $detalles,
        CarbonInterface $momento,
    ): array {
        if ($destinatarios->isEmpty()) {
            return [];
        }

        $idCurso = isset($detalles['curso_id']) ? (int) $detalles['curso_id'] : null;

        return DB::transaction(function () use ($destinatarios, $detalles, $momento, $idCurso): array {
            $this->bloquearTabla();

            $siguiente = (int) NotificacionDocente::query()->max('id_notificacion');
            $creados = [];

            foreach ($destinatarios as $destinatario) {
                $siguiente++;

                $creados[] = NotificacionDocente::create([
                    'id_notificacion' => $siguiente,
                    'id_docente_destinatario' => $destinatario->id_usuario,
                    'id_curso' => $idCurso,
                    'tipo' => self::TIPO_EXAMEN_ELIMINADO,
                    'estado' => self::ESTADO_RECIBIDO,
                    'oculta' => false,
                    'fecha_evento' => $momento,
                    'detalles' => $detalles,
                ]);
            }

            return $creados;
        });
    }

    /**
     * Texto del aviso de respaldo con la fecha y hora exactas.
     *
     * @param  ?CarbonInterface  $fecha  Momento del respaldo.
     * @return string Mensaje informativo para la tarjeta.
     *
     * @author T1 - Tech One SRL
     *
     * @since  2026-11-22
     */
    public static function mensajeRespaldo(?CarbonInterface $fecha): string
    {
        if ($fecha === null) {
            return 'Se realizó un respaldo automático de la base de datos.';
        }

        return sprintf(
            'Se realizó un respaldo automático de la base de datos el %s a las %s.',
            $fecha->format('d/m/Y'),
            $fecha->format('H:i'),
        );
    }

    /**
     * Oculta el aviso de una invitación (borrado lógico). Se usa al cancelar:
     * el invitado deja de ver en su bandeja una invitación que ya no puede
     * responder.
     *
     * @param  int  $idInvitacion  Invitación cuyo aviso se oculta.
     */
    public function ocultarDeInvitacion(int $idInvitacion): void
    {
        NotificacionDocente::query()
            ->where('id_invitacion', $idInvitacion)
            ->update(['oculta' => true]);
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
     * más antigua. Sirve para docentes y auxiliares: cada uno ve solo las filas
     * dirigidas a su `id_usuario`. Si no hay ninguna, devuelve una colección
     * vacía.
     *
     * @param  Usuario  $usuario  Usuario de la sesión.
     * @return Collection<int, array<string, mixed>> Avisos mapeados para la API.
     */
    public function listarPara(Usuario $usuario): Collection
    {
        $notificaciones = $this->consultarPara($usuario)
            ->with([
                'curso',
                'centralRiesgo.estudiante',
                'centralRiesgo.examen.tipoExamen',
                'centralRiesgo.registrador',
                'invitacion.invitador',
                'invitacion.invitado',
                'invitacion.examen.tipoExamen',
                'invitacion.examen.cursos',
            ])
            ->get();

        return $this->preparar($notificaciones);
    }

    /**
     * Cantidad de avisos nuevos (recibidos y no ocultos) del usuario. Las filas
     * ya vistas siguen en el historial, pero no cuentan para el badge.
     */
    public function contarNuevas(Usuario $usuario): int
    {
        return $this->consultarPara($usuario)
            ->where('estado', self::ESTADO_RECIBIDO)
            ->count();
    }

    /**
     * Marca como vistos todos los avisos nuevos del usuario y devuelve cuántos
     * cambiaron. Los ocultos y los ajenos quedan intactos.
     */
    public function marcarTodasLeidas(Usuario $usuario): int
    {
        return $this->consultarPara($usuario)
            ->where('estado', self::ESTADO_RECIBIDO)
            ->update(['estado' => self::ESTADO_VISTO]);
    }

    /**
     * Consulta base de los avisos visibles del usuario: destinatario directo
     * (docente o auxiliar) o, en las filas legacy, el docente dueño del curso.
     *
     * @return Builder<NotificacionDocente>
     */
    private function consultarPara(Usuario $usuario): Builder
    {
        return NotificacionDocente::query()
            ->where('oculta', false)
            ->where(function (Builder $query) use ($usuario): void {
                $query->where('id_docente_destinatario', $usuario->id_usuario)
                    ->orWhere(function (Builder $notificacionLegacy) use ($usuario): void {
                        $notificacionLegacy
                            ->whereNull('id_docente_destinatario')
                            ->whereHas('curso', fn (Builder $curso): Builder => $curso->where('sis_doc', $usuario->id_usuario));
                    });
            });
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

        if ($notificacion->tipo === self::TIPO_RESPUESTA_INVITACION) {
            return $this->mapearRespuestaInvitacion($notificacion);
        }

        if ($notificacion->tipo === self::TIPO_RESPALDO_BD) {
            return $this->mapearRespaldo($notificacion);
        }

        if ($notificacion->tipo === self::TIPO_CAMBIO_EXAMEN) {
            return $this->mapearCambioExamen($notificacion);
        }

        if ($notificacion->tipo === self::TIPO_EXAMEN_ELIMINADO) {
            return $this->mapearExamenEliminado($notificacion);
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

    /**
     * Aviso que ve el invitador cuando el invitado responde: "Ana aceptó/rechazó
     * tu invitación...".
     *
     * @param  NotificacionDocente  $notificacion  Aviso de respuesta, con la
     *                                             invitación y su invitado cargados.
     * @return array<string, mixed> Datos para la pantalla/API.
     */
    private function mapearRespuestaInvitacion(NotificacionDocente $notificacion): array
    {
        $invitacion = $notificacion->invitacion;
        $examen = $invitacion?->examen;
        $invitado = $invitacion?->invitado;
        $tipoExamen = $examen !== null ? $examen->tipoExamen->nombre_tipo_examen : '';
        $materias = $examen === null
            ? []
            : $examen->cursos->pluck('nombre_curso')->filter()->values()->all();
        $detalleMaterias = $materias !== [] ? ' de '.implode(', ', $materias) : '';
        $acepto = $invitacion?->estado === InvitacionEstado::Aceptada;
        $fecha = $invitacion?->updated_at;

        return [
            'id' => (int) $notificacion->id_notificacion,
            'tipo' => self::TIPO_RESPUESTA_INVITACION,
            'titulo' => self::TITULO_RESPUESTA_INVITACION,
            'descripcion' => sprintf(
                '%s %s tu invitación para compartir el examen %s%s.',
                $this->nombreCompleto($invitado),
                $acepto ? 'aceptó' : 'rechazó',
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
                'invitado' => $this->nombreCompleto($invitado),
                'respuesta' => $invitacion?->estado?->value,
            ],
        ];
    }

    /**
     * Mapea el aviso del respaldo automático: solo mensaje informativo con la
     * fecha y hora exactas. No incluye `detalle_href`, enlace ni datos de acción,
     * porque la notificación es estrictamente de solo lectura.
     *
     * @param  NotificacionDocente  $notificacion  Aviso de tipo `respaldo_bd`.
     * @return array<string, mixed> Datos para la pantalla/API.
     */
    private function mapearRespaldo(NotificacionDocente $notificacion): array
    {
        $fecha = $notificacion->fecha_evento;

        return [
            'id' => (int) $notificacion->id_notificacion,
            'tipo' => self::TIPO_RESPALDO_BD,
            'titulo' => self::TITULO_RESPALDO_BD,
            'descripcion' => self::mensajeRespaldo($fecha),
            'fecha_registro' => $fecha?->toDateTimeString(),
            'hace' => $fecha !== null ? $this->tiempo->enEspanol($fecha) : '',
        ];
    }

    /**
     * Mapea el aviso de cambios de examen desde `detalles`. El mensaje es texto
     * plano y no lleva acción: la tarjeta solo informa lo que se modificó.
     *
     * @param  NotificacionDocente  $notificacion  Aviso de tipo `examen_modificado`.
     * @return array<string, mixed> Datos para la pantalla/API.
     */
    private function mapearCambioExamen(NotificacionDocente $notificacion): array
    {
        $detalles = $notificacion->detalles ?? [];
        $examen = (string) ($detalles['examen'] ?? 'Examen');
        $materias = array_values(array_filter((array) ($detalles['materias'] ?? [])));
        $cambios = array_values(array_filter((array) ($detalles['cambios'] ?? [])));
        $detalleMaterias = $materias !== [] ? ' de '.implode(', ', $materias) : '';
        $fecha = $notificacion->fecha_evento;

        return [
            'id' => (int) $notificacion->id_notificacion,
            'tipo' => self::TIPO_CAMBIO_EXAMEN,
            'titulo' => self::TITULO_CAMBIO_EXAMEN,
            'descripcion' => sprintf(
                'El examen de %s%s cambió: %s.',
                $examen,
                $detalleMaterias,
                implode('; ', $cambios),
            ),
            'referencia' => (int) ($detalles['examen_id'] ?? 0),
            'fecha_registro' => $fecha?->toDateTimeString(),
            'hace' => $fecha !== null ? $this->tiempo->enEspanol($fecha) : '',
            'datos' => [
                'examen_id' => (int) ($detalles['examen_id'] ?? 0),
                'examen' => $examen,
                'materias' => $materias,
                'cambios' => $cambios,
                'editor' => (string) ($detalles['editor'] ?? ''),
            ],
        ];
    }

    /**
     * Mapea el aviso de examen eliminado desde `detalles`. El mensaje es texto
     * plano y no lleva acción: la tarjeta solo informa qué examen se eliminó
     * de qué curso y por quién.
     *
     * @param  NotificacionDocente  $notificacion  Aviso de tipo `examen_eliminado`.
     * @return array<string, mixed> Datos para la pantalla/API.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-24
     */
    private function mapearExamenEliminado(NotificacionDocente $notificacion): array
    {
        $detalles = $notificacion->detalles ?? [];
        $examen = (string) ($detalles['examen'] ?? 'Examen');
        $curso = (string) ($detalles['curso'] ?? '');
        $eliminadoPor = (string) ($detalles['eliminado_por'] ?? '');
        $fecha = $notificacion->fecha_evento;

        return [
            'id' => (int) $notificacion->id_notificacion,
            'tipo' => self::TIPO_EXAMEN_ELIMINADO,
            'titulo' => self::TITULO_EXAMEN_ELIMINADO,
            'descripcion' => sprintf(
                'El examen %s fue eliminado del curso %s%s.',
                $examen,
                $curso,
                $eliminadoPor !== '' ? ' por '.$eliminadoPor : '',
            ),
            'referencia' => (int) ($detalles['examen_id'] ?? 0),
            'fecha_registro' => $fecha?->toDateTimeString(),
            'hace' => $fecha !== null ? $this->tiempo->enEspanol($fecha) : '',
            'datos' => [
                'examen_id' => (int) ($detalles['examen_id'] ?? 0),
                'examen' => $examen,
                'curso' => $curso,
                'eliminado_por' => $eliminadoPor,
            ],
        ];
    }

    private function fechaDe(NotificacionDocente $notificacion): ?CarbonInterface
    {
        return match ($notificacion->tipo) {
            self::TIPO_INVITACION_EXAMEN => $notificacion->invitacion?->created_at,
            self::TIPO_RESPUESTA_INVITACION => $notificacion->invitacion?->updated_at,
            self::TIPO_RESPALDO_BD => $notificacion->fecha_evento,
            self::TIPO_CAMBIO_EXAMEN => $notificacion->fecha_evento,
            self::TIPO_EXAMEN_ELIMINADO => $notificacion->fecha_evento,
            default => $notificacion->centralRiesgo?->fecha_registro,
        };
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
