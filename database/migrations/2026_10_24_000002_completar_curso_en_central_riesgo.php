<?php

/**
 * @file    2026_10_24_000002_completar_curso_en_central_riesgo.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Completa `central_riesgo.id_curso` en las incidencias que se registraron sin
 * curso (el monitor en vivo no lo guardaba). La central de riesgos (HU 10)
 * lista por ese curso, así que sin él quedaban "Sin materia" y fuera de "Mis
 * materias".
 *
 * Usa la misma regla que ResolverCursoIncidenciaService, en dos pasadas:
 *  1. El curso del examen en el que está inscrito el estudiante (el de menor
 *     id si son varios).
 *  2. Si sigue sin curso y el examen tiene un solo curso, ese.
 * Lo que no se puede decidir queda en null. Solo toca filas con `id_curso`
 * null, así que correrla de nuevo no cambia nada.
 *
 * `down()` no deshace nada: no hay forma de distinguir los cursos completados
 * acá de los que se eligieron al registrar.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: completar `id_curso` de las incidencias sin curso.
 *
 * @see  App\Services\CentralRiesgo\ResolverCursoIncidenciaService
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'update central_riesgo cr set id_curso = ('
            .' select min(ec.id_curso) from examen_curso ec'
            .' join estudiante_curso ecur on ecur.id_curso = ec.id_curso'
            .' where ec.id_examen = cr.id_examen and ecur.sis_estudiante = cr.sis_estudiante'
            .') where cr.id_curso is null'
        );

        DB::statement(
            'update central_riesgo cr set id_curso = ('
            .' select min(ec.id_curso) from examen_curso ec where ec.id_examen = cr.id_examen'
            .') where cr.id_curso is null'
            .' and (select count(*) from examen_curso ec where ec.id_examen = cr.id_examen) = 1'
        );
    }

    public function down(): void
    {
        // Sin vuelta atrás: ver la descripción del archivo.
    }
};
