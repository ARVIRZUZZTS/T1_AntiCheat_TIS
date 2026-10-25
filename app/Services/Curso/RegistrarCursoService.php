<?php

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Examen\RegistrarExamenService;
use App\Support\DTO\RegistrarCursoDTO;
use App\Support\DTO\ResumenCursoDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrarCursoService
{
    public function __construct(
        private RegistrarExamenService $examenes,
    ) {}

    /**
     * Registra el curso completo en una sola transaccion.
     *
     * @throws ValidationException Si un catalogo no existe o el curso esta duplicado.
     */
    public function ejecutar(Usuario $docente, RegistrarCursoDTO $datos): ResumenCursoDTO
    {
        Gate::forUser($docente)->authorize('create', Curso::class);

        return DB::transaction(function () use ($docente, $datos): ResumenCursoDTO {
            DB::statement('LOCK TABLE curso IN SHARE ROW EXCLUSIVE MODE');

            [$gestion, $ambiente] = $this->validarCatalogos($datos);
            $this->validarQueNoEsteDuplicado($docente, $datos);

            $curso = new Curso;
            $curso->id_curso = ((int) Curso::query()->max('id_curso')) + 1;
            $curso->nombre_curso = $datos->nombreCurso();
            $curso->sis_doc = (int) $docente->id_usuario;
            $curso->id_ambiente = $datos->idAmbiente;
            $curso->fecha_creacion = Carbon::today()->toDateString();
            $curso->estado = CursoEstado::EnCurso->value;
            $curso->save();

            DB::table('curso_tipo_gestion')->insert([
                'id_curso' => $curso->id_curso,
                'id_tg' => $datos->gestion->idTipoGestion,
                'anio' => $datos->gestion->anio,
            ]);

            foreach ($datos->examenes as $examen) {
                $this->examenes->crearParaCurso(
                    $curso,
                    $examen,
                    (int) $docente->id_usuario,
                    $datos->idAmbiente,
                );
            }

            return new ResumenCursoDTO(
                idCurso: (int) $curso->id_curso,
                materia: $datos->materia,
                grupo: $datos->grupo,
                idGestion: $datos->gestion->idTipoGestion,
                nombreGestion: Str::ucfirst((string) $gestion->nombre_tipo_gestion),
                anio: $datos->gestion->anio,
                idAmbiente: $datos->idAmbiente,
                nombreAmbiente: (string) $ambiente->nombre_ambiente,
                cantidadExamenes: count($datos->examenes),
            );
        });
    }

    /**
     * Valida los catalogos en consultas por lote, no una consulta por examen.
     *
     * @return array{0: object, 1: object}
     */
    private function validarCatalogos(RegistrarCursoDTO $datos): array
    {
        $gestion = DB::table('tipo_gestion')
            ->where('id_tipo_gestion', $datos->gestion->idTipoGestion)
            ->first(['id_tipo_gestion', 'nombre_tipo_gestion']);

        if ($gestion === null) {
            throw ValidationException::withMessages([
                'gestion' => 'Seleccione una gestion valida.',
            ]);
        }

        $ambiente = DB::table('ambiente')
            ->where('id_ambiente', $datos->idAmbiente)
            ->first(['id_ambiente', 'nombre_ambiente']);

        if ($ambiente === null) {
            throw ValidationException::withMessages([
                'ambiente_id' => 'Seleccione un ambiente valido.',
            ]);
        }

        $normas = [];
        $materiales = [];

        foreach ($datos->examenes as $examen) {
            array_push($normas, ...$examen->normasIds);
            array_push($materiales, ...$examen->materialesIds);
        }

        $normasValidas = DB::table('norma')
            ->whereIn('id_norma', array_values(array_unique($normas)))
            ->pluck('id_norma')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $materialesValidos = DB::table('material')
            ->whereIn('id_material', array_values(array_unique($materiales)))
            ->pluck('id_material')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        foreach ($datos->examenes as $indice => $examen) {
            if (array_diff($examen->normasIds, $normasValidas) !== []) {
                throw ValidationException::withMessages([
                    "examenes.{$indice}.normas_ids" => 'Una de las normas seleccionadas no existe.',
                ]);
            }

            if (array_diff($examen->materialesIds, $materialesValidos) !== []) {
                throw ValidationException::withMessages([
                    "examenes.{$indice}.materiales_ids" => 'Uno de los materiales seleccionados no existe.',
                ]);
            }
        }

        return [$gestion, $ambiente];
    }

    private function validarQueNoEsteDuplicado(Usuario $docente, RegistrarCursoDTO $datos): void
    {
        $duplicado = DB::table('curso')
            ->join('curso_tipo_gestion', 'curso.id_curso', '=', 'curso_tipo_gestion.id_curso')
            ->where('curso.nombre_curso', $datos->nombreCurso())
            ->where('curso.sis_doc', (int) $docente->id_usuario)
            ->where('curso_tipo_gestion.id_tg', $datos->gestion->idTipoGestion)
            ->where('curso_tipo_gestion.anio', $datos->gestion->anio)
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'materia' => 'Ya tiene un curso con esa materia, grupo y gestion.',
            ]);
        }
    }
}
