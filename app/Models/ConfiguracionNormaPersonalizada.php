<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_configuracion_norma
 * @property int $id_configuracion_examen
 * @property int $numero_norma
 * @property string $descripcion_norma
 */
class ConfiguracionNormaPersonalizada extends Model
{
    protected $table = 'configuracion_norma_personalizada';

    protected $primaryKey = 'id_configuracion_norma';

    public $timestamps = false;

    protected $fillable = ['id_configuracion_examen', 'numero_norma', 'descripcion_norma'];

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionExamenDocente::class, 'id_configuracion_examen');
    }
}
