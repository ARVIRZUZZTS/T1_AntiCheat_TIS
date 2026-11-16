<?php

/**
 * @file    MateriasDemoSeeder.php
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 * @created 2026-11-15
 *
 * @description
 * Seeder idempotente que llena la base con materias de ejemplo para la gestión
 * 2/2026 (segundo semestre 2026). Para cada materia crea 2 grupos (cursos
 * "<Materia> - Grupo N") y a cada grupo le asigna al menos 15 estudiantes. Los
 * estudiantes salen de un pool compartido, así que pueden repetirse entre
 * materias distintas, pero dentro de una misma materia cada estudiante aparece
 * una sola vez (los dos grupos no comparten alumnos).
 *
 * Es idempotente: usa firstOrCreate / insertOrIgnore con claves deterministas
 * (código SIS por índice, nombre de curso por materia+grupo), así que correrlo
 * dos veces no duplica nada. Las tablas no tienen secuencia, por eso el id se
 * calcula con max()+1 y se lleva en memoria durante la corrida.
 *
 * @changelog
 * - 2026-11-15  [Diego Tejerina]  feat: seeder de materias/grupos/estudiantes
 *   de ejemplo (gestión 2/2026), idempotente.
 *
 * @see  database/seeders/DevEstudiantesSeeder.php
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MateriasDemoSeeder extends Seeder
{
    /** Materias a crear. */
    private const MATERIAS = [
        'Álgebra I',
        'Álgebra II',
        'Cálculo I',
        'Cálculo II',
        'Ecuaciones Diferenciales',
        'Introducción a la Programación',
        'Taller de Ingeniería de Software',
    ];

    private const GRUPOS_POR_MATERIA = 2;

    private const ALUMNOS_POR_GRUPO = 16;

    /** Gestión 2/2026 = segundo semestre (id_tipo_gestion) 2026. */
    private const ID_TIPO_GESTION = 3;

    private const ANIO = 2026;

    /** Prefijo del código SIS de los estudiantes de ejemplo (gestión 2026). */
    private const SIS_BASE = 202600000;

    private const NOMBRES = [
        'Juan', 'Maria', 'Carlos', 'Ana', 'Luis', 'Sofia', 'Diego', 'Valeria',
        'Jorge', 'Camila', 'Pedro', 'Lucia', 'Mateo', 'Gabriela', 'Andres',
        'Daniela', 'Fernando', 'Paola', 'Ricardo', 'Natalia', 'Sergio', 'Rosa',
        'Marco', 'Elena', 'Ivan', 'Carla', 'Oscar', 'Fabiola', 'Hugo', 'Veronica',
    ];

    private const APELLIDOS = [
        'Quispe', 'Mamani', 'Flores', 'Choque', 'Condori', 'Vargas', 'Rojas',
        'Gutierrez', 'Alvarez', 'Cruz', 'Fernandez', 'Lopez', 'Ramirez', 'Torrez',
        'Chambi', 'Apaza', 'Ticona', 'Colque', 'Huanca', 'Nina', 'Aliaga', 'Soliz',
        'Camacho', 'Zabala', 'Montaño', 'Arce', 'Villca', 'Poma', 'Guzman', 'Lima',
    ];

    public function run(): void
    {
        $idDocente = $this->idDocente();

        // Pool de estudiantes compartido: suficientes para que cada materia tome
        // 32 distintos y a la vez se repitan entre materias.
        $totalPool = 80;
        $pool = $this->crearPoolEstudiantes($totalPool);

        $siguienteCurso = ((int) DB::table('curso')->max('id_curso')) + 1;

        $indiceMateria = 0;
        foreach (self::MATERIAS as $materia) {
            // Ventana del pool para esta materia: distinta por materia (se
            // solapan entre materias), y dentro de la materia los 32 alumnos son
            // todos distintos.
            $base = ($indiceMateria * 16) % $totalPool;

            for ($grupo = 1; $grupo <= self::GRUPOS_POR_MATERIA; $grupo++) {
                $nombreCurso = $materia.' - Grupo '.$grupo;

                $idCurso = $this->firstOrCreateCurso($nombreCurso, $idDocente, $siguienteCurso);
                if ($idCurso === $siguienteCurso) {
                    $siguienteCurso++;
                }

                $this->vincularGestion($idCurso);

                $desde = ($base + ($grupo - 1) * self::ALUMNOS_POR_GRUPO) % $totalPool;
                for ($i = 0; $i < self::ALUMNOS_POR_GRUPO; $i++) {
                    $sis = $pool[($desde + $i) % $totalPool];
                    DB::table('estudiante_curso')->insertOrIgnore([
                        'sis_estudiante' => $sis,
                        'id_curso' => $idCurso,
                    ]);
                }
            }

            $indiceMateria++;
        }
    }

    /**
     * Crea (si no existen) los estudiantes del pool y devuelve sus códigos SIS.
     *
     * @return list<string>
     */
    private function crearPoolEstudiantes(int $total): array
    {
        $sisList = [];
        for ($i = 1; $i <= $total; $i++) {
            $sis = (string) (self::SIS_BASE + $i);
            $sisList[] = $sis;

            DB::table('estudiante')->insertOrIgnore([
                'sis_estudiante' => $sis,
                'nombre_estudiante' => self::NOMBRES[$i % count(self::NOMBRES)],
                'apellido_estudiante' => self::APELLIDOS[$i % count(self::APELLIDOS)],
                'carrera' => 'Ingeniería de Sistemas',
                'verificado' => true,
            ]);
        }

        return $sisList;
    }

    /**
     * Id del curso existente (por nombre + docente) o del nuevo que se crea con
     * el id propuesto.
     */
    private function firstOrCreateCurso(string $nombreCurso, int $idDocente, int $idPropuesto): int
    {
        $existente = DB::table('curso')
            ->where('nombre_curso', $nombreCurso)
            ->where('sis_doc', $idDocente)
            ->value('id_curso');

        if ($existente !== null) {
            return (int) $existente;
        }

        DB::table('curso')->insert([
            'id_curso' => $idPropuesto,
            'nombre_curso' => $nombreCurso,
            'sis_doc' => $idDocente,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        return $idPropuesto;
    }

    private function vincularGestion(int $idCurso): void
    {
        DB::table('curso_tipo_gestion')->insertOrIgnore([
            'id_curso' => $idCurso,
            'id_tg' => self::ID_TIPO_GESTION,
            'anio' => self::ANIO,
        ]);
    }

    /**
     * Un docente al que colgar los cursos de ejemplo: el primero con rol
     * docente. Si no hubiera ninguno, usa el primer usuario.
     */
    private function idDocente(): int
    {
        $idDocente = DB::table('usuario')
            ->join('rol_usuario', 'rol_usuario.id_usuario', '=', 'usuario.id_usuario')
            ->join('rol', 'rol.id_rol', '=', 'rol_usuario.id_rol')
            ->where('rol.nombre_rol', 'docente')
            ->value('usuario.id_usuario');

        return (int) ($idDocente ?? DB::table('usuario')->min('id_usuario'));
    }
}
