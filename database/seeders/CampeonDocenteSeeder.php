<?php

/**
 * @file    CampeonDocenteSeeder.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-11-08
 * @updated 2026-11-08
 *
 * @description
 * Seeder de desarrollo/pruebas: crea el docente "Campeon" (verificado), su
 * curso "Base de Datos 2" y lo puebla con todos los estudiantes existentes.
 *
 * Se ejecuta a mano:
 *
 *     php artisan db:seed --class=CampeonDocenteSeeder
 *
 * Es idempotente: si el docente/curso ya existen, los reutiliza y solo
 * inserta las inscripciones faltantes.
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: creación inicial.
 */

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CampeonDocenteSeeder extends Seeder
{
    private const SIS_DOCENTE = '202500123';

    private const NOMBRE_CURSO = 'Base de Datos 2';

    public function run(): void
    {
        $docente = Usuario::query()->where('cod_sis', self::SIS_DOCENTE)->first();

        if ($docente === null) {
            $docente = new Usuario();
            $docente->id_usuario = ((int) Usuario::query()->max('id_usuario')) + 1;
            $docente->cod_sis = self::SIS_DOCENTE;
            $docente->password = Hash::make('12345678');
            $docente->nombre_usuario = 'Campeon';
            $docente->apellido = 'Docente';
            $docente->nombre = 'Campeon';
            $docente->estado_cuenta = 'habilitada';
            $docente->fecha_creacion = now();
            $docente->save();

            DB::table('rol_usuario')->insertOrIgnore([
                'id_usuario' => $docente->id_usuario,
                'id_rol' => 1,
            ]);
        } else {
            $docente->estado_cuenta = 'habilitada';
            $docente->password = Hash::make('12345678');
            $docente->save();

            DB::table('rol_usuario')->insertOrIgnore([
                'id_usuario' => $docente->id_usuario,
                'id_rol' => 1,
            ]);
        }

        $curso = Curso::query()
            ->where('nombre_curso', self::NOMBRE_CURSO)
            ->where('sis_doc', $docente->id_usuario)
            ->first();

        if ($curso === null) {
            $curso = new Curso();
            $curso->id_curso = ((int) Curso::query()->max('id_curso')) + 1;
            $curso->nombre_curso = self::NOMBRE_CURSO;
            $curso->sis_doc = $docente->id_usuario;
            $curso->fecha_creacion = now()->toDateString();
            $curso->estado = 'EnCurso';
            $curso->save();

            DB::table('curso_tipo_gestion')->insertOrIgnore([
                'id_curso' => $curso->id_curso,
                'id_tg' => 1,
                'anio' => (int) now()->year,
            ]);
        }

        $estudiantes = DB::table('estudiante')->pluck('sis_estudiante');

        $filas = $estudiantes->map(fn (string $sis): array => [
            'sis_estudiante' => $sis,
            'id_curso' => $curso->id_curso,
        ])->all();

        if ($filas !== []) {
            DB::table('estudiante_curso')->insertOrIgnore($filas);
        }

        $this->command?->info("Docente Campeon (SIS: ".self::SIS_DOCENTE.") listo. Curso: ".self::NOMBRE_CURSO." (ID {$curso->id_curso}), estudiantes inscritos: ".count($filas).".");
    }
}
