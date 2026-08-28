<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReevaluacionFinalizadaGestorMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreGestor;
    public string $resultado;

    public function __construct(Trabajo $trabajo, string $nombreGestor, string $resultado)
    {
        $this->trabajo = $trabajo;
        $this->nombreGestor = $nombreGestor;
        $this->resultado = $resultado;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "NOTIFICACIÓN GESTOR: Re-evaluación Completada para Proyecto [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reevaluacion_finalizada_gestor',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
