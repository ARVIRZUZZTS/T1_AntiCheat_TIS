<?php

use App\Enums\Motivo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (! DB::selectOne("SELECT 1 FROM pg_type WHERE typname = 'motivo' LIMIT 1")) {
            return;
        }

        $nuevos = [
            Motivo::IntentoDeIngresoDuplicado->value,
            Motivo::AulaEquivocada->value,
            Motivo::IntentoDeIngresoMaterialNoAutorizado->value,
        ];

        foreach ($nuevos as $valor) {
            DB::statement("ALTER TYPE motivo ADD VALUE IF NOT EXISTS " . DB::getPdo()->quote($valor));
        }
    }

    public function down(): void
    {
        // PostgreSQL no permite quitar valores de un ENUM sin recrearlo.
    }
};