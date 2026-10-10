<?php

/**
 * @file    CentralRiesgo.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-10-09
 *
 * @description
 * Modelo de la tabla `central_riesgo`: mapea las infracciones registradas
 * durante un examen (tramposo, sospechoso).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 * - 2026-09-24  [T1]  fix: agregar `estado_incidencia` (columna NOT NULL en
 *                         la base, faltaba en fillable y casts).
 * - 2026-10-09  [Diego Tejerina]  feat: registrar docente confirmador (#142).
 */

namespace App\Models;

use App\Enums\EstadoIncidencia;
use App\Enums\TipoInfraccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_registro
 * @property int $id_ingreso
 * @property int $id_registrador
 * @property ?int $id_confirmador
 * @property ?int $id_examen
 * @property ?string $detalle_motivo
 * @property ?string $fecha_registro
 * @property TipoInfraccion $tipo_infraccion
 * @property EstadoIncidencia $estado_incidencia
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
        'id_confirmador',
        'detalle_motivo',
        'fecha_registro',
        'tipo_infraccion',
        'estado_incidencia',
    ];

    protected function casts(): array
    {
        return [
            'tipo_infraccion' => TipoInfraccion::class,
            'estado_incidencia' => EstadoIncidencia::class,
        ];
    }

    /** @return BelongsTo<RegistroAsistencia, $this> */
    public function registroAsistencia(): BelongsTo
    {
        return $this->belongsTo(RegistroAsistencia::class, 'id_ingreso');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_registrador', 'id_usuario');
    }
}
