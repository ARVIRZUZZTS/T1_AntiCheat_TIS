<?php

/**
 * @file    MateriaCatalogoSeeder.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Carga el catálogo de materias de Ingeniería de Sistemas (FCyT-UMSS),
 * semestres 1 a 5, con su código SIS, nombre, semestre y prerrequisitos. Datos
 * verificados del plan de estudios (la fuente web
 * https://sistemas.cs.umss.edu.bo/materias respondió 404 al intentar completar
 * los semestres 6 a 9; no se inventan códigos ni nombres).
 *
 * Es idempotente: cada materia se ubica por su código; correrlo dos veces no
 * duplica. Y se niega a correr si la conexión no es local, para no tocar
 * Supabase por accidente.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: catálogo de materias (sem. 1-5).
 *
 * @see  App\Models\Materia
 */

namespace Database\Seeders;

use App\Models\Materia;
use App\Support\Validadores;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MateriaCatalogoSeeder extends Seeder
{
    /**
     * Materias por código: [codigo => [nombre, semestre, [prerrequisitos...]]].
     * Fuente: plan de estudios Ingeniería de Sistemas FCyT-UMSS.
     *
     * @var array<string, array{0: string, 1: int, 2: list<string>}>
     */
    private const MATERIAS = [
        // Semestre 1
        '2008019' => ['Álgebra I', 1, []],
        '2008054' => ['Cálculo I', 1, []],
        '2006063' => ['Física General', 1, []],
        '1803001' => ['Inglés I', 1, []],
        '2010010' => ['Introducción a la Programación', 1, []],
        '2010140' => ['Metodología', 1, []],
        // Semestre 2
        '2008022' => ['Álgebra II', 2, ['2008019']],
        '2010013' => ['Arquitectura de Computadoras I', 2, ['2006063']],
        '2008056' => ['Cálculo II', 2, ['2008054']],
        '2010003' => ['Elem. de Programación y Estruc. de Datos', 2, ['2010010', '2010140']],
        '2008057' => ['Matemática Discreta', 2, ['2008054']],
        // Semestre 3
        '2010015' => ['Base de Datos I', 3, ['2010003']],
        '2008060' => ['Cálculo Numérico', 3, ['2008057']],
        '2010141' => ['Circuitos Electrónicos', 3, ['2010013']],
        '2008058' => ['Ecuaciones Diferenciales', 3, ['2008056']],
        '2008059' => ['Estadística I', 3, ['2008022']],
        '2010012' => ['Métodos, Técnicas y Taller de Programación', 3, ['2010003']],
        // Semestre 4
        '2010016' => ['Base de Datos II', 4, ['2010015']],
        '2016046' => ['Contabilidad Básica', 4, ['2008060']],
        '2008061' => ['Estadística II', 4, ['2008059']],
        '2016048' => ['Investigación Operativa I', 4, ['2008058']],
        '2010018' => ['Sistemas de Información I', 4, ['2010012']],
        '2010017' => ['Taller de Sistemas Operativos', 4, ['2010141']],
        // Semestre 5
        '2010035' => ['Aplicación de Sistemas Operativos', 5, ['2010017']],
        '1803002' => ['Inglés II', 5, ['1803001']],
        '2016051' => ['Investigación Operativa II', 5, ['2016048']],
        '2016057' => ['Mercadotecnia', 5, ['2008061']],
        '2010022' => ['Sistemas de Información II', 5, ['2010018']],
        '2010142' => ['Sistemas I', 5, ['2016046']],
        '2010053' => ['Taller de Base de Datos', 5, ['2010016']],
        // Semestre 6
        '2010020' => ['Ingeniería de Software', 6, ['2010022']],
        '2010027' => ['Inteligencia Artificial', 6, ['2010053']],
        '2010047' => ['Redes de Computadoras', 6, ['2010035']],
        '2010019' => ['Simulación de Sistemas', 6, ['2016051']],
        '2010144' => ['Sistemas Económicos', 6, ['2016057']],
        '2010143' => ['Sistemas II', 6, ['2010142']],
        // Semestre 7
        '2010186' => ['Dinámica de Sistemas', 7, ['2010019']],
        '2014087' => ['Electrotecnia Industrial', 7, []],
        '2010145' => ['Gestión de Calidad de Software', 7, ['2010027']],
        '2016092' => ['Planificación y Evaluación de Proyectos', 7, ['2010144']],
        '2010176' => ['Programación de Sistemas Paralelos', 7, ['2010143']],
        '2010146' => ['Redes Avanzadas de Computadoras', 7, ['2010047']],
        '2010024' => ['Taller de Ingeniería de Software', 7, ['2010020']],
        // Semestre 8
        '2010102' => ['Evaluación y Auditoría de Sistemas', 8, ['2010145']],
        '2016059' => ['Gestión Estratégica de Empresas', 8, ['2016092']],
        '2010210' => ['Informática Forense', 8, ['2010016']],
        '1803009' => ['Inglés III', 8, ['1803002']],
        '2010119' => ['Metodol. y Planif. de Proyecto de Grado', 8, ['2010024']],
        '2010209' => ['Seguridad de Sistemas', 8, ['2010146']],
        '2010116' => ['Taller de Simulación de Sistemas', 8, ['2010186']],
        // Semestre 9
        '2010218' => ['Ciencia de Datos y Machine Learning', 9, ['2010020']],
        '2016049' => ['Costos Industriales', 9, []],
        '2010032' => ['Data Warehouse', 9, []],
        '2010178' => ['Entornos Virtuales de Aprendizaje', 9, ['2010020']],
        '2016052' => ['Ingeniería de Métodos y Reingeniería', 9, []],
        '2016023' => ['Ingeniería Económica', 9, []],
        '2016021' => ['Planif. y Control de la Producción I', 9, []],
        '2016027' => ['Planif. y Control de la Producción II', 9, []],
        '2010147' => ['Práctica Empresarial', 9, ['2010119']],
        '2010122' => ['Proyecto Final', 9, ['2010119']],
        '2010189' => ['Reconocimiento de Voz', 9, ['2010027']],
        '2010188' => ['Servicios Telemáticos', 9, ['2010047']],
        // Semestre 10
        '2010192' => ['Transmisión IP', 10, []],
    ];

    public function run(): void
    {
        $this->exigirConexionLocal();

        // 1) Materias (idempotente por código).
        foreach (self::MATERIAS as $codigo => [$nombre, $semestre]) {
            Materia::query()->updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'nombre_normalizado' => Validadores::normalizarNombreMateria($nombre),
                    'semestre' => $semestre,
                    'origen' => Materia::ORIGEN_CATALOGO,
                ],
            );
        }

        // 2) Prerrequisitos (idempotente: solo agrega los que faltan).
        $idPorCodigo = Materia::query()->pluck('id_materia', 'codigo');

        foreach (self::MATERIAS as $codigo => [, , $prerrequisitos]) {
            $idMateria = $idPorCodigo[$codigo] ?? null;
            if ($idMateria === null) {
                continue;
            }

            foreach ($prerrequisitos as $codigoPrerreq) {
                $idPrerreq = $idPorCodigo[$codigoPrerreq] ?? null;
                if ($idPrerreq === null) {
                    continue;
                }

                DB::table('materia_prerrequisito')->insertOrIgnore([
                    'id_materia' => $idMateria,
                    'id_prerrequisito' => $idPrerreq,
                ]);
            }
        }
    }

    /**
     * Impide sembrar contra una base que no sea local (protege Supabase).
     *
     * @throws RuntimeException  Si el host de la conexión no es local.
     */
    private function exigirConexionLocal(): void
    {
        $conexion = (string) config('database.default');
        $host = (string) config("database.connections.{$conexion}.host");

        if (in_array($host, ['127.0.0.1', 'localhost', '::1', ''], true)) {
            return;
        }

        // Salida explicita para el paso gatillado contra la base compartida
        // (con respaldo y confirmacion del usuario): SEED_MATERIAS_REMOTO=1.
        if ((string) env('SEED_MATERIAS_REMOTO') === '1') {
            return;
        }

        throw new RuntimeException(
            "MateriaCatalogoSeeder se niega a correr: la conexion '{$conexion}' apunta a '{$host}', que no es local. "
            .'Use SEED_MATERIAS_REMOTO=1 solo con respaldo y confirmacion.',
        );
    }
}
