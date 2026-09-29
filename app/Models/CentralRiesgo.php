<?php

/**
 * @file    CentralRiesgo.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
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
 * @property ?int $id_ingreso
 * @property int $id_registrador
 * @property ?string $detalle_motivo
 * @property ?string $materia
 * @property ?string $descripcion
 * @property ?string $id_estudiante
 * @property ?string $fecha_registro
 * @property TipoInfraccion $tipo_infraccion
 */
class CentralRiesgo extends Model
{
    protected $table = 'central_riesgo';

    protected $primaryKey = 'id_registro';

    public $timestamps = false;

    /**
     * La secuencia la crea la migración
     * `2026_09_28_000001_ampliar_central_riesgo_para_el_reporte_de_incidencias`.
     *
     * Con esto en `true`, Eloquent recupera el id que le asignó la base y el
     * resumen del modal puede mostrar el número del registro. En `false` la fila
     * se guardaba igual, pero `$registro->id_registro` quedaba vacío.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_ingreso',
        'id_registrador',
        'detalle_motivo',
        'materia',
        'descripcion',
        'id_estudiante',
        'fecha_registro',
        'tipo_infraccion',
    ];

    protected function casts(): array
    {
        return [
            'tipo_infraccion' => TipoInfraccion::class,
            'fecha_registro' => 'datetime',
        ];
    }

    /**
     * Estudiante sobre el que se registró la incidencia.
     *
     * La tabla no tiene `sis_estudiante` sino `id_estudiante`, porque la clave
     * primaria de `estudiante` es su código SIS y esa es la que se guarda.
     *
     * @return BelongsTo<Estudiante, $this>
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'sis_estudiante');
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
