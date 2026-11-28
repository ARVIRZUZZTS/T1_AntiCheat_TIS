<?php

namespace App\Support\DTO;

/**
 * @file    CargarListaPrevia.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-24
 *
 * @updated 2026-11-24
 *
 * @description
 * Previsualización de un archivo de estudiantes contra un curso que ya tiene
 * inscritos: cuántos códigos SIS del archivo son nuevos (se añadirían) y
 * cuántos ya están repetidos en el curso (se ignorarían). No escribe en la
 * base; alimenta el modal de confirmación de la carga (HU 25).
 *
 * @changelog
 * - 2026-11-24  [OchoaCesar]  feat: DTO de previsualización de la carga (HU 25).
 */
final class CargarListaPrevia
{
    /**
     * @param  int  $nuevos  Códigos SIS del archivo aún no inscritos en el curso.
     * @param  int  $repetidos  Códigos SIS del archivo ya inscritos en el curso.
     */
    public function __construct(
        public readonly int $nuevos,
        public readonly int $repetidos,
    ) {}
}
