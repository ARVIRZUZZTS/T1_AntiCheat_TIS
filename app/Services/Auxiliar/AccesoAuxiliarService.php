<?php

/**
 * @file    AccesoAuxiliarService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Que examenes ve un auxiliar en cada pagina de Monitor en vivo (HU 24):
 *
 *  - Asignado a un curso (`auxiliar_curso` en estado Activo): ve todos los
 *    examenes del curso en Ingreso y en Monitor.
 *  - Asignado a un examen (`auxiliar_examen`): ve ese examen solo en las
 *    paginas que el docente marco (`ver_ingreso`, `ver_monitor`).
 *
 * Lo usan Ingreso (AccesoMonitor), Monitor (MonitorEnVivo), la policy del
 * monitor y el sidebar del auxiliar, asi la regla vive en un solo lugar. Cada
 * metodo es una sola consulta (la base remota pesa ~380 ms por viaje).
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 *
 * @see  App\Livewire\Monitoreo\AccesoMonitor
 * @see  App\Livewire\Monitoreo\MonitorEnVivo
 * @see  App\Policies\ExamenPolicy::monitorear()
 */

namespace App\Services\Auxiliar;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccesoAuxiliarService
{
    public const INGRESO = 'ingreso';

    public const MONITOR = 'monitor';

    /** Valor de `auxiliar_curso.estado` (enum estado_usuario) que da acceso. */
    public const ESTADO_ACTIVO = 'Activo';

    /**
     * Consulta con los ids de los examenes que el auxiliar ve en la pagina,
     * para usar en un `whereIn('id_examen', ...)`.
     */
    public function idsExamenes(int $idAuxiliar, string $pagina): Builder
    {
        $columna = $this->columna($pagina);

        $porCurso = DB::table('examen_curso as ec')
            ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
            ->where('ac.id_auxiliar', $idAuxiliar)
            ->where('ac.estado', self::ESTADO_ACTIVO)
            ->select('ec.id_examen');

        $porExamen = DB::table('auxiliar_examen')
            ->where('id_auxiliar', $idAuxiliar)
            ->where($columna, true)
            ->select('id_examen');

        return $porCurso->union($porExamen);
    }

    /**
     * Si el auxiliar puede ver ese examen en la pagina.
     */
    public function puede(int $idAuxiliar, int $idExamen, string $pagina): bool
    {
        $columna = $this->columna($pagina);

        $fila = DB::selectOne(
            'select (exists (select 1 from examen_curso ec join auxiliar_curso ac on ac.id_curso = ec.id_curso'
            .' where ec.id_examen = ? and ac.id_auxiliar = ? and ac.estado = ?)'
            ." or exists (select 1 from auxiliar_examen ae where ae.id_examen = ? and ae.id_auxiliar = ? and ae.{$columna} = ?)) as puede",
            [$idExamen, $idAuxiliar, self::ESTADO_ACTIVO, $idExamen, $idAuxiliar, true],
        );

        return (bool) ($fila->puede ?? false);
    }

    /**
     * Paginas de Monitor en vivo que el auxiliar tiene habilitadas en algun
     * examen (o por algun curso), para armar su sidebar.
     *
     * @return array{ingreso: bool, monitor: bool}
     */
    public function paginas(int $idAuxiliar): array
    {
        $fila = DB::selectOne(
            'select exists (select 1 from auxiliar_curso where id_auxiliar = ? and estado = ?) as curso,'
            .' exists (select 1 from auxiliar_examen where id_auxiliar = ? and ver_ingreso = ?) as ingreso,'
            .' exists (select 1 from auxiliar_examen where id_auxiliar = ? and ver_monitor = ?) as monitor',
            [$idAuxiliar, self::ESTADO_ACTIVO, $idAuxiliar, true, $idAuxiliar, true],
        );

        $porCurso = (bool) ($fila->curso ?? false);

        return [
            self::INGRESO => $porCurso || (bool) ($fila->ingreso ?? false),
            self::MONITOR => $porCurso || (bool) ($fila->monitor ?? false),
        ];
    }

    /**
     * Docente cuya configuracion del examen sigue el auxiliar: el del curso
     * que tiene asignado o, si fue asignado al examen suelto, quien lo asigno.
     */
    public function docenteConfiguracion(int $idAuxiliar, int $idExamen): ?int
    {
        $fila = DB::selectOne(
            'select coalesce('
            .'(select c.sis_doc from examen_curso ec join auxiliar_curso ac on ac.id_curso = ec.id_curso'
            .' join curso c on c.id_curso = ec.id_curso'
            .' where ec.id_examen = ? and ac.id_auxiliar = ? and ac.estado = ? order by c.id_curso limit 1),'
            .' (select ae.asignado_por from auxiliar_examen ae where ae.id_examen = ? and ae.id_auxiliar = ?)'
            .') as docente',
            [$idExamen, $idAuxiliar, self::ESTADO_ACTIVO, $idExamen, $idAuxiliar],
        );

        return isset($fila->docente) ? (int) $fila->docente : null;
    }

    private function columna(string $pagina): string
    {
        return match ($pagina) {
            self::INGRESO => 'ver_ingreso',
            self::MONITOR => 'ver_monitor',
            default => throw new InvalidArgumentException("Pagina desconocida: {$pagina}"),
        };
    }
}
