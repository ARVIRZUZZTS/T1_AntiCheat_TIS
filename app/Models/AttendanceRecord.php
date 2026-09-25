<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'registro_asistencia';

    protected $primaryKey = 'id_ingreso';

    public $timestamps = false;

    protected $fillable = [
        'id_ingreso',
        'hora_ingreso',
        'id_examen',
        'id_estudiante',
        'id_registrador',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_estudiante');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'id_examen');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(SystemUser::class, 'id_registrador');
    }
}
