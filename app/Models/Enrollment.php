<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'estudiante_examen';

    protected $primaryKey = 'id_estudiante_examen';

    public $timestamps = false;

    protected $fillable = [
        'id_estudiante_examen',
        'sis_estudiante',
        'id_examen',
        'estado',
        'motivo',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'sis_estudiante');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'id_examen');
    }
}
