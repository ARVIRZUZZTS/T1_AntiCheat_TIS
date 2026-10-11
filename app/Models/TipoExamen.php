<?php

/**
 * @file    TipoExamen.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Modelo Eloquent de la tabla `tipo_examen`: el catálogo de tipos de examen
 * (PP, SP, FINAL...) contra el que apunta la columna `tipo_examen` de `examen`.
 * Mapear la tabla como modelo evita el join a mano que antes se arms en el
 * servicio de listado para traer el nombre del tipo.
 *
 * Ojo con el nombre: este modelo NO es el enum de dominio
 * {@see \App\Enums\TipoExamen}, que enumera los mismos valores para validar y
 * para el selector del formulario de alta. Este es el acceso a la tabla.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del modelo.
 *
 * @see  App\Models\Examen
 * @see  App\Enums\TipoExamen
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_tipo_examen
 * @property string $nombre_tipo_examen
 */
class TipoExamen extends Model
{
    protected $table = 'tipo_examen';

    protected $primaryKey = 'id_tipo_examen';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_tipo_examen',
        'nombre_tipo_examen',
    ];
}
