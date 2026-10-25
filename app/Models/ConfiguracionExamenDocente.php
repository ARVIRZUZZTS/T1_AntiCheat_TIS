<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_configuracion_examen
 * @property int $id_examen
 * @property int $id_docente
 */
class ConfiguracionExamenDocente extends Model
{
    protected $table = 'configuracion_examen_docente';

    protected $primaryKey = 'id_configuracion_examen';

    protected $fillable = ['id_examen', 'id_docente'];

    public function scopeDelDocente(Builder $query, int $idDocente): Builder
    {
        return $query->where($this->qualifyColumn('id_docente'), $idDocente);
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_docente', 'id_usuario');
    }

    /** @return BelongsToMany<Norma, $this> */
    public function normas(): BelongsToMany
    {
        return $this->belongsToMany(
            Norma::class,
            'configuracion_examen_norma',
            'id_configuracion_examen',
            'id_norma'
        );
    }

    /** @return BelongsToMany<Material, $this> */
    public function materialesPermitidos(): BelongsToMany
    {
        return $this->belongsToMany(
            Material::class,
            'configuracion_examen_material',
            'id_configuracion_examen',
            'id_material'
        );
    }

    /** @return HasMany<ConfiguracionNormaPersonalizada, $this> */
    public function normasPersonalizadas(): HasMany
    {
        return $this->hasMany(ConfiguracionNormaPersonalizada::class, 'id_configuracion_examen');
    }

    /** @return HasMany<ConfiguracionMaterialPersonalizado, $this> */
    public function materialesPersonalizados(): HasMany
    {
        return $this->hasMany(ConfiguracionMaterialPersonalizado::class, 'id_configuracion_examen');
    }

    /** @return HasMany<EstudianteExamen, $this> */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_configuracion_examen');
    }
}
