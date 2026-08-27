<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Almacena la calificación y observaciones de una evaluación de trabajo de grado. */
class Calificacion extends Model
{
    protected $table = 'calificacion';
    protected $primaryKey = 'id_calificacion';

    protected $fillable = [
        'id_rubrica',
        'id_profesor',
        'puntaje_total',
        'observacion_final',
        'comentarios',
        'estado',
        'fecha_calificacion',
    ];

    public $timestamps = false;

    public function rubrica()
    {
        return $this->belongsTo(Rubrica::class, 'id_rubrica');
    }

    public function profesor()
    {
        return $this->belongsTo(Profesor::class, 'id_profesor');
    }
}
