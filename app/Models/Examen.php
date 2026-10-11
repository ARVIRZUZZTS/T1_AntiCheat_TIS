<?php

/**
 * @file    Examen.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `examen`: representa un examen masivo con su
 * ventana horaria (fecha, hora_inicio, duracion) y sus relaciones de
 * persistencia: cursos asociados, inscripciones (estudiante_examen) y
 * registros de asistencia.
 *
 * La hora de fin NO es una columna: es un dato derivado (hora_inicio + duracion)
 * que se calcula en `getHoraFinAttribute()`, para que no pueda quedar
 * desincronizado con la duración y para que un examen que cruza la medianoche
 * sume el día en vez de quedarse en la misma fecha.
 *
 * @changelog
 * - 2026-09-24  [T1]         feat: creación inicial del modelo.
 * - 2026-09-25  [OchoaCesar] feat: creación inicial del modelo.
 * - 2026-09-25  [OchoaCesar] feat: agregar relación registrosAsistencia().
 * - 2026-09-26  [Diego Tejerina] feat: anotaciones @property y relación cursos().
 * - 2026-09-26  [T1]         fix: resolver conflicto de merge sin resolver
 *   dejado en dev por el commit e4ea2fd (marcadores <<<<<<< sin quitar).
 * - 2026-09-28  [T1]         fix: resolver conflictos de merge al integrar
 *   dev en feature/28 (#28).
 * - 2026-10-05  [Alex Candia] feat: relación tipoExamen() con el catálogo, en
 *   vez del join a mano que usaba el listado de exámenes de la materia.
 * - 2026-10-05  [Alex Candia] feat: relaciones ambientes(), normas(),
 *   materialesPermitidos(), materialesPersonalizados() y normasPersonalizadas(),
 *   que son las que escribe RegistrarExamenService al crear un examen.
 * - 2026-10-10  [Alex Candia] refactor: `hora_fin` deja de ser columna y pasa a
 *   ser un accesor derivado de la hora de inicio y la duración.
 *
 * @see  EstudianteExamen
 * @see  RegistroAsistencia
 * @see  TipoExamen
 * @see  App\Services\Examen\RegistrarExamenService
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_examen
 * @property ?string $fecha
 * @property ?string $hora_inicio
 * @property ?string $hora_fin Hora derivada (inicio + duración); no es columna.
 * @property ?int $duracion
 * @property int $creador
 * @property int $tipo_examen
 */
class Examen extends Model
{
    protected $table = 'examen';

    protected $primaryKey = 'id_examen';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_examen',
        'fecha',
        'hora_inicio',
        'duracion',
        'creador',
        'tipo_examen',
    ];

    /**
     * Hora de fin derivada: la de inicio más la duración.
     *
     * No se lee de la base porque la columna ya no existe: se construye con
     * Carbon a partir de `hora_inicio`, que suma los minutos y, si el examen
     * cruza la medianoche, avanza al día siguiente. Devuelve null cuando falta
     * la hora de inicio o la duración.
     */
    public function getHoraFinAttribute(): ?string
    {
        if ($this->hora_inicio === null || $this->duracion === null) {
            return null;
        }

        return Carbon::parse('2000-01-01 '.$this->hora_inicio)
            ->addMinutes((int) $this->duracion)
            ->format('H:i:s');
    }

    /** @return BelongsToMany<Curso, $this> */
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(
            Curso::class,
            'examen_curso',
            'id_examen',
            'id_curso'
        );
    }

    /** @return HasMany<EstudianteExamen, $this> */
    public function estudianteExamenes(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_examen');
    }

    /**
     * Alias de estudianteExamenes(), con el nombre que ya usa la feature
     * #28 (lista de estudiantes por materia). Se mantienen los dos nombres
     * para no romper ese código ya mergeado a dev; unificar en una limpieza
     * posterior.
     *
     * @return HasMany<EstudianteExamen, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_examen');
    }

    /** @return HasMany<RegistroAsistencia, $this> */
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_examen');
    }

    /**
     * Tipo de examen del catálogo (`tipo_examen`). La clave foránea se pasa
     * explícita porque el nombre de la relación es `tipoExamen`: si se dejara
     * que Eloquent la dedujera, buscaría la columna `tipo_examen_id`, que no
     * existe en el esquema.
     *
     * @return BelongsTo<TipoExamen, $this>
     */
    public function tipoExamen(): BelongsTo
    {
        return $this->belongsTo(TipoExamen::class, 'tipo_examen');
    }

    /** @return BelongsToMany<Ambiente, $this> */
    public function ambientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Ambiente::class,
            'examen_ambiente',
            'id_examen',
            'id_ambiente'
        );
    }

    /** @return BelongsToMany<Norma, $this> */
    public function normas(): BelongsToMany
    {
        return $this->belongsToMany(
            Norma::class,
            'examen_norma',
            'id_examen',
            'id_norma'
        );
    }

    /**
     * Material del catálogo que se permite. La tabla puente llama a la FK
     * `id_material_permitido`, no `id_material`, así que va explícita.
     *
     * @return BelongsToMany<Material, $this>
     */
    public function materialesPermitidos(): BelongsToMany
    {
        return $this->belongsToMany(
            Material::class,
            'examen_material_permitido',
            'id_examen',
            'id_material_permitido'
        );
    }

    /** @return HasMany<MaterialPersonalizado, $this> */
    public function materialesPersonalizados(): HasMany
    {
        return $this->hasMany(MaterialPersonalizado::class, 'id_examen');
    }

    /** @return HasMany<NormaPersonalizada, $this> */
    public function normasPersonalizadas(): HasMany
    {
        return $this->hasMany(NormaPersonalizada::class, 'id_examen');
    }
}
