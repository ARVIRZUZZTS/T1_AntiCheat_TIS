<?php

namespace App\Models;

use App\Enums\InvitacionEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvitacionExamenCompartido extends Model
{
    protected $table = 'invitacion_examen_compartido';

    protected $primaryKey = 'id_invitacion';

    public $incrementing = false;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'id_invitacion',
        'id_docente_invitador',
        'id_docente_invitado',
        'id_examen',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => InvitacionEstado::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Examen, $this> */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function invitador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_docente_invitador', 'id_usuario');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function invitado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_docente_invitado', 'id_usuario');
    }

    /** @return HasOne<NotificacionDocente, $this> */
    public function notificacion(): HasOne
    {
        return $this->hasOne(NotificacionDocente::class, 'id_invitacion', 'id_invitacion');
    }
}
