<?php

namespace App\Notifications;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuevaVersionDisponible extends Notification
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

        $rol = $notifiable->rol ?? null;
        if ($rol === 'Administrador') {
            $url = route('admin.detallesTrabajo', $this->trabajo->id_trabajo);
        } else {
            $url = route('evaluador.dashboard');
        }

        return [
            'tipo'    => 'nueva_version',
            'titulo'  => 'Documento corregido disponible para revisar',
            'mensaje' => "El gestor subió una corrección del trabajo \"{$titulo}\". Ya puedes iniciar la nueva revisión.",
            'url'     => $url,
            'trabajo_id' => $this->trabajo->id_trabajo,
        ];
    }
}
