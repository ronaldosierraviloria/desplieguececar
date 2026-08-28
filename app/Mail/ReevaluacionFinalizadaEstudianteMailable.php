<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReevaluacionFinalizadaEstudianteMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreEstudiante;
    public string $resultado;

    public function __construct(Trabajo $trabajo, string $nombreEstudiante, string $resultado)
    {
        $this->trabajo = $trabajo;
        $this->nombreEstudiante = $nombreEstudiante;
        $this->resultado = $resultado;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Re-evaluación Finalizada para tu Proyecto [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reevaluacion_finalizada_estudiante',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
