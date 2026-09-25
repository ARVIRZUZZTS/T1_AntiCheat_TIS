<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id_usuario',
        'cod_sis',
        'contraseña',
        'nombre_usuario',
        'apellido',
    ];

    protected $hidden = ['contraseña'];

    public function esRegistradorDeExamen(int $idExamen): bool
    {
        return Examen::where('id_examen', $idExamen)
            ->where(function ($q) {
                $q->where('creador', $this->id_usuario)
                  ->orWhereHas('invitados', fn ($q2) =>
                      $q2->where('id_docente_invitado', $this->id_usuario)
                         ->where('estado', 'aceptado')
                  );
            })
            ->exists();
    }

}
