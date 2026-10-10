<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Estudiantes por página del monitor en vivo
    |--------------------------------------------------------------------------
    |
    | El monitor filtra y busca en el navegador sobre las filas ya renderizadas,
    | así que la página tiene que traer la inscripción completa del examen para
    | que el filtro y el buscador cubran a todos los estudiantes y no solo a los
    | de la página visible. Si algún examen llega a tener más inscritos que este
    | número, la paginación aparece sola.
    |
    */

    'por_pagina' => 50,

];
