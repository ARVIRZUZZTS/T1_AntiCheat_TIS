<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarConfiguracionExamenDocenteRequest;
use App\Models\ConfiguracionExamenDocente;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Examen\ConfiguracionExamenDocenteService;
use App\Services\Examen\EliminarExamenCursoService;
use App\Services\Examen\VincularCursoExamenCompartidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfiguracionExamenDocenteController extends Controller
{
    public function mostrar(
        Request $request,
        Examen $examen,
        ConfiguracionExamenDocenteService $servicio,
    ): JsonResponse {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 401);

        return response()->json(['datos' => $this->datos($servicio->obtener($usuario, $examen))]);
    }

    public function actualizar(
        GuardarConfiguracionExamenDocenteRequest $request,
        Examen $examen,
        ConfiguracionExamenDocenteService $servicio,
    ): JsonResponse {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $configuracion = $servicio->guardar($usuario, $examen, $request->validated());

        return response()->json(['datos' => $this->datos($configuracion)]);
    }

    public function vincularCurso(
        Request $request,
        Examen $examen,
        Curso $curso,
        VincularCursoExamenCompartidoService $servicio,
    ): JsonResponse {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 401);

        return response()->json([
            'inscripciones_creadas' => $servicio->ejecutar($usuario, $examen, $curso),
        ]);
    }

    /**
     * Elimina el examen del curso (desvinculación lógica). Solo el docente
     * que gestiona el curso; el examen puede seguir en otros cursos.
     *
     * @param  Request  $request  Request con el docente autenticado.
     * @param  Examen  $examen  Examen de la URL.
     * @param  Curso  $curso  Curso de la URL.
     * @param  EliminarExamenCursoService  $servicio  Servicio de eliminación.
     * @return JsonResponse Mensaje de exito.
     *
     * @author OchoaCesar <cesareduardonick@gmail.com>
     *
     * @since  2026-11-16
     */
    public function desvincularCurso(
        Request $request,
        Examen $examen,
        Curso $curso,
        EliminarExamenCursoService $servicio,
    ): JsonResponse {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 401);

        $servicio->ejecutar($usuario, $examen, $curso);

        return response()->json(['mensaje' => 'Examen eliminado con exito']);
    }

    /** @return array<string, mixed> */
    private function datos(ConfiguracionExamenDocente $configuracion): array
    {
        return [
            'id' => (int) $configuracion->id_configuracion_examen,
            'examen_id' => (int) $configuracion->id_examen,
            'docente_id' => (int) $configuracion->id_docente,
            'normas' => $configuracion->normas->pluck('id_norma')->map(fn ($id): int => (int) $id)->all(),
            'materiales' => $configuracion->materialesPermitidos->pluck('id_material')->map(fn ($id): int => (int) $id)->all(),
            'normas_personalizadas' => $configuracion->normasPersonalizadas
                ->sortBy('numero_norma')->pluck('descripcion_norma')->values()->all(),
            'materiales_personalizados' => $configuracion->materialesPersonalizados
                ->sortBy('numero_material')->pluck('descripcion_material')->values()->all(),
        ];
    }
}
