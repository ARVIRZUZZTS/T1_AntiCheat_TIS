<?php

/**
 * @file    RegistrarIncidenciaService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-25
 *
 * @updated 2026-10-26
 *
 * @description
 * Registro de una incidencia en la central de riesgos desde el monitor en vivo
 * (HU 5). Es el único camino de registro: lo usan el registro rápido del
 * ingreso, el del monitor y el del estudiante no inscrito.
 *
 * Reglas:
 *  - Tipo según el rol del registrador: un docente registra `tramposo`
 *    (confirmado) y un auxiliar `sospechoso` (en revisión); el tipo es
 *    también el estado de la incidencia.
 *  - Motivo de la lista del origen (Motivo::paraIngreso() o paraMonitor()).
 *    La descripción admite hasta 300 caracteres y es obligatoria con "Otro".
 *  - Si el SIS no existe en la base, se da de alta al estudiante con los datos
 *    que dio (SIS, nombres y apellidos, obligatorios) como no verificado.
 *  - Curso: si el estudiante está inscrito en el examen, el de su inscripción;
 *    si no, el primer curso del registrador en ese examen (para un auxiliar,
 *    el primero que tiene asignado).
 *
 * Los errores de validación se lanzan como InvalidArgumentException con un
 * mensaje para mostrar (el monitor los muestra tal cual).
 *
 * @changelog
 * - 2026-10-25  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 * - 2026-10-26  [Valery D. Ortuno P]  perf: el estudiante y su inscripción salen
 *   de una sola consulta, y la incidencia llega a las notificaciones con el
 *   estudiante ya cargado (no lo vuelven a consultar): dos viajes menos.
 *
 * @see  App\Support\DTO\RegistroIncidenciaDTO
 * @see  App\Livewire\Monitoreo\MonitorEnVivo
 * @see  App\Services\Notificacion\NotificacionService
 */

namespace App\Services\CentralRiesgo;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use App\Support\DTO\RegistroIncidenciaDTO;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarIncidenciaService
{
    /** Tope de caracteres de la descripción del hecho. */
    public const DESCRIPCION_MAXIMO = 300;

    /** Tope de caracteres de nombres y de apellidos (columnas de `estudiante`). */
    public const NOMBRE_MAXIMO = 50;

    /** SIS: solo dígitos, 8 o 9 (los que ya tiene la base). */
    private const PATRON_SIS = '/^\d{8,9}$/';

    /** Nombres y apellidos: letras (con tildes y ñ) y espacios. */
    private const PATRON_NOMBRE = '/^[\p{L} ]+$/u';

