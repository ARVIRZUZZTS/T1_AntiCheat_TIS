<?php

namespace App\Support\DTO;

/**
 * @file    CargarListaEstudiantesResultado.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @updated 2026-11-21
 *
 * @description
 * Resumen de la carga de una lista de estudiantes a un curso.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: DTO de resultado de carga (HU 25).
 */
final class CargarListaEstudiantesResultado
{
    /**
     * @param  int  $totalFilas  Filas de datos leídas (excluye el encabezado).
     * @param  int  $estudiantesNuevos  Estudiantes creados en `estudiante`.
     * @param  int  $inscripcionesNuevas  Filas insertadas en `estudiante_curso`.
     * @param  int  $yaInscritos  Saltados por ya estar inscritos en el curso.
     * @param  int  $tramposos  Estudiantes que aparecen en la central de riesgos como tramposos.
     * @param  int  $estudianteExamenesCreados  Filas creadas en `estudiante_examen`.
     * @param  list<string>  $sisDuplicadosEnArchivo  SIS repetidos dentro del archivo.
     * @param  list<string>  $sisInvalidos  Filas omitidas por SIS inválido.
     */
    public function __construct(
        public readonly int $totalFilas,
        public readonly int $estudiantesNuevos,
        public readonly int $inscripcionesNuevas,
        public readonly int $yaInscritos,
        public readonly int $tramposos,
        public readonly int $estudianteExamenesCreados,
        public readonly array $sisDuplicadosEnArchivo = [],
        public readonly array $sisInvalidos = [],
    ) {}
}
