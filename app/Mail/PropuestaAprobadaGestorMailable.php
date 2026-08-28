<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropuestaAprobadaGestorMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreGestor;

    public function __construct(Trabajo $trabajo, string $nombreGestor)
    {
        $this->trabajo = $trabajo;
        $this->nombreGestor = $nombreGestor;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Propuesta Aprobada [{$codigo}] - Pendiente Envío de Informe Final de Trabajo de Grado",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta_aprobada_gestor',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
