<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Estudiantes por página del monitor en vivo
    |--------------------------------------------------------------------------
    |
    | El filtro y la búsqueda ya no operan en el navegador sobre filas ya
    | pintadas (ver MonitorEnVivo::estudiantesFiltrados()): corren en el
    | servidor antes de paginar, así que esto puede ser un número chico de
    | verdad sin perder cobertura sobre el curso completo. 6 es lo que entra,
    | verificado sin scroll de página en 1366×768, con las tarjetas de conteo
    | (ahora en grid de 6 columnas en vez de flex-wrap, que a este ancho
    | mandaba "Tramposos" a una segunda fila), el buscador y los filtros ya
    | dibujados arriba de la tabla — ajustalo si el diseño de esa cabecera
    | cambia de alto.
    |
    */

    'por_pagina' => 50,

];
