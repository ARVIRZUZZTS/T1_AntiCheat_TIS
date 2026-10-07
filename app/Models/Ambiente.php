<?php

/**
 * @file    Ambiente.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `ambiente`: el catálogo de aulas y laboratorio
 * donde se toma un examen. Es la lectura de la tabla nomás; el vínculo con el
 * examen vive en la tabla puente `examen_ambiente`.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del modelo.
 *
 * @see  App\Services\Ambiente\ListarAmbientesService
 * @see  App\Models\Examen
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_ambiente
 * @property string $nombre_ambiente
 */
class Ambiente extends Model
{
    protected $table = 'ambiente';

    protected $primaryKey = 'id_ambiente';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_ambiente',
        'nombre_ambiente',
    ];
}
