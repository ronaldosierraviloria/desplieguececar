<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Representa un área académica o departamento dentro de una facultad. */
class Area extends Model
{
    protected $table = 'area';
    protected $primaryKey = 'id_area';

    protected $fillable = [
        'nombre_area',
        'id_facultad',
    ];

    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'id_facultad', 'id_facultad');
    }

    public function profesores()
    {
        return $this->hasMany(Profesor::class, 'id_area', 'id_area');
    }

    public function rubricas()
    {
        return $this->hasMany(Rubrica::class, 'id_tipo', 'id_tipo');
    }


}