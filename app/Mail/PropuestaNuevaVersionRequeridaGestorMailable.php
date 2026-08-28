<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropuestaNuevaVersionRequeridaGestorMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreGestor;
    public string $resultadoEvaluacion;

    public function __construct(Trabajo $trabajo, string $nombreGestor, string $resultadoEvaluacion)
    {
        $this->trabajo = $trabajo;
        $this->nombreGestor = $nombreGestor;
        $this->resultadoEvaluacion = $resultadoEvaluacion;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "ALERTA GESTOR: Propuesta [{$codigo}] requiere nuevo documento de correcciones",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta_nueva_version_requerida_gestor',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
