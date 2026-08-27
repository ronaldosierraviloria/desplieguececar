<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropuestaSubidaNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreDestinatario;
    public string $rolDestinatario;

    public function __construct(Trabajo $trabajo, string $nombreDestinatario, string $rolDestinatario = 'Estudiante')
    {
        $this->trabajo = $trabajo;
        $this->nombreDestinatario = $nombreDestinatario;
        $this->rolDestinatario = $rolDestinatario;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Registro de Propuesta de Grado [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta_subida',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
