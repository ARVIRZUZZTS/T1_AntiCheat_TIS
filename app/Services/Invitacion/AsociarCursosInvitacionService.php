<?php

/**
 * @file    AsociarCursosInvitacionService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-23
 *
 * @description
 * Invitaciones recibidas a un examen compartido (HU 132): listar, aceptar
 * asociando el examen a cursos propios, asociar una ya aceptada y rechazar.
 *
 * Un examen compartido es el mismo examen para los docentes que lo comparten;
 * asociarlo a cursos del invitado sirve para que los estudiantes de esos
 * cursos entren a las aulas del examen y para que lo vean los auxiliares de
 * esos cursos. Al aceptar:
 *
 *  1. la invitacion pasa a "aceptado";
 *  2. el invitado recibe su configuracion del examen, copiada de la de quien
 *     invito (o la del creador, o la del examen), como hacia
 *     ConfiguracionExamenDocenteService::crearPara();
 *  3. el examen queda en los cursos elegidos (`examen_curso`) y se inscriben
 *     sus estudiantes (`estudiante_examen`);
 *  4. se notifica a quien invito.
 *
 * Todo va en una transaccion y con consultas de conjunto (INSERT ... SELECT):
 * cada viaje a la base remota pesa (~400 ms) y antes eran mas de 40 viajes
 * (uno por estudiante, por regla, por material...); ahora son una docena,
 * sin importar cuantos estudiantes o reglas haya.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 132).
 * - 2026-11-23  [Valery D. Ortuno P]  perf: aceptar y asociar en un solo paso y
 *   con consultas de conjunto; rechazar liviano; una sola lista con las
 *   pendientes y las aceptadas que aun no tienen curso.
 *
 * @see  App\Livewire\Invitaciones\Index
 * @see  App\Services\Examen\ConfiguracionExamenDocenteService::crearPara()
 */

namespace App\Services\Invitacion;

use App\Enums\CursoEstado;
use App\Enums\EstadoEstudianteExamen;
use App\Enums\InvitacionEstado;
use App\Enums\TipoExamen;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsociarCursosInvitacionService
{
    public const MENSAJE_SIN_CURSOS = 'Elige al menos uno de tus cursos.';

    public const MENSAJE_CURSO_INVALIDO = 'Elige solo tus cursos activos.';

    public const MENSAJE_NO_EXISTE = 'La invitacion ya no existe.';

    public const MENSAJE_NO_PENDIENTE = 'Esta invitacion ya fue respondida.';

    public const MENSAJE_NO_ACEPTADA = 'La invitacion todavia no fue aceptada.';

    public const MENSAJE_ARCHIVADO = 'El examen esta en un curso archivado y ya no se puede modificar.';

    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * Invitaciones del docente para la pantalla "Aceptar invitacion": las
     * pendientes y las aceptadas cuyo examen aun no esta en un curso suyo, en
     * una sola consulta.
     *
     * @return list<array{id: int, estado: string, examen: string, fecha_examen: ?string, curso: ?string, invitador: string, enviada_en: ?string}>
     */
    public function listar(Usuario $docente): array
    {
        $idDocente = (int) $docente->id_usuario;

        return DB::table('invitacion_examen_compartido as i')
            ->join('examen as e', 'e.id_examen', '=', 'i.id_examen')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->join('usuario as u', 'u.id_usuario', '=', 'i.id_docente_invitador')
            ->where('i.id_docente_invitado', $idDocente)
            ->where(fn ($consulta) => $consulta
                ->where('i.estado', InvitacionEstado::Pendiente->value)
                ->orWhere(fn ($aceptadas) => $aceptadas
                    ->where('i.estado', InvitacionEstado::Aceptada->value)
                    ->whereNotExists(fn ($curso) => $curso->from('examen_curso as ec')
                        ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                        ->whereColumn('ec.id_examen', 'i.id_examen')
                        ->where('c.sis_doc', $idDocente)
                        ->selectRaw('1'))))
            ->orderByDesc('i.created_at')
            ->selectRaw(
                'i.id_invitacion, i.estado, i.created_at, e.id_examen, e.nombre_examen, e.fecha, e.hora_inicio,'
                .' t.nombre_tipo_examen, u.nombre_usuario, u.apellido,'
                .' (select c.nombre_curso from examen_curso ec join curso c on c.id_curso = ec.id_curso'
                .' where ec.id_examen = e.id_examen and c.sis_doc = i.id_docente_invitador order by c.nombre_curso limit 1) as curso'
            )
            ->get()
            ->map(fn (object $fila): array => [
                'id' => (int) $fila->id_invitacion,
                'estado' => (string) $fila->estado,
                'examen' => (string) ($fila->nombre_examen
                    ?? TipoExamen::etiquetaDe($fila->nombre_tipo_examen)
                    ?? 'Examen #'.$fila->id_examen),
                'fecha_examen' => $fila->fecha
                    ? Carbon::parse($fila->fecha)->format('d/m/Y').($fila->hora_inicio ? ' '.substr((string) $fila->hora_inicio, 0, 5) : '')
                    : null,
                'curso' => $fila->curso,
                'invitador' => trim($fila->nombre_usuario.' '.$fila->apellido),
                'enviada_en' => $fila->created_at ? Carbon::parse($fila->created_at)->format('d/m/Y') : null,
            ])
            ->all();
    }

    /**
     * Cursos activos propios (no archivados ni finalizados).
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function cursosDisponibles(Usuario $docente): array
    {
        return DB::table('curso')
            ->where('sis_doc', $docente->id_usuario)
            ->where('estado', CursoEstado::EnCurso->value)
            ->orderBy('nombre_curso')
            ->get(['id_curso', 'nombre_curso'])
            ->map(fn (object $curso): array => ['id' => (int) $curso->id_curso, 'nombre' => (string) $curso->nombre_curso])
            ->all();
    }

    /**
     * Acepta la invitacion y asocia el examen a los cursos elegidos.
     *
     * @param  list<int>  $idsCursos
     *
     * @throws ValidationException
     */
    public function aceptar(Usuario $docente, int $idInvitacion, array $idsCursos): void
    {
        $idsCursos = $this->idsLimpios($idsCursos);

        DB::transaction(function () use ($docente, $idInvitacion, $idsCursos): void {
            $invitacion = $this->invitacionBloqueada($docente, $idInvitacion);

            if ($invitacion->estado !== InvitacionEstado::Pendiente->value) {
                $this->rechazarCon('invitacion', self::MENSAJE_NO_PENDIENTE);
            }

            $this->exigirCursos($docente, $idsCursos);

            DB::table('invitacion_examen_compartido')
                ->where('id_invitacion', $idInvitacion)
                ->update(['estado' => InvitacionEstado::Aceptada->value, 'updated_at' => now()]);

            $idConfiguracion = $invitacion->config_propia !== null
                ? (int) $invitacion->config_propia
                : $this->crearConfiguracion($invitacion, (int) $docente->id_usuario);

            $this->vincular((int) $invitacion->id_examen, $idConfiguracion, $idsCursos);

            $this->notificaciones->notificarRespuestaInvitacion(
                (new InvitacionExamenCompartido)->forceFill([
                    'id_invitacion' => $idInvitacion,
                    'id_docente_invitador' => (int) $invitacion->id_docente_invitador,
                    'id_docente_invitado' => (int) $docente->id_usuario,
                    'id_examen' => (int) $invitacion->id_examen,
                ])
            );

            $this->olvidarMonitor((int) $invitacion->id_examen, (int) $docente->id_usuario);
        });
    }

    /**
     * Asocia el examen de una invitacion ya aceptada a los cursos elegidos.
     *
     * @param  list<int>  $idsCursos
     *
     * @throws ValidationException
     */
    public function asociar(Usuario $docente, int $idInvitacion, array $idsCursos): void
    {
        $idsCursos = $this->idsLimpios($idsCursos);

        DB::transaction(function () use ($docente, $idInvitacion, $idsCursos): void {
            $invitacion = $this->invitacionBloqueada($docente, $idInvitacion);

            if ($invitacion->estado !== InvitacionEstado::Aceptada->value) {
                $this->rechazarCon('invitacion', self::MENSAJE_NO_ACEPTADA);
            }

            $this->exigirCursos($docente, $idsCursos);

            $idConfiguracion = $invitacion->config_propia !== null
                ? (int) $invitacion->config_propia
                : $this->crearConfiguracion($invitacion, (int) $docente->id_usuario);

            $this->vincular((int) $invitacion->id_examen, $idConfiguracion, $idsCursos);
            $this->olvidarMonitor((int) $invitacion->id_examen, (int) $docente->id_usuario);
        });
    }

    /**
     * Rechaza una invitacion pendiente y avisa a quien invito.
     *
     * @throws ValidationException
     */
    public function rechazar(Usuario $docente, int $idInvitacion): void
    {
        DB::transaction(function () use ($docente, $idInvitacion): void {
            $invitacion = $this->invitacionBloqueada($docente, $idInvitacion);

            if ($invitacion->estado !== InvitacionEstado::Pendiente->value) {
                $this->rechazarCon('invitacion', self::MENSAJE_NO_PENDIENTE);
            }

            DB::table('invitacion_examen_compartido')
                ->where('id_invitacion', $idInvitacion)
                ->update(['estado' => InvitacionEstado::Rechazada->value, 'updated_at' => now()]);

            $this->notificaciones->notificarRespuestaInvitacion(
                (new InvitacionExamenCompartido)->forceFill([
                    'id_invitacion' => $idInvitacion,
                    'id_docente_invitador' => (int) $invitacion->id_docente_invitador,
                    'id_docente_invitado' => (int) $docente->id_usuario,
                    'id_examen' => (int) $invitacion->id_examen,
                ])
            );
        });
    }

    /**
     * La invitacion del docente, bloqueada, con lo necesario para responder en
     * la misma consulta: configuracion propia, la de quien invito, la del
     * creador y si el examen tiene un curso archivado.
     *
     * @throws ValidationException
     */
    private function invitacionBloqueada(Usuario $docente, int $idInvitacion): object
    {
        $invitacion = DB::table('invitacion_examen_compartido as i')
            ->join('examen as e', 'e.id_examen', '=', 'i.id_examen')
            ->where('i.id_invitacion', $idInvitacion)
            ->where('i.id_docente_invitado', $docente->id_usuario)
            ->selectRaw(
                'i.id_invitacion, i.id_examen, i.id_docente_invitador, i.estado,'
                .' (select id_configuracion_examen from configuracion_examen_docente where id_examen = i.id_examen and id_docente = i.id_docente_invitado) as config_propia,'
                .' (select id_configuracion_examen from configuracion_examen_docente where id_examen = i.id_examen and id_docente = i.id_docente_invitador) as config_invitador,'
                .' (select id_configuracion_examen from configuracion_examen_docente where id_examen = i.id_examen and id_docente = e.creador) as config_creador,'
                .' exists (select 1 from examen_curso ec join curso c on c.id_curso = ec.id_curso where ec.id_examen = i.id_examen and c.estado = ?) as archivado',
                [CursoEstado::Archivado->value],
            )
            ->lockForUpdate()
            ->first();

        if ($invitacion === null) {
            $this->rechazarCon('invitacion', self::MENSAJE_NO_EXISTE);
        }

        if ((bool) $invitacion->archivado) {
            $this->rechazarCon('invitacion', self::MENSAJE_ARCHIVADO);
        }

        return $invitacion;
    }

    /**
     * Crea la configuracion del invitado copiando la de quien invito (o la
     * del creador, o la del examen): reglas, materiales y los escritos a mano.
     */
    private function crearConfiguracion(object $invitacion, int $idDocente): int
    {
        $idExamen = (int) $invitacion->id_examen;
        $idConfiguracion = (int) DB::table('configuracion_examen_docente')->insertGetId([
            'id_examen' => $idExamen,
            'id_docente' => $idDocente,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_configuracion_examen');

        $origen = $invitacion->config_invitador ?? $invitacion->config_creador;

        if ($origen !== null) {
            $origen = (int) $origen;
            $this->copiar('configuracion_examen_norma', ['id_configuracion_examen', 'id_norma'],
                DB::table('configuracion_examen_norma')->where('id_configuracion_examen', $origen)
                    ->selectRaw('?, id_norma', [$idConfiguracion]));
            $this->copiar('configuracion_examen_material', ['id_configuracion_examen', 'id_material'],
                DB::table('configuracion_examen_material')->where('id_configuracion_examen', $origen)
                    ->selectRaw('?, id_material', [$idConfiguracion]));
            $this->copiar('configuracion_norma_personalizada', ['id_configuracion_examen', 'numero_norma', 'descripcion_norma'],
                DB::table('configuracion_norma_personalizada')->where('id_configuracion_examen', $origen)
                    ->selectRaw('?, numero_norma, descripcion_norma', [$idConfiguracion]));
            $this->copiar('configuracion_material_personalizado', ['id_configuracion_examen', 'numero_material', 'descripcion_material'],
                DB::table('configuracion_material_personalizado')->where('id_configuracion_examen', $origen)
                    ->selectRaw('?, numero_material, descripcion_material', [$idConfiguracion]));
        } else {
            $this->copiar('configuracion_examen_norma', ['id_configuracion_examen', 'id_norma'],
                DB::table('examen_norma')->where('id_examen', $idExamen)
                    ->selectRaw('?, id_norma', [$idConfiguracion]));
            $this->copiar('configuracion_examen_material', ['id_configuracion_examen', 'id_material'],
                DB::table('examen_material_permitido')->where('id_examen', $idExamen)
                    ->selectRaw('?, id_material_permitido', [$idConfiguracion]));
            $this->copiar('configuracion_norma_personalizada', ['id_configuracion_examen', 'numero_norma', 'descripcion_norma'],
                DB::table('norma_personalizada')->where('id_examen', $idExamen)
                    ->selectRaw('?, numero_norma, descripcion_norma', [$idConfiguracion]));
            $this->copiar('configuracion_material_personalizado', ['id_configuracion_examen', 'numero_material', 'descripcion_material'],
                DB::table('material_personalizado')->where('id_examen', $idExamen)
                    ->selectRaw('?, numero_material, descripcion_material', [$idConfiguracion]));
        }

        return $idConfiguracion;
    }

    /** @param list<string> $columnas */
    private function copiar(string $tabla, array $columnas, $consulta): void
    {
        DB::table($tabla)->insertUsing($columnas, $consulta);
    }

    /**
     * Pone el examen en los cursos que aun no lo tienen e inscribe a sus
     * estudiantes con la configuracion del docente (los que ya estaban
     * inscritos con ella no se repiten; un estudiante en dos de los cursos
     * queda una sola vez, en el primero).
     *
     * @param  list<int>  $idsCursos
     */
    private function vincular(int $idExamen, int $idConfiguracion, array $idsCursos): void
    {
        DB::table('examen_curso')->insertUsing(
            ['id_examen', 'id_curso'],
            DB::table('curso as c')
                ->whereIn('c.id_curso', $idsCursos)
                ->whereNotExists(fn ($ya) => $ya->from('examen_curso as ec')
                    ->where('ec.id_examen', $idExamen)
                    ->whereColumn('ec.id_curso', 'c.id_curso')
                    ->selectRaw('1'))
                ->selectRaw('?, c.id_curso', [$idExamen]),
        );

        $pendientes = DB::table('estudiante_curso as ecur')
            ->whereIn('ecur.id_curso', $idsCursos)
            ->whereNotExists(fn ($ya) => $ya->from('estudiante_examen as ee')
                ->where('ee.id_examen', $idExamen)
                ->where('ee.id_configuracion_examen', $idConfiguracion)
                ->whereColumn('ee.sis_estudiante', 'ecur.sis_estudiante')
                ->selectRaw('1'))
            ->groupBy('ecur.sis_estudiante')
            ->selectRaw('ecur.sis_estudiante, min(ecur.id_curso) as id_curso');

        DB::table('estudiante_examen')->insertUsing(
            ['id_estudiante_examen', 'sis_estudiante', 'id_examen', 'id_curso', 'id_configuracion_examen', 'estado'],
            DB::query()
                ->fromSub($pendientes, 'x')
                ->selectRaw(
                    '(select coalesce(max(id_estudiante_examen), 0) from estudiante_examen)'
                    .' + row_number() over (order by x.sis_estudiante), x.sis_estudiante, ?, x.id_curso, ?, ?',
                    [$idExamen, $idConfiguracion, EstadoEstudianteExamen::Habilitado->value],
                ),
        );
    }

    /**
     * Valida que los cursos sean activos y del docente.
     *
     * @param  list<int>  $idsCursos
     *
     * @throws ValidationException
     */
    private function exigirCursos(Usuario $docente, array $idsCursos): void
    {
        if ($idsCursos === []) {
            $this->rechazarCon('cursos', self::MENSAJE_SIN_CURSOS);
        }

        $validos = DB::table('curso')
            ->whereIn('id_curso', $idsCursos)
            ->where('sis_doc', $docente->id_usuario)
            ->where('estado', CursoEstado::EnCurso->value)
            ->count();

        if ($validos !== count($idsCursos)) {
            $this->rechazarCon('cursos', self::MENSAJE_CURSO_INVALIDO);
        }
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<int>
     */
    private function idsLimpios(array $ids): array
    {
        return collect($ids)->map(fn ($id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique()->values()->all();
    }

    private function olvidarMonitor(int $idExamen, int $idDocente): void
    {
        Cache::forget(MonitorEnVivo::CACHE_INFORME_PREFIJO.$idExamen.'.docente.'.$idDocente);
    }

    /** @throws ValidationException */
    private function rechazarCon(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
