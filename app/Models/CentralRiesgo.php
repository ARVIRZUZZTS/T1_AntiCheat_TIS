<?php

/**
 * @file    CentralRiesgo.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-29
 *
 * @description
 * Modelo de la tabla `central_riesgo`: mapea las infracciones registradas
 * durante un examen (tramposo o sospechoso).
 *
 * Cada incidencia guarda su propio estudiante, examen y registrador, para que
 * también se pueda registrar a alguien que todavía no tiene fila de ingreso o
 * que no estaba en la base de datos. `id_ingreso` queda solo como trazabilidad
 * del monitor en vivo. La materia no se guarda: se deriva con
 * `id_examen -> examen_curso -> curso.nombre_curso`.
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 * - 2026-09-29  [Valery D. Ortuno P]  fix: `id_ingreso` pasa a ser opcional y se
 *   agregan `sis_estudiante`, `id_examen` y `motivo`; `fecha_registro` pasa a
 *   `timestamp` y la infracción queda en tramposo o sospechoso (#70).
 * - 2026-09-29  [Candy]  feat: `materia()` deriva la materia del examen, que ya
 *   no se guarda en la tabla, y `fillable` queda con las columnas del esquema
 *   nuevo; el id lo asigna la secuencia de la base (#70).
 */

namespace App\Models;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @property int $id_registro
 * @property string $sis_estudiante
 * @property int $id_examen
 * @property int $id_registrador
 * @property Motivo $motivo
 * @property ?string $detalle_motivo
 * @property \Illuminate\Support\Carbon $fecha_registro
 * @property TipoInfraccion $tipo_infraccion
 * @property ?int $id_ingreso
 */
class CentralRiesgo extends Model
{
    protected $table = 'central_riesgo';

    protected $primaryKey = 'id_registro';

    public $timestamps = false;

    /**
     * El id lo asigna la secuencia `central_riesgo_id_registro_seq` de la base,
     * no la aplicación: así dos registros simultáneos no calculan el mismo y el
     * número se le puede mostrar a quien registró la incidencia sin volver a
     * consultarlo.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'sis_estudiante',
        'id_examen',
        'id_registrador',
        'motivo',
        'detalle_motivo',
        'fecha_registro',
        'tipo_infraccion',
        'id_ingreso',
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
        return $this->belongsTo(Estudiante::class, 'sis_estudiante');
    }

    /** @return BelongsTo<Examen, $this> */
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
     * Ingreso del monitor en vivo en el que se observó la incidencia, si aplica.
     *
     * @return BelongsTo<RegistroAsistencia, $this>
     */
    public function registroAsistencia(): BelongsTo
    {
        return $this->belongsTo(RegistroAsistencia::class, 'id_ingreso');
    }
    
    /**
     * Materia del examen en el que se observó la incidencia.
     *
     * No es una columna: se deriva con la cadena
     * `id_examen -> examen_curso -> curso.nombre_curso`, que es la única fuente
     * de verdad de la materia. Si el examen todavía no tiene curso asignado
     * devuelve null en vez de fallar, porque es un dato que se puede auditar
     * después sin perder la incidencia.
     *
     * @return ?string  Nombre del curso, o null si el examen no tiene curso.
     */
    public function materia(): ?string
    {
        $curso = $this->examen?->cursos->first();

        return $curso?->nombre_curso;
    }
}
