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
 * durante un examen (tramposo o sospechoso).
 *
 * Cada incidencia guarda su propio estudiante, examen y registrador, para que
 * también se pueda registrar a alguien que todavía no tiene fila de ingreso.
 * La materia no se guarda: se deriva con
 * `id_examen -> examen_curso -> curso.nombre_curso`.
 *
 * La columna `motivo` es un enum de la base que usa espacios
 * ('intento de ingreso no autorizado'), mientras que el enum de dominio
 * {@see \App\Enums\Motivo} usa guiones bajos. Mientras no se concilien, no se
 * castea a la clase enum: se lee y se escribe como string, o Eloquent lanza
 * ValueError al hidratar cualquier fila existente.
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 * - 2026-09-24  [T1]  fix: agregar `estado_incidencia` (columna NOT NULL en
 *                         la base, faltaba en fillable y casts).
 * - 2026-10-09  [T1]  feat: alinear el modelo con el esquema #70 de la base:
 *   `sis_estudiante`, `id_examen` y `motivo` como columnas propias; `id_ingreso`
 *   y `estado_incidencia` dejan de existir; `fecha_registro` pasa a timestamp y
 *   se agregan las relaciones estudiante(), examen() y materia().
 */

namespace App\Models;

use App\Enums\TipoInfraccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id_registro
 * @property string $sis_estudiante
 * @property int $id_examen
 * @property int $id_registrador
 * @property string $motivo
 * @property ?string $detalle_motivo
 * @property Carbon $fecha_registro
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
        'sis_estudiante',
        'id_examen',
        'id_registrador',
        'motivo',
        'detalle_motivo',
        'fecha_registro',
        'tipo_infraccion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'tipo_infraccion' => TipoInfraccion::class,
        ];
    }

    /**
     * Estudiante sobre el que se registró la incidencia.
     *
     * @return BelongsTo<Estudiante, $this>
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'sis_estudiante', 'sis_estudiante');
    }

    /**
     * Examen en el que se observó la incidencia, del que se deriva la materia.
     *
     * @return BelongsTo<Examen, $this>
     */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_registrador', 'id_usuario');
    }

    /**
     * Materia de la incidencia: el primer curso del examen.
     *
     * No es una columna: se deriva con la cadena
     * `id_examen -> examen_curso -> curso.nombre_curso`. Si el examen todavía
     * no tiene curso asignado devuelve null en vez de fallar, porque es un dato
     * que se puede auditar después sin perder la incidencia.
     *
     * @return ?string Nombre del curso, o null si el examen no tiene curso.
     */
    public function materia(): ?string
    {
        return $this->examen?->cursos->sortBy('id_curso')->first()?->nombre_curso;
    }
}
