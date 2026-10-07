<?php

/**
 * @file    NormaPersonalizada.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `norma_personalizada`: la regla que se escribe a
 * mano al crear un examen y no viene del catálogo. No tiene FK al catálogo de
 * normas: `numero_norma` es el orden de la línea dentro del examen (1, 2, 3...),
 * que es como ya vienen los datos que hay cargados.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del modelo.
 *
 * @see  App\Models\MaterialPersonalizado
 * @see  App\Services\Examen\RegistrarExamenService
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_examen
 * @property int $numero_norma
 * @property string $descripcion_norma
 */
class NormaPersonalizada extends Model
{
    protected $table = 'norma_personalizada';

    protected $primaryKey = null;

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id_examen',
        'numero_norma',
        'descripcion_norma',
    ];

    /** @return BelongsTo<Examen, $this> */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }
}