    public function __construct(
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * Valida y registra la incidencia.
     *
     * @param  RegistroIncidenciaDTO  $datos  Lo que se llenó en el modal.
     * @param  Usuario  $registrador  Docente o auxiliar que registra.
     * @param  int  $idDocente  Docente cuya configuración del examen sigue el monitor.
     *
     * @return CentralRiesgo  Incidencia registrada.
     *
     * @throws InvalidArgumentException  Si un dato no es válido (mensaje para mostrar).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    public function ejecutar(RegistroIncidenciaDTO $datos, Usuario $registrador, int $idDocente): CentralRiesgo
    {
        $sis = trim($datos->sis);
        $motivo = $this->validarMotivo($datos);
        $detalle = $this->validarDetalle($motivo, $datos->detalle);

        if (preg_match(self::PATRON_SIS, $sis) !== 1) {
            throw new InvalidArgumentException('Ingrese un codigo SIS valido (8 o 9 digitos).');
        }

        $esAuxiliar = self::esAuxiliar($registrador);

        $registro = DB::transaction(function () use ($datos, $sis, $motivo, $detalle, $registrador, $idDocente, $esAuxiliar): CentralRiesgo {
            $estudiante = $this->estudianteConInscripcion($sis, $datos->idExamen, $idDocente)
                ?? $this->altaNoVerificado($sis, $datos);
            $idInscripcion = $estudiante->getAttribute('inscripcion_id');
            $idCursoInscripcion = $estudiante->getAttribute('inscripcion_curso');

            DB::statement('LOCK TABLE central_riesgo IN SHARE ROW EXCLUSIVE MODE');

            $incidencia = CentralRiesgo::create([
                'id_registro' => (int) CentralRiesgo::query()->max('id_registro') + 1,
                'sis_estudiante' => $estudiante->sis_estudiante,
                'id_examen' => $datos->idExamen,
                'id_curso' => $idCursoInscripcion !== null
                    ? (int) $idCursoInscripcion
                    : $this->cursoDelRegistrador($registrador, $datos->idExamen, $esAuxiliar),
                'id_estudiante_examen' => $idInscripcion !== null ? (int) $idInscripcion : null,
                'id_registrador' => $registrador->id_usuario,
                'motivo' => $motivo->value,
                'detalle_motivo' => $detalle,
                'fecha_registro' => now(),
                'tipo_infraccion' => $datos->origen === RegistroIncidenciaDTO::ORIGEN_INGRESO
                    ? TipoInfraccion::Sospechoso->value
                    : ($esAuxiliar
                        ? TipoInfraccion::Sospechoso->value
                        : TipoInfraccion::Tramposo->value),
            ]);

            // Las notificaciones leen el estudiante de la incidencia: ya lo
            // tenemos, así que no hace falta otra consulta.
            return $incidencia->setRelation('estudiante', $estudiante);
        });

        // El servicio de notificaciones decide qué aviso aplica: "Posible
        // tramposo" al docente del curso si lo reporta un auxiliar (HU 12), o
        // "Ingresado a la central" a sus docentes si queda como tramposo (HU 6).
        $this->notificaciones->notificarPosibleTramposo($registro);
        $this->notificaciones->notificarCentralRiesgo($registro);

        return $registro;
    }

    /**
     * Indica si el usuario registra como auxiliar (sin rol docente).
     *
     * @param  Usuario  $usuario  Registrador.
     *
     * @return bool  Si su incidencia queda como sospechoso (en revisión).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    public static function esAuxiliar(Usuario $usuario): bool
    {
        $roles = $usuario->roles->pluck('nombre_rol');

        return $roles->contains(Rol::NOMBRE_AUXILIAR) && ! $roles->contains(Rol::NOMBRE_DOCENTE);
    }

    /**
     * @throws InvalidArgumentException  Si el motivo no es de la lista del origen.
     */
    private function validarMotivo(RegistroIncidenciaDTO $datos): Motivo
    {
        $permitidos = match ($datos->origen) {
            RegistroIncidenciaDTO::ORIGEN_INGRESO => Motivo::paraIngreso(),
            RegistroIncidenciaDTO::ORIGEN_MONITOR => Motivo::paraMonitor(),
            default => throw new InvalidArgumentException('Origen de la incidencia no valido.'),
        };

        $motivo = Motivo::tryFrom($datos->motivo);

        if ($motivo === null || ! in_array($motivo, $permitidos, true)) {
            throw new InvalidArgumentException('Seleccione un motivo de la lista.');
        }

        return $motivo;
    }

    /**
     * @return ?string  La descripción sin espacios sobrantes, o null si quedó vacía.
     *
     * @throws InvalidArgumentException  Si supera el tope o falta con el motivo "Otro".
     */
    private function validarDetalle(Motivo $motivo, ?string $detalle): ?string
    {
        $detalle = trim((string) $detalle);

        if (mb_strlen($detalle) > self::DESCRIPCION_MAXIMO) {
            throw new InvalidArgumentException('La descripcion no debe superar los '.self::DESCRIPCION_MAXIMO.' caracteres.');
        }

        if ($motivo === Motivo::Otro && $detalle === '') {
            throw new InvalidArgumentException('Describa el hecho cuando el motivo es Otro.');
        }

        return $detalle === '' ? null : $detalle;
    }

    /**
     * El estudiante con su inscripción en el examen (la del docente cuya
     * configuración se usa), en una sola consulta: la inscripción viaja como
     * `inscripcion_id` e `inscripcion_curso`, null si no está inscrito.
     *
     * @return ?Estudiante  Null si el SIS no está en la base.
     */
    private function estudianteConInscripcion(string $sis, int $idExamen, int $idDocente): ?Estudiante
    {
        $inscripcion = fn (string $columna) => EstudianteExamen::query()
            ->select($columna)
            ->whereColumn('estudiante_examen.sis_estudiante', 'estudiante.sis_estudiante')
            ->where('estudiante_examen.id_examen', $idExamen)
            ->delDocente($idDocente)
            ->limit(1);

        return Estudiante::query()
            ->select('estudiante.*')
            ->addSelect([
                'inscripcion_id' => $inscripcion('id_estudiante_examen'),
                'inscripcion_curso' => $inscripcion('id_curso'),
            ])
            ->whereKey($sis)
            ->first();
    }

    /**
     * Da de alta al estudiante que no estaba en la base con los datos que dio.
     * Queda como no verificado y sin curso: no aparece en las listas de curso.
     *
     * @throws InvalidArgumentException  Si faltan o no son válidos los nombres o apellidos.
     */
    private function altaNoVerificado(string $sis, RegistroIncidenciaDTO $datos): Estudiante
    {
        $nombre = $this->validarNombre($datos->nombre, 'los nombres');
        $apellido = $this->validarNombre($datos->apellido, 'los apellidos');

        return Estudiante::query()->create([
            'sis_estudiante' => $sis,
            'nombre_estudiante' => $nombre,
            'apellido_estudiante' => $apellido,
            'carrera' => null,
            'verificado' => false,
        ]);
    }

    /**
     * @throws InvalidArgumentException  Si el texto está vacío, es muy largo o trae otros caracteres.
     */
    private function validarNombre(?string $texto, string $campo): string
    {
        $texto = preg_replace('/\s+/u', ' ', trim((string) $texto));

        if ($texto === '') {
            throw new InvalidArgumentException('El estudiante no esta registrado: ingrese '.$campo.'.');
        }

        if (mb_strlen($texto) > self::NOMBRE_MAXIMO || preg_match(self::PATRON_NOMBRE, $texto) !== 1) {
            throw new InvalidArgumentException('Revise '.$campo.': solo letras, hasta '.self::NOMBRE_MAXIMO.' caracteres.');
        }

        return $texto;
    }

    /**
     * Primer curso del registrador en el examen, para un estudiante que no
     * está inscrito en él. Si no se encuentra ninguno, el primer curso del
     * examen, para que la incidencia nunca quede sin curso.
     *
     * @return ?int  id_curso, o null si el examen no tiene cursos.
     */
    private function cursoDelRegistrador(Usuario $registrador, int $idExamen, bool $esAuxiliar): ?int
    {
        $consulta = DB::table('examen_curso as ec')
            ->where('ec.id_examen', $idExamen)
            ->orderBy('ec.id_curso');

        $propio = $esAuxiliar
            ? (clone $consulta)
                ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
                ->where('ac.id_auxiliar', $registrador->id_usuario)
                ->value('ec.id_curso')
            : (clone $consulta)
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->where('c.sis_doc', $registrador->id_usuario)
                ->value('ec.id_curso');

        $idCurso = $propio ?? $consulta->value('ec.id_curso');

        return $idCurso === null ? null : (int) $idCurso;
    }
}
