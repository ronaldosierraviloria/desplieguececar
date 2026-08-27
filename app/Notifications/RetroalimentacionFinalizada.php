<?php

namespace App\Notifications;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RetroalimentacionFinalizada extends Notification
{
    use Queueable;

    public function __construct(
        protected Trabajo $trabajo
    ) {}

    public function via(object $notifiable): array
    {
        return [];
    }

    public function toDatabase(object $notifiable): array
    {
        $titulo = $this->trabajo->titulo;

        return [
            'tipo'    => 'retroalimentacion',
            'titulo'  => 'Retroalimentación finalizada',
            'mensaje' => "Ambos jurados finalizaron su retroalimentación del trabajo \"{$titulo}\". El gestor ya puede subir el documento corregido.",
            'url'     => route('admin.detallesTrabajo', $this->trabajo->id_trabajo),
            'trabajo_id' => $this->trabajo->id_trabajo,
        ];
    }
}
