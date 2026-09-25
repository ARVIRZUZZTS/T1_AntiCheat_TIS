<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $table = 'examen';

    protected $primaryKey = 'id_examen';

    public $timestamps = false;

    protected $fillable = [
        'id_examen',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion',
        'creador',
        'tipo_examen',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'id_examen');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'id_examen');
    }
}
