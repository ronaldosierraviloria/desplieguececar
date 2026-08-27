<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Registra el historial de cambios de estado de un trabajo de grado, incluyendo rechazos y eliminaciones. */
class HistorialEstado extends Model
{
    use HasFactory;

    protected $table = 'historial_estados';

    protected $fillable = [
        'trabajo_grado_id',
        'estado',
        'user_id',
        'observacion_estado',
    ];

    /**
     * Relación con el Trabajo de Grado.
     */
    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class, 'trabajo_grado_id', 'id_trabajo');
    }

    /**
     * Relación con el Usuario (Gestor/Evaluador).
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }
}
