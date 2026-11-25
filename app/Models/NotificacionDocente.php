<?php

/**
 * @file    NotificacionDocente.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description
 * Modelo de la tabla `notificacion_docente`: aviso del sistema dirigido a un
 * usuario (docente o auxiliar) por su `id_usuario`. Los tipos vigentes son:
 *
 *   - `posible_tramposo` (HU 12): un auxiliar registró al estudiante como
 *     sospechoso; va solo al docente de la materia del caso.
 *   - `central_riesgo` (HU 6): el estudiante quedó registrado o confirmado
 *     como tramposo; se genera uno por cada materia activa del estudiante.
 *   - `examen_modificado`: cambiaron los ambientes, la fecha o la hora de un
 *     examen; va a los auxiliares asignados y, si está compartido, a los
 *     docentes involucrados. Los cambios se guardan en `detalles`.
 *
 * Cada fila referencia el registro del caso en `central_riesgo` y el curso
 * (materia) del docente; el título, el auxiliar, el estudiante, el examen
 * y la fecha/hora se derivan de esas relaciones al consultar, no se duplican.
 *
 * La tabla no tiene secuencia: `id_notificacion` lo calcula la aplicación al
 * crear (max + 1), igual que `central_riesgo` y `examen`.
 *
 * `oculta` es el borrado lógico que usa la T2: ocultar una notificación no toca
 * el caso (`central_riesgo` sigue "En revisión") ni lo que ven otros usuarios.
 *
 * @see  App\Services\Notificacion\NotificacionService
 * @see  App\Models\CentralRiesgo
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: creación inicial del modelo (#HU12).
 * - 2026-10-11  [David E. Chavez T.]  feat: columna `tipo` para distinguir los
 *   avisos "central_riesgo" de la HU 6 (#HU6).
 * - 2026-11-22  [T1]  feat: columna `fecha_evento` para avisos autogenerados sin
 *   entidad de origen, como el respaldo automático de la base de datos (#HU33).
 * - 2026-11-24  [Equipo T1]  feat: columna `detalles` para el aviso de cambios
 *   de ambientes, fecha u hora de un examen.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id_notificacion
 * @property ?int $id_central_riesgo
 * @property ?int $id_curso
 * @property ?int $id_docente_destinatario
 * @property ?int $id_invitacion
 * @property string $tipo
 * @property string $estado
 * @property bool $oculta
 * @property ?Carbon $fecha_evento
 * @property ?array<string, mixed> $detalles
 */
class NotificacionDocente extends Model
{
    protected $table = 'notificacion_docente';

    protected $primaryKey = 'id_notificacion';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'id_notificacion',
        'id_central_riesgo',
        'id_curso',
        'id_docente_destinatario',
        'id_invitacion',
        'tipo',
        'estado',
        'oculta',
        'fecha_evento',
        'detalles',
    ];

    protected function casts(): array
    {
        return [
            'oculta' => 'boolean',
            'fecha_evento' => 'datetime',
            'detalles' => 'array',
        ];
    }

    /** @return BelongsTo<CentralRiesgo, $this> */
    public function centralRiesgo(): BelongsTo
    {
        return $this->belongsTo(CentralRiesgo::class, 'id_central_riesgo', 'id_registro');
    }

    /** @return BelongsTo<Curso, $this> */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_docente_destinatario', 'id_usuario');
    }

    /** @return BelongsTo<InvitacionExamenCompartido, $this> */
    public function invitacion(): BelongsTo
    {
        return $this->belongsTo(InvitacionExamenCompartido::class, 'id_invitacion', 'id_invitacion');
    }
}
