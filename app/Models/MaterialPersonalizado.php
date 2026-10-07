<?php

/**
 * @file    MaterialPersonalizado.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `material_personalizado`: el material que se
 * escribe a mano al crear un examen y no viene del catálogo. No tiene FK al
 * catálogo de materiales: `numero_material` es el orden de la línea dentro del
 * examen (1, 2, 3...), como en `norma_personalizada`.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del modelo.
 *
 * @see  App\Models\NormaPersonalizada
 * @see  App\Services\Examen\RegistrarExamenService
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_examen
 * @property int $numero_material
 * @property string $descripcion_material
 */
class MaterialPersonalizado extends Model
{
    protected $table = 'material_personalizado';

    protected $primaryKey = null;

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id_examen',
        'numero_material',
        'descripcion_material',
    ];

    /** @return BelongsTo<Examen, $this> */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }
}
