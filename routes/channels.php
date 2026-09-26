<?php

use App\Models\Examen;
use App\Models\Usuario;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('examen.{idExamen}', function (Usuario $usuario, int $idExamen) {
    if (app()->environment('local')) {
        return true;
    }

    return false;
});