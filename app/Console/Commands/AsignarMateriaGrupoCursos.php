<?php

/**
 * @file    AsignarMateriaGrupoCursos.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Migración de datos: asigna a cada curso existente su `id_materia` y
 * `numero_grupo` interpretando `nombre_curso`. La materia se busca SOLO en el
 * catálogo (materias con código); si no coincide con ninguna, el curso queda
 * con `id_materia = NULL` y se reporta (no se inventan materias). Si el grupo no
 * se puede deducir, deja `numero_grupo` en null y lo reporta. No borra ni
 * renombra ningún curso. Es idempotente y seguro de re-ejecutar (nunca crea ni
 * borra materias). Además llena `docente_materia` con los pares de los cursos.
 *
 * Por seguridad se niega a correr contra una base no local, salvo con
 * `--permitir-remoto` (para el paso gatillado contra Supabase, con respaldo y
 * confirmación).
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial.
 * - 2026-11-28  [Diego Tejerina]  refactor: solo engancha al catálogo (no crea
 *   materias "agregadas"); sin materia -> null y se reporta.
 *
 * @see  App\Support\ParseoNombreCurso
 * @see  App\Models\Materia
 */

namespace App\Console\Commands;

use App\Models\Curso;
use App\Models\Materia;
use App\Support\ParseoNombreCurso;
use App\Support\Validadores;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AsignarMateriaGrupoCursos extends Command
{
    protected $signature = 'cursos:asignar-materia-grupo {--permitir-remoto : Permite correr contra una base no local}';

    protected $description = 'Engancha los cursos existentes al catálogo de materias y les asigna el grupo, interpretando nombre_curso.';

    public function handle(): int
    {
        if (! $this->conexionLocal() && ! $this->option('permitir-remoto')) {
            $this->error('La conexion no es local. Use --permitir-remoto solo con respaldo y confirmacion.');

            return self::FAILURE;
        }

        // Solo se engancha a materias del catálogo (las que tienen código).
        $oficialPorNombre = Materia::query()->whereNotNull('codigo')->pluck('id_materia', 'nombre_normalizado');

        $filas = [];
        $sinGrupo = 0;
        $sinMateria = 0;
        $paresDocenteMateria = [];

        DB::transaction(function () use (&$filas, &$sinGrupo, &$sinMateria, &$paresDocenteMateria, $oficialPorNombre): void {
            foreach (Curso::query()->orderBy('id_curso')->get() as $curso) {
                ['materia' => $textoMateria, 'grupo' => $grupo] = ParseoNombreCurso::interpretar((string) $curso->nombre_curso);

                $normalizado = Validadores::normalizarNombreMateria($textoMateria);
                $idMateria = $oficialPorNombre[$normalizado] ?? null;

                $curso->id_materia = $idMateria;
                $curso->numero_grupo = $grupo;
                $curso->save();

                if ($idMateria === null) {
                    $sinMateria++;
                } else {
                    $paresDocenteMateria[(int) $curso->sis_doc.'-'.$idMateria] = [
                        'id_docente' => (int) $curso->sis_doc,
                        'id_materia' => $idMateria,
                    ];
                }

                if ($grupo === null) {
                    $sinGrupo++;
                }

                $filas[] = [
                    $curso->id_curso,
                    (string) $curso->nombre_curso,
                    $idMateria !== null ? $textoMateria : 'SIN MATERIA (revisar)',
                    $grupo ?? 'SIN GRUPO',
                ];
            }

            foreach ($paresDocenteMateria as $par) {
                DB::table('docente_materia')->insertOrIgnore($par);
            }
        });

        if ($filas === []) {
            $this->info('No hay cursos para migrar.');

            return self::SUCCESS;
        }

        $this->table(['Curso', 'nombre_curso', 'Materia del catálogo', 'Grupo'], $filas);
        $this->info(sprintf(
            'Cursos: %d | sin materia del catálogo (revisar): %d | sin grupo deducible (revisar): %d',
            count($filas),
            $sinMateria,
            $sinGrupo,
        ));

        return self::SUCCESS;
    }

    private function conexionLocal(): bool
    {
        $conexion = (string) config('database.default');
        $host = (string) config("database.connections.{$conexion}.host");

        return in_array($host, ['127.0.0.1', 'localhost', '::1', ''], true);
    }
}
