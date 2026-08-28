<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevaVersionEstudianteMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreEstudiante;

    public function __construct(Trabajo $trabajo, string $nombreEstudiante)
    {
        $this->trabajo = $trabajo;
        $this->nombreEstudiante = $nombreEstudiante;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Confirmación: Nueva Versión con Correcciones Subida [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nueva_version_estudiante',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
