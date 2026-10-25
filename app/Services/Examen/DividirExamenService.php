<?php

/**
 * @file    DividirExamenService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-24
 * @updated 2026-10-24
 *
 * @description
 * Servicio de la feature Examen encargado únicamente de dividir a los
 * estudiantes inscritos en un examen entre los ambientes seleccionados,
 * según el criterio indicado: por apellido o por carrera.
 *
 * La asignación se registra en la tabla puente `estudiante_examen_ambiente`
 * (id_ee, id_ambiente). El reparto es equitativo entre los ambientes
 * elegidos, manteniendo un orden estable para cada criterio.
 *
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  App\Models\EstudianteExamen
 *
 * @changelog
 * - 2026-10-24  [Alisson D. Alvarado]  feat: creación inicial del servicio con la lógica de división por ambientes.
 */

namespace App\Services\Examen;

use App\Models\EstudianteExamen;
use App\Models\Examen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DividirExamenService
{
    /**
     * Divide a los estudiantes del examen entre los ambientes seleccionados
     * por apellido o por carrera.
     *
     * @param  Examen  $examen        Examen al que pertenecen las inscripciones.
     * @param  array<int|string>  $ambientes    Lista de ids de ambientes seleccionados.
     * @param  string  $divisionPor   Criterio de división: 'apellido' o 'carrera'.
     *
     * @return void
     *
     * @throws InvalidArgumentException Si no se indica un criterio válido.
     */
    public function dividir(Examen $examen, array $ambientes, string $divisionPor): void
    {
        if ($ambientes === []) {
            return;
        }

        $ambientes = array_values(array_map(static fn ($id): int => (int) $id, $ambientes));

        if (! in_array($divisionPor, ['apellido', 'carrera'], true)) {
            throw new InvalidArgumentException('El criterio de división debe ser "apellido" o "carrera".');
        }

        $inscripciones = $this->obtenerInscripcionesOrdenadas($examen, $divisionPor);

        if ($inscripciones->isEmpty()) {
            return;
        }

        $filas = [];
        $indiceAmbiente = 0;
        $cantidadAmbientes = count($ambientes);

        foreach ($inscripciones as $inscripcion) {
            $ambienteId = $ambientes[$indiceAmbiente % $cantidadAmbientes];
            $filas[] = [
                'id_ee' => $inscripcion->id_estudiante_examen,
                'id_ambiente' => $ambienteId,
            ];
            $indiceAmbiente++;
        }

        if ($filas !== []) {
            DB::table('estudiante_examen_ambiente')->insert($filas);
        }
    }

    /**
     * Obtiene las inscripciones del examen con el estudiante cargado
     * y las ordena según el criterio de división.
     *
     * @param  Examen  $examen        Examen del cual se obtienen las inscripciones.
     * @param  string  $divisionPor   Criterio de ordenamiento: 'apellido' o 'carrera'.
     *
     * @return Collection<int, EstudianteExamen>
     */
    private function obtenerInscripcionesOrdenadas(Examen $examen, string $divisionPor): Collection
    {
        $inscripciones = EstudianteExamen::query()
            ->with('estudiante')
            ->where('id_examen', $examen->id_examen)
            ->get();

        if ($inscripciones->isEmpty()) {
            return $inscripciones;
        }

        if ($divisionPor === 'apellido') {
            return $inscripciones->sortBy(function (EstudianteExamen $inscripcion): string {
                $estudiante = $inscripcion->estudiante;
                $apellido = $estudiante?->apellido_estudiante ?? '';
                $nombre = $estudiante?->nombre_estudiante ?? '';
                $sis = $inscripcion->sis_estudiante ?? '';

                return mb_strtolower($apellido.' '.$nombre.' '.$sis);
            })->values();
        }

        return $inscripciones->sortBy(function (EstudianteExamen $inscripcion): string {
            $estudiante = $inscripcion->estudiante;
            $carrera = $estudiante?->carrera ?? '';
            $apellido = $estudiante?->apellido_estudiante ?? '';
            $nombre = $estudiante?->nombre_estudiante ?? '';
            $sis = $inscripcion->sis_estudiante ?? '';

            return mb_strtolower($carrera.'|'.$apellido.' '.$nombre.' '.$sis);
        })->values();
    }
}
