<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $table = 'estudiante';

    protected $primaryKey = 'sis_estudiante';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'sis_estudiante',
        'nombre_estudiante',
        'apellido_estudiante',
        'carrera',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'sis_estudiante');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'id_estudiante');
    }
}
