<?php

/**
 * @file    TipoExamen.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Enum de dominio con los tipos de examen admitidos por la base de datos
 * (tipo `tipo_examen_nombre`, tabla `tipo_examen`). Es la única fuente de los
 * valores válidos y de su significado: el listado de exámenes de la materia y
 * el selector del formulario de alta toman de acá el nombre a mostrar, en vez
 * de dejar los códigos (PP, SP...) sueltos en las pantallas.
 *
 * Ojo con el nombre: este enum NO es {@see \App\Models\TipoExamen}, que mapea la
 * tabla `tipo_examen` para leerla con Eloquent.
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del enum.
 * - 2026-10-05  [Alex Candia]  feat: significado de cada código (etiqueta()),
 *   etiquetaDe() para mapear un valor de la base y opciones() con el nombre
 *   legible. Antes las etiquetas eran el propio código, que resultaba ambiguo
 *   en la lista de exámenes.
 *
 * @see  App\Services\Examen\ListarExamenesCursoService
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 */

namespace App\Enums;

enum TipoExamen: string
{
    case Pp = 'PP';

    case Sp = 'SP';

    case Final = 'FINAL';

    case Si = 'SI';

    case Parcial = 'PARCIAL';

    case Practica = 'PRACTICA';

    /**
     * Nombre del tipo tal como se lo muestra a la persona usuaria.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pp => 'Primer parcial',
            self::Sp => 'Segundo parcial',
            self::Final => 'Examen final',
            self::Si => 'Segunda Instancia',
            self::Parcial => 'Parcial',
            self::Practica => 'Práctica',
        };
    }

    /**
     * Nombre a mostrar para el tipo guardado en la base. Si el valor no está en
     * el enum se devuelve tal cual: la base manda y la pantalla nunca se queda
     * sin typename.
     *
     * @param  ?string  $valor  Valor de `nombre_tipo_examen` (o null si el
     *                          examen no tiene tipo).
     * @return ?string Nombre legible, o null si no hay tipo.
     */
    public static function etiquetaDe(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return self::tryFrom($valor)?->etiqueta() ?? $valor;
    }

    /**
     * Opciones del selector de tipo de examen, en el formato que espera
     * `x-ui.select` (valor => etiqueta): se envía el código de la base y se
     * muestra su significado.
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $tipo) {
            $opciones[$tipo->value] = $tipo->etiqueta();
        }

        return $opciones;
    }
}
