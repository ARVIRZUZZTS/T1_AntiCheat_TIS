<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_configuracion_material
 * @property int $id_configuracion_examen
 * @property int $numero_material
 * @property string $descripcion_material
 */
class ConfiguracionMaterialPersonalizado extends Model
{
    protected $table = 'configuracion_material_personalizado';

    protected $primaryKey = 'id_configuracion_material';

    public $timestamps = false;

    protected $fillable = ['id_configuracion_examen', 'numero_material', 'descripcion_material'];

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionExamenDocente::class, 'id_configuracion_examen');
    }
}
