<?php

/**
 * @file    TipoExamen.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-10
 *
 * @description
 * Enum de dominio con los tipos de examen admitidos por la base de datos
 * (tipo `tipo_examen_nombre`, tabla `tipo_examen`).
 *
 * El `value` de cada caso es el texto exacto que guarda PostgreSQL, en
 * minúscula (`examen parcial`). Antes eran códigos de dos letras (`PP`, `SP`) que
 * la base ya no tiene: con esos valores el selector del modal ofrecía tipos
 * inexistentes y el alta fallaba siempre. `etiqueta()` devuelve el mismo texto con
 * mayúscula inicial, que es lo que se ve en las pantallas.
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
 * - 2026-10-10  [Alex Candia]  fix: los tres tipos pasan a ser los valores reales
 *   del enum `tipo_examen_nombre` (examen parcial, examen final, segunda
 *   instancia). Se caen los seis códigos anteriores, que la base ya no tiene, y
 *   con ellos `PP/SP/FINAL/SI/PARCIAL/PRACTICA` ya no se ofrecían en el alta.
 *
 * @see  App\Services\Examen\ListarExamenesCursoService
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 */

namespace App\Enums;

enum TipoExamen: string
{
    case ExamenParcial = 'examen parcial';

    case ExamenFinal = 'examen final';

    case SegundaInstancia = 'segunda instancia';

    /**
     * Nombre del tipo tal como se lo muestra a la persona usuaria: el valor de
     * la base con mayúscula inicial.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::ExamenParcial => 'Examen parcial',
            self::ExamenFinal => 'Examen final',
            self::SegundaInstancia => 'Segunda instancia',
        };
    }

    /**
     * Nombre a mostrar para el tipo guardado en la base. Si el valor no está en
     * el enum se devuelve tal cual: la base manda y la pantalla nunca se queda
     * sin nombre de tipo.
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
     * `x-ui.select` (valor => etiqueta): se envía el texto de la base y se
     * muestra su versión legible.
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