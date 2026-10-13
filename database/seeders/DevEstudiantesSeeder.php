<?php

/**
 * @file    DevEstudiantesSeeder.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-12
 *
 * @description
 * Seeder SOLO de desarrollo: agrega 20 estudiantes al curso 1 ("Calculo I"),
 * inscritos en su examen actual, con los cuatro estados/observaciones que
 * necesita la vista de estudiantes del curso para probarse de verdad en el
 * navegador — avatares con nombres de distinta longitud, badges de estado y
 * observación, motivo de deshabilitación, y suficientes filas como para que
 * aparezca la paginación:
 *
 *   -  6 habilitados, sin observación
 *   -  5 deshabilitados, cada uno con su motivo
 *   -  4 con observación "sospechoso" (mezcla habilitado/deshabilitado)
 *   -  3 con observación "tramposo" (mezcla habilitado/deshabilitado)
 *   -  2 sin ninguna fila en estudiante_examen ("Sin estado")
 *
 * Usa el rango de SIS 90500001-90500020, que no choca con los datos reales
 * del seed (20210001-20210005) ni con los rangos 900000+/900990+ que usan
 * los tests. Es re-ejecutable: primero borra cualquier fila suya de una
 * corrida anterior (por ese mismo rango de SIS) antes de volver a insertar.
 *
 * NO se registra en DatabaseSeeder (no existe ese archivo en este proyecto
 * todavía) ni corre con `php artisan migrate:fresh --seed`. Se ejecuta a
 * mano, una sola vez, cuando se necesite probar la pantalla con datos:
 *
 *     php artisan db:seed --class=DevEstudiantesSeeder
 *
 * Se niega a correr si la conexión activa (la que diga el .env en ese
 * momento) no apunta a un host local conocido — aborta con un mensaje en
 * vez de escribir nada. Nunca lo corras contra Supabase ni otra base
 * compartida con el equipo.
 *
 * @changelog
 * - 2026-10-12  [Diego Tejerina]  feat: creación inicial del seeder.
 * - 2026-10-12  [Diego Tejerina]  fix: aborta si la conexión activa no es
 *   local (127.0.0.1/localhost/el servicio de Docker), para que no se
 *   pueda correr sin querer contra Supabase u otra base compartida.
 */

namespace Database\Seeders;

