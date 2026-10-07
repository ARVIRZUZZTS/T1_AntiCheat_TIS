<?php

/**
 * @file    Material.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `material`: el catálogo de material que se puede
 * permitir en un examen (calculadora, formulario, tabla periódica...). Es la
 * lectura de la tabla nomás; el vínculo con el examen vive en la tabla puente
 * `examen_material_permitido`.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del modelo.
 *
 * @see  App\Services\Material\ListarMaterialesService
 * @see  App\Models\Examen
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_material
 * @property string $descripcion
 */
class Material extends Model
{
    protected $table = 'material';

    protected $primaryKey = 'id_material';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_material',
        'descripcion',
    ];
}
