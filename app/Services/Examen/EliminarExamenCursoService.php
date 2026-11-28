<?php

/**
 * @file    EliminarExamenCursoService.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-16
 *
 * @updated 2026-11-24
 *
 * @description
 * Elimina lógicamente un examen de un curso: revoca la vinculación
 * `examen_curso` del curso elegido y borra las inscripciones de ese curso
 * (`estudiante_examen` y sus ambientes), sin tocar la fila del examen ni los
 * datos de otros cursos ni de otros docentes (configuraciones, invitaciones,
 * ingresos e incidencias quedan intactos).
 *
 * Al desvincularlo avisa a los auxiliares activos del curso y a los docentes
 * que comparten el examen (no a quien elimina); el aviso se crea dentro de
 * la transacción y solo cuando el borrado es manual (no `$forzar`).
 *
 * Si el examen estaba compartido en varios cursos, solo se desvincula el
 * curso pedido y el examen sigue vivo para los demás; si era su único curso,
 * el examen queda sin cursos (huérfano) y los listados lo excluyen.
 *
 * Dos guardas impiden perder datos con historia:
 *  1. El examen ya inició o finalizó (misma regla de ventana horaria que
 *     ListarExamenesCursoService::resolverEstado).
 *  2. El examen tiene ingresos (registro_asistencia) o incidencias
 *     (central_riesgo) registrados.
 *
 * Eliminar un curso completo necesita desvincular sus exámenes aunque tengan
 * historia; para ese caso el parámetro `$forzar` omite las dos guardas y no
 * genera avisos de examen eliminado. El examen sigue vivo para otros cursos
 * con los que se comparta.
 *
 * @changelog
 * - 2026-11-16  [OchoaCesar]  feat: creación inicial del servicio.
 * - 2026-11-16  [Alex Candia]  feat: parámetro $forzar para desvincular
 *   exámenes desde la eliminación de un curso.
 * - 2026-11-24  [OchoaCesar]  feat: aviso «Examen eliminado» a los auxiliares
 *   activos del curso y a los docentes que comparten el examen (solo en el
 *   borrado manual, no al eliminar el curso completo).
 *
 * @see  App\Http\Controllers\Api\ConfiguracionExamenDocenteController
 * @see  App\Services\Curso\EliminarCursoService
 * @see  App\Services\Examen\ListarExamenesCursoService
 * @see  App\Services\Examen\NotificarEliminacionExamenService
 * @see  resources/views/partials/examenes-curso.blade.php
 */

namespace App\Services\Examen;

use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\Curso;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use App\Models\Usuario;
use App\Services\Curso\AsegurarCursoEditableService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EliminarExamenCursoService
{
    /** Mensaje cuando la ventana horaria del examen ya arrancó. */
    public const MENSAJE_NO_INICIADO = 'No se puede eliminar un examen que ya inició o finalizó:';

    /** Mensaje cuando el examen tiene ingresos o incidencias. */
    public const MENSAJE_CON_DATOS = 'No se puede eliminar un examen con ingresos o incidencias registradas:';

    public function __construct(
        private readonly AsegurarCursoEditableService $editable,
        private readonly NotificarEliminacionExamenService $notificaciones,
    ) {}

    /**
     * Desvincula el examen del curso y limpia las inscripciones de ese curso.
     *
     * @param  Usuario  $docente  Docente autenticado (debe gestionar el curso).
     * @param  Examen  $examen  Examen a eliminar del curso.
     * @param  Curso  $curso  Curso del que se desvincula el examen.
     * @param  bool  $forzar  Omitir las guardas de historia al borrar el curso completo.
     *
     * @throws ValidationException Si el examen ya inició o tiene datos y no se fuerza.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-16
     */
    public function ejecutar(Usuario $docente, Examen $examen, Curso $curso, bool $forzar = false): void
    {
        Gate::forUser($docente)->authorize('gestionar', $curso);

        if (! $forzar) {
            $this->editable->asegurarCursoEditable($curso, 'eliminar');
            $this->editable->asegurarExamenEditable($examen, 'eliminar');
        }

        $vinculado = $examen->cursos()
            ->where('curso.id_curso', $curso->id_curso)
            ->exists();

        if (! $vinculado) {
            abort(404, 'El examen no pertenece a este curso.');
        }

        if (! $forzar && $this->yaInicio($examen)) {
            throw ValidationException::withMessages([
                'eliminar' => self::MENSAJE_NO_INICIADO,
            ]);
        }

        if (! $forzar && $this->tieneIngresosIncidencias($examen)) {
            throw ValidationException::withMessages([
                'eliminar' => self::MENSAJE_CON_DATOS,
            ]);
        }

        DB::transaction(function () use ($docente, $examen, $curso, $forzar): void {
            $this->limpiarInscripciones($examen, $curso);

            $examen->cursos()->detach($curso->id_curso);

            // La composicion del informe de monitoreo cambia al perder
            // inscripciones: se invalida el caché de cada docente participante.
            $examenes = MonitorEnVivo::CACHE_INFORME_PREFIJO.$examen->id_examen;

            foreach ($examen->configuracionesDocentes()->pluck('id_docente') as $idDocente) {
                Cache::forget($examenes.'.docente.'.$idDocente);
            }

            // Aviso dentro de la transacción (atomico, como al editar): solo
            // en el borrado manual; al eliminar el curso completo no aplica.
            if (! $forzar) {
                $this->notificaciones->notificar($examen, $curso, $docente);
            }
        });
    }

    /**
     * Indica si la ventana horaria del examen ya arrancó (en curso o
     * finalizado). Un examen sin fecha nunca empezó: es programado.
     *
     * @param  Examen  $examen  Examen a evaluar.
     * @return bool True si ya inició o finalizó.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-16
     */
    private function yaInicio(Examen $examen): bool
    {
        if ($examen->fecha === null) {
            return false;
        }

        $inicio = Carbon::parse($examen->fecha.' '.($examen->hora_inicio ?? '00:00:00'));

        return Carbon::now()->gte($inicio);
    }

    /**
     * Indica si el examen tiene ingresos (registro_asistencia) o incidencias
     * (central_riesgo) registrados: con datos no se puede eliminar.
     *
     * @param  Examen  $examen  Examen a evaluar.
     * @return bool True si tiene ingresos o incidencias.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-16
     */
    private function tieneIngresosIncidencias(Examen $examen): bool
    {
        return RegistroAsistencia::query()
            ->where('id_examen', $examen->id_examen)
            ->exists()
            || $examen->incidencias()->exists();
    }

    /**
     * Borra las inscripciones del examen en el curso elegido, con sus ambientes.
     * `estudiante_examen_ambiente` no tiene ON DELETE CASCADE, así que se
     * limpia primero. Solo se toca el curso pedido: las inscripciones de los
     * demás cursos del examen quedan intactas.
     *
     * @param  Examen  $examen  Examen desvinculado.
     * @param  Curso  $curso  Curso del que se borran las inscripciones.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-16
     */
    private function limpiarInscripciones(Examen $examen, Curso $curso): void
    {
        $ids = EstudianteExamen::query()
            ->where('id_examen', $examen->id_examen)
            ->where('id_curso', $curso->id_curso)
            ->pluck('id_estudiante_examen');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('estudiante_examen_ambiente')
            ->whereIn('id_ee', $ids)
            ->delete();

        EstudianteExamen::query()
            ->whereIn('id_estudiante_examen', $ids)
            ->delete();
    }
}