use App\Enums\EstadoIncidencia;
use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevEstudiantesSeeder extends Seeder
{
    private const ID_CURSO = 1;

    private const PREFIJO_SIS = '905000';

    private const CANTIDAD = 20;

    /**
     * Hosts que se consideran "base local de desarrollo". Cualquier otro
     * (Supabase, un servidor remoto, lo que sea) aborta el seeder: esto NO
     * es para correr contra una base compartida con el equipo.
     *
     * @var list<string>
     */
    private const HOSTS_LOCALES = ['127.0.0.1', 'localhost', '::1', 'postgres', 'db'];

    public function run(): void
    {
        if (! $this->conexionEsLocal()) {
            $this->command?->error(
                'DevEstudiantesSeeder no corre: la conexión actual apunta a "'
                .config('database.connections.pgsql.host').'", que no es una base local '
                .'(127.0.0.1/localhost/el servicio de Docker). Este seeder es solo para '
                .'desarrollo local; nunca lo corras contra Supabase ni ninguna base compartida.'
            );

            return;
        }

        $curso = Curso::find(self::ID_CURSO);

        if (! $curso) {
            $this->command?->error('No existe el curso 1: corré primero el seed base del proyecto.');

            return;
        }

        $idExamen = $curso->examenActual()?->id_examen;

        if ($idExamen === null) {
            $this->command?->error('El curso 1 no tiene un examen actual: no hay a qué inscribir a los estudiantes.');

            return;
        }

        $idDocente = $curso->sis_doc;

        $estudiantes = [
            // Habilitados, sin observación. Nombres de distinta longitud,
            // para ver también que el avatar se quede en 2 iniciales.
            ['nombre' => 'Ana', 'apellido' => 'Rojas', 'estado' => 'habilitado'],
            ['nombre' => 'Luis Alberto', 'apellido' => 'Quispe Mamani', 'estado' => 'habilitado'],
            ['nombre' => 'Maria Fernanda', 'apellido' => 'Gutierrez Soliz', 'estado' => 'habilitado'],
            ['nombre' => 'Jorge', 'apellido' => 'Flores', 'estado' => 'habilitado'],
            ['nombre' => 'Daniela', 'apellido' => 'Condori Vargas', 'estado' => 'habilitado'],
            ['nombre' => 'Esteban', 'apellido' => 'Nina', 'estado' => 'habilitado'],

            // Deshabilitados, cada uno con su motivo.
            ['nombre' => 'Patricia', 'apellido' => 'Villca', 'estado' => 'deshabilitado', 'motivo' => 'No asistio al laboratorio previo'],
            ['nombre' => 'Gonzalo', 'apellido' => 'Zambrana', 'estado' => 'deshabilitado', 'motivo' => 'Pago pendiente de matricula'],
            ['nombre' => 'Ursula', 'apellido' => 'Mendez', 'estado' => 'deshabilitado', 'motivo' => 'No entrego el trabajo practico'],
            ['nombre' => 'Walter', 'apellido' => 'Aguilar', 'estado' => 'deshabilitado', 'motivo' => 'Inasistencia injustificada'],
            ['nombre' => 'Zulma', 'apellido' => 'Peredo', 'estado' => 'deshabilitado', 'motivo' => 'Nota insuficiente en la practica'],

            // Observacion "sospechoso" (mezcla de estado de habilitacion).
            ['nombre' => 'Ignacio', 'apellido' => 'Rivera', 'estado' => 'habilitado', 'infraccion' => 'sospechoso'],
            ['nombre' => 'Noelia', 'apellido' => 'Cardenas', 'estado' => 'deshabilitado', 'motivo' => 'Observado por posible copia', 'infraccion' => 'sospechoso'],
            ['nombre' => 'Benjamin', 'apellido' => 'Torrico', 'estado' => 'habilitado', 'infraccion' => 'sospechoso'],
            ['nombre' => 'Carla', 'apellido' => 'Soria', 'estado' => 'deshabilitado', 'motivo' => 'Observado por posible copia', 'infraccion' => 'sospechoso'],

            // Observacion "tramposo" (mezcla de estado de habilitacion).
            ['nombre' => 'Ruben', 'apellido' => 'Paredes', 'estado' => 'habilitado', 'infraccion' => 'tramposo'],
            ['nombre' => 'Alejandra', 'apellido' => 'Choque Yucra', 'estado' => 'deshabilitado', 'motivo' => 'Ingreso a central de riesgos', 'infraccion' => 'tramposo'],
            ['nombre' => 'Hugo', 'apellido' => 'Siles', 'estado' => 'habilitado', 'infraccion' => 'tramposo'],

            // Sin ninguna fila en estudiante_examen: debe verse "Sin estado".
            ['nombre' => 'Vanesa', 'apellido' => 'Arce', 'estado' => null],
            ['nombre' => 'Marco', 'apellido' => 'Illanes', 'estado' => null],
        ];

        if (count($estudiantes) !== self::CANTIDAD) {
            throw new \LogicException('La lista de estudiantes del seeder no tiene '.self::CANTIDAD.' elementos.');
        }

        $this->limpiarCorridaAnterior();

        $idEstudianteExamen = (int) (DB::table('estudiante_examen')->max('id_estudiante_examen') ?? 900000) + 1;
        $idRegistro = (int) (DB::table('central_riesgo')->max('id_registro') ?? 900000) + 1;

        foreach ($estudiantes as $indice => $datos) {
            $sis = self::PREFIJO_SIS.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT);

            Estudiante::create([
                'sis_estudiante' => $sis,
                'nombre_estudiante' => $datos['nombre'],
                'apellido_estudiante' => $datos['apellido'],
                'carrera' => 'Sistemas',
            ]);

            DB::table('estudiante_curso')->insert([
                'sis_estudiante' => $sis,
                'id_curso' => self::ID_CURSO,
            ]);

            if ($datos['estado'] !== null) {
                EstudianteExamen::create([
                    'id_estudiante_examen' => $idEstudianteExamen++,
                    'sis_estudiante' => $sis,
                    'id_examen' => $idExamen,
                    'estado' => $datos['estado'],
                    'motivo' => $datos['motivo'] ?? null,
                ]);
            }

            if (isset($datos['infraccion'])) {
                CentralRiesgo::create([
                    'id_registro' => $idRegistro++,
                    'id_registrador' => $idDocente,
                    'detalle_motivo' => 'Generado por DevEstudiantesSeeder para pruebas.',
                    'fecha_registro' => now(),
                    'tipo_infraccion' => $datos['infraccion'],
                    'estado_incidencia' => EstadoIncidencia::Pendiente,
                    'id_curso' => self::ID_CURSO,
                    'sis_estudiante' => $sis,
                    'id_examen' => $idExamen,
                    'motivo' => 'otro',
                ]);
            }
        }

        $this->command?->info(self::CANTIDAD.' estudiantes de prueba agregados al curso 1 (SIS '.self::PREFIJO_SIS.'01 a '.self::PREFIJO_SIS.'20).');
    }

    /**
     * Borra lo que haya quedado de una corrida anterior de este mismo
     * seeder, identificado por el prefijo de SIS, para que correrlo de
     * nuevo no duplique estudiantes ni choque con la clave primaria de
     * estudiante.sis_estudiante.
     */
    private function limpiarCorridaAnterior(): void
    {
        $sisAnteriores = DB::table('estudiante')
            ->where('sis_estudiante', 'like', self::PREFIJO_SIS.'%')
            ->pluck('sis_estudiante');

        if ($sisAnteriores->isEmpty()) {
            return;
        }

        DB::table('central_riesgo')->whereIn('sis_estudiante', $sisAnteriores)->delete();
        DB::table('estudiante_examen')->whereIn('sis_estudiante', $sisAnteriores)->delete();
        DB::table('estudiante_curso')->whereIn('sis_estudiante', $sisAnteriores)->delete();
        DB::table('estudiante')->whereIn('sis_estudiante', $sisAnteriores)->delete();
    }

    /**
     * true si la conexión activa apunta a una base local de desarrollo.
     *
     * Revisa el host de la conexión `pgsql` (la que usa toda la app) contra
     * una lista de hosts locales conocidos, y además descarta explícitamente
     * cualquier host de Supabase como resguardo extra.
     */
    private function conexionEsLocal(): bool
    {
        $host = (string) config('database.connections.pgsql.host');

        if (str_contains($host, 'supabase.co')) {
            return false;
        }

        return in_array($host, self::HOSTS_LOCALES, true);
    }
}
