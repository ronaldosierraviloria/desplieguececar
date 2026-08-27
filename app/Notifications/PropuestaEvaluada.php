<?php

namespace App\Notifications;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropuestaEvaluada extends Notification
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
        $evaluacion = $this->trabajo->evaluaciones->first();
        $resultado = $evaluacion->resultado ?? null;

        $mensaje = match ($resultado) {
            'rechazada' => "La propuesta \"{$titulo}\" fue rechazada por los evaluadores. Se debe consultar a los estudiantes si desean continuar con el proyecto.",
            'aceptada_con_mejoras' => "Todos los evaluadores finalizaron la evaluación de la propuesta \"{$titulo}\". Puedes subir el informe final.",
            default => "Todos los evaluadores finalizaron la evaluación de la propuesta \"{$titulo}\". Ya puedes subir el informe final.",
        };

        return [
            'tipo'    => 'propuesta_evaluada',
            'titulo'  => 'Propuesta evaluada',
            'mensaje' => $mensaje,
            'url'     => route('gestor.trabajo.detalles', $this->trabajo->id_trabajo),
            'trabajo_id' => $this->trabajo->id_trabajo,
        ];
    }
}
