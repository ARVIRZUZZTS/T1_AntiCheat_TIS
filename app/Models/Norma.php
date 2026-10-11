<?php

/**
 * @file    Norma.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `norma`: el catálogo de reglas del examen (no usar
 * celulares, prohibido hablar...). Es la lectura de la tabla nomás; el vínculo
 * con el examen vive en la tabla puente `examen_norma`.
 *
 * Las reglas que se escriben a mano en el formulario no son de este catálogo:
 * no tienen tabla donde guardarse todavía.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del modelo.
 *
 * @see  App\Services\Norma\ListarNormasService
 * @see  App\Models\Examen
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_norma
 * @property string $detalle_norma
 */
class Norma extends Model
{
    protected $table = 'norma';

    protected $primaryKey = 'id_norma';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_norma',
        'detalle_norma',
    ];
}
