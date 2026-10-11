<?php

/**
 * @file    CentralRiesgo.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-10-10
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
 * - 2026-10-09  [T1]  feat: alinear el modelo con el esquema #70 de la base:
 *   `sis_estudiante`, `id_examen` y `motivo` como columnas propias; `id_ingreso`
 *   y `estado_incidencia` dejan de existir; `fecha_registro` pasa a timestamp y
 *   se agregan las relaciones estudiante(), examen() y materia().
 * - 2026-10-10  [T1]  fix: `id_registro` entra a `$fillable`. El PK es un entero
 *   NOT NULL sin secuencia, así que el servicio de ingreso lo asigna a mano
 *   (max + 1); sin esto Eloquent lo dejaba en null y el INSERT fallaba.
 *   deja de existir; `fecha_registro` pasa a timestamp y se agregan las
 *   relaciones estudiante(), examen() y materia().
 * - 2026-10-10  [T1]  feat: `id_curso` nullable para guardar el curso elegido
 *   cuando el examen se comparte entre varios cursos; `materia()` lo prefiere
 *   sobre el primer curso del examen. `id_registro` entra a fillable porque la
 *   aplicación calcula el id (la tabla no tiene secuencia). Se castea `motivo` a
 *   `Motivo`, que `registrar()` ya usaba con `->etiqueta()`.
 */

namespace App\Models;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id_registro
 * @property string $sis_estudiante
 * @property int $id_examen
 * @property ?int $id_curso
 * @property int $id_registrador
 * @property ?int $id_confirmador
 * @property Motivo $motivo
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
        'id_registro',
        'sis_estudiante',
        'id_examen',
        'id_curso',
        'id_registrador',
        'id_confirmador',
        'motivo',
        'detalle_motivo',
        'fecha_registro',
        'tipo_infraccion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'motivo' => Motivo::class,
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
     * Curso elegido para la incidencia cuando el examen se comparte entre varios
     * cursos. Es nullable: los exámenes de un solo curso no lo necesitan y las
     * incidencias viejas no lo tienen.
     *
     * @return BelongsTo<Curso, $this>
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    /**
     * Materia de la incidencia.
     *
     * Si se guardó el curso elegido (`id_curso`) se usa ese; si no, se cae al
     * primer curso del examen con la cadena
     * `id_examen -> examen_curso -> curso.nombre_curso`. Se prefiere el curso
     * guardado porque un examen puede compartirse entre varios cursos y el
     * primero no tiene por qué ser el que la persona eligió.
     *
     * Devuelve null en vez de fallar cuando no hay ni curso elegido ni curso
     * asignado al examen, porque es un dato que se puede auditar después sin
     * perder la incidencia.
     *
     * @return ?string Nombre del curso, o null si no se puede resolver.
     */
    public function materia(): ?string
    {
        if ($this->id_curso !== null) {
            $curso = $this->curso;

            if ($curso !== null) {
                return $curso->nombre_curso;
            }
        }

        return $this->examen?->cursos->sortBy('id_curso')->first()?->nombre_curso;
    }
}
