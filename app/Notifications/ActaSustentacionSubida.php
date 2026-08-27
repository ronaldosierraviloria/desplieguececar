<?php

namespace App\Notifications;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActaSustentacionSubida extends Notification
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
            'tipo'    => 'acta_sustentacion',
            'titulo'  => 'Acta de sustentación subida — proceso finalizado',
            'mensaje' => "El acta de sustentación del trabajo de grado \"{$titulo}\" ha sido registrada. El proceso del proyecto ha finalizado.",
            'url'     => route('gestor.trabajo.detalles', $this->trabajo->id_trabajo),
            'trabajo_id' => $this->trabajo->id_trabajo,
        ];
    }
}
