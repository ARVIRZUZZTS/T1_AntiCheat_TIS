<?php

/**
 * @file    BusquedaEstudianteService.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Servicio de dominio que define COMO se busca a un estudiante. El modo de la
 * búsqueda no lo elige quien escribe, lo decide el primer caracter del término:
 *
 *   - empieza con un dígito: solo se admiten dígitos y el código
 *     SIS tiene 9, asi que el término se recorta a ese largo.
 *   - empieza con una letra: solo letras del castellano
 *     y espacios, para poder buscar "Nombre Apellido".
 *
 *
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 * @see  App\Livewire\Examenes\EstudiantesCurso
 * @see  resources/views/partials/busqueda-estudiante.blade.php
 *
 * @changelog
 * - 2026-09-26  [Alisson D. Alvarado]  feat: creación inicial del servicio.
 */

namespace App\Services\Examen;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

class BusquedaEstudianteService
{
    public const MODO_SIS = 'sis';

    public const MODO_NOMBRE = 'nombre';

    public const LARGO_COD_SIS = 9;

    public const LARGO_MAXIMO_NOMBRE = 60;


    public const PERMITIDOS_NOMBRE = 'A-Za-zÁÉÍÓÚÜÑáéíóúüñ ';

    public const PERMITIDOS_COD_SIS = '0-9';


    public const PATRON_NOMBRE = '/^['.self::PERMITIDOS_NOMBRE.']+$/u';

    public const PATRON_COD_SIS = '/^['.self::PERMITIDOS_COD_SIS.']{1,'.self::LARGO_COD_SIS.'}$/';

    public const DESCARTE_COD_SIS = '/\D/g';

    public const DESCARTE_NOMBRE = '/[^'.self::PERMITIDOS_NOMBRE.']/g';

    /**
     * Modo de búsqueda que corresponde a un término.
     *
     * @param  string  $termino  Término escrito por la persona que busca.
     * @return string MODO_SIS, MODO_NOMBRE o "" si el término está vacío.
     */
    public function modo(string $termino): string
    {
        if ($termino === '') {
            return '';
        }

        return preg_match('/^[0-9]/', $termino) === 1 ? self::MODO_SIS : self::MODO_NOMBRE;
    }

    /**
     * Deja el término con los caracteres que el modo admite y lo recorta al
     * largo máximo de ese modo. Es la misma regla que se aplica en el navegador,
     * pero en el servidor: cubre lo que llegue por URL o por un cliente que no
     * pase por el sanitizeo del input.
     *
     * @param  string  $termino  Término a sanear.
     * @return string Término sanado (puede quedar vacío).
     */
    public function sanear(string $termino): string
    {
        if ($this->modo($termino) === self::MODO_NOMBRE) {
            return mb_substr(trim($this->conservar($termino, self::PERMITIDOS_NOMBRE)), 0, self::LARGO_MAXIMO_NOMBRE);
        }

        return mb_substr($this->conservar($termino, self::PERMITIDOS_COD_SIS), 0, self::LARGO_COD_SIS);
    }

    /**
     * Valida el término contra el modo que su primer caracter declara. Se usa
     * para rechazar la búsqueda con un mensaje claro en vez de devolver
     * silenciosamente una lista vacía.
     *
     * @param  ?string  $termino  Término a validar (null o vacío se aceptan).
     * @return string Modo de la búsqueda, listo para pasar a aplicar().
     *
     * @throws InvalidArgumentException Si el término tiene caracteres o un largo
     *                                  que no corresponden a su modo.
     */
    public function validar(?string $termino): string
    {
        if ($termino === null || $termino === '') {
            return '';
        }

        $modo = $this->modo($termino);
        $patron = $modo === self::MODO_SIS ? self::PATRON_COD_SIS : self::PATRON_NOMBRE;

        if (preg_match($patron, $termino) !== 1) {
            throw new InvalidArgumentException($this->mensajeDeError($modo));
        }

        return $modo;
    }

    /**
     * Aplica el filtro del término a la consulta de estudiantes del curso.
     *
     * @param  BelongsToMany  $query  Consulta de estudiantes del curso.
     * @param  string  $termino  Término de búsqueda ya validado.
     * @return string Modo con el que se filtró, para que la vista lo muestre.
     *
     * @throws InvalidArgumentException Si el término no es válido para su modo.
     */
    public function aplicar(BelongsToMany $query, ?string $termino): string
    {
        if ($termino === null || $termino === '') {
            return '';
        }

        $modo = $this->validar($termino);
        $patron = '%'.$termino.'%';

        if ($modo === self::MODO_SIS) {
            $query->where('estudiante.sis_estudiante', 'ilike', $patron);

            return $modo;
        }

        $query->where(function (Builder $q) use ($patron) {
            $q->where('estudiante.nombre_estudiante', 'ilike', $patron)
                ->orWhere('estudiante.apellido_estudiante', 'ilike', $patron)
                ->orWhereRaw(
                    "estudiante.nombre_estudiante || ' ' || estudiante.apellido_estudiante ilike ?",
                    [$patron]
                );
        });

        return $modo;
    }

    /**
     * Deja en el término solo los caracteres del conjunto indicado. Se arma con
     * preg_split y no con preg_replace + modificador `g`: la regla queda igual y
     * el resultado no depende del modificador global de PCRE.
     *
     * @param  string  $termino  Término a sanear.
     * @param  string  $permitidos  Clase de caracteres admitidos.
     */
    private function conservar(string $termino, string $permitidos): string
    {
        return implode('', preg_split('/[^'.$permitidos.']/u', $termino) ?: []);
    }

    /**
     * Mensaje de error para una búsqueda que no cumple su propio modo.
     *
     * @param  string  $modo  Modo que el término declara.
     * @return string Mensaje en castellano, sin datos del estudiante.
     */
    private function mensajeDeError(string $modo): string
    {
        return $modo === self::MODO_SIS
            ? 'El código SIS debe tener solo dígitos (máximo '.self::LARGO_COD_SIS.').'
            : 'La búsqueda por nombre solo admite letras y espacios.';
    }
}
