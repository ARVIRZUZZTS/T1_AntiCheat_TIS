<?php

/**
 * @file    CentralRiesgo.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Modelo de la tabla `central_riesgo`: mapea las infracciones registradas
 * durante un examen (tramposo, sospechoso, pendiente, aula equivocada).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 */

namespace App\Models;

use App\Enums\TipoInfraccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_registro
 * @property int $id_ingreso
 * @property int $id_registrador
 * @property ?string $detalle_motivo
 * @property ?string $fecha_registro
 * @property TipoInfraccion $tipo_infraccion
 */
class CentralRiesgo extends Model
{
    protected $table = 'central_riesgo';

    protected $primaryKey = 'id_registro';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_registro',
        'id_ingreso',
        'id_registrador',
        'detalle_motivo',
        'fecha_registro',
        'tipo_infraccion',
    ];

    protected function casts(): array
    {
        return [
            'tipo_infraccion' => TipoInfraccion::class,
        ];
    }

    /** @return BelongsTo<RegistroAsistencia, $this> */
    public function registroAsistencia(): BelongsTo
    {
        return $this->belongsTo(RegistroAsistencia::class, 'id_ingreso');
    }
}
