<?php
/**
 * @file    GestionCursoDTO.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Gestión de un curso: el periodo (`tipo_gestion`: primer semestre, invierno,
 * segundo semestre, verano) y el año en que se dicta. Viaja junta del
 * formulario de crear curso al servicio de alta, que la guarda en
 * `curso_tipo_gestion`.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del DTO.
 *
 * @see  App\Services\Curso\RegistrarCursoService
 */

namespace App\Support\DTO;

readonly class GestionCursoDTO
{
    public function __construct(
        public int $idTipoGestion,
        public int $anio,
    ) {}
}
