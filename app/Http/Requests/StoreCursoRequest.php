<?php

/**
 * @file    StoreCursoRequest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-10-24
 * @updated 2026-10-26
 * @updated 2026-11-04
 *
 * @description
 * Validación del alta de un curso desde el formulario "Crear curso". Se piden
 * la materia, el grupo, la gestión y el año; la materia y el grupo se guardan
 * juntos como "{materia} - Grupo {grupo}" (separados por " - " para poder
 * dividirlos después si hace falta) y la gestión con su año va a
 * `curso_tipo_gestion`. El año es el actual o uno posterior (hora de Bolivia,
 * la zona de la aplicación), de cuatro cifras; el formulario propone el
 * actual. Que el curso no exista ya lo revisa RegistrarCursoService.
 *
 * El grupo es alfanumérico de hasta 3 caracteres (2, 10, 21A) y se guarda en
 * mayúsculas. Con 80 caracteres de materia, el nombre completo no pasa de los
 * 100 que admite `curso.nombre_curso`.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Services\Curso\RegistrarCursoService
 * @see  resources/views/pages/curso-crear.blade.php
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del request.
 * - 2026-10-26  [Valery D. Ortuno P]  perf: la gestión se valida con `in`
 *   contra el catálogo cacheado (ListarGestionesService) en vez de `exists`.
 * - 2026-11-04  [Valery D. Ortuno P]  fix: no se aceptan años pasados: el año
 *   va desde el actual, sin tope (antes 2000 a 2099).
 */

namespace App\Http\Requests;

use App\Services\Curso\ListarGestionesService;
use App\Support\DTO\GestionCursoDTO;
use App\Support\Validadores;
use Illuminate\Foundation\Http\FormRequest;

class StoreCursoRequest extends FormRequest
{
    /**
     * El rol docente ya lo exige el middleware `docente` de la ruta; el curso
     * se crea a nombre del usuario autenticado.
     *
     * @return bool  Si hay un usuario autenticado.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Quita espacios sobrantes y pasa el grupo a mayúsculas antes de validar,
     * para que "  Calculo   I " y "21a" se guarden limpios.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'grupo' => Validadores::normalizarGrupo($this->input('grupo')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(ListarGestionesService $gestiones): array
    {
        return [
            'id_materia' => Validadores::reglaMateriaId(),
            'grupo' => Validadores::reglaGrupo(),
            'gestion' => Validadores::reglaGestion(array_keys($gestiones->ejecutar())),
            'anio' => Validadores::reglaAnio($this->anioMinimo()),
        ];
    }

    /**
     * Primer año aceptado: el actual, en hora de Bolivia (`app.timezone`).
     *
     * @return int  Año en curso.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-11-04
     */
    public function anioMinimo(): int
    {
        return now()->year;
    }

    /**
     * Gestión elegida (periodo y año), lista para el servicio de alta.
     *
     * @return GestionCursoDTO  Periodo y año validados.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function gestion(): GestionCursoDTO
    {
        return new GestionCursoDTO(
            idTipoGestion: (int) $this->validated('gestion'),
            anio: (int) $this->validated('anio'),
        );
    }


    /**
     * La aplicación está en locale `en`; los mensajes se traducen acá y van
     * sin acentos, como el resto de los textos visibles.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return Validadores::mensajesCurso($this->anioMinimo());
    }

    /** Materia elegida del catálogo. */
    public function idMateria(): int
    {
        return (int) $this->validated('id_materia');
    }

    /** Grupo (1-10). */
    public function numeroGrupo(): int
    {
        return (int) $this->validated('grupo');
    }
}
