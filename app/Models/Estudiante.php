<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Representa un estudiante matriculado en un trabajo de grado. */
class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiante';
    protected $primaryKey = 'id_estudiante';

    protected $fillable = [
        'id_trabajo',
        'nombre',
        'apellido',
        'correo',
        'id_area',
        'motivo_eliminacion',
    ];
    public $timestamps = false;

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class, 'id_trabajo');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area', 'id_area');
    }
}
