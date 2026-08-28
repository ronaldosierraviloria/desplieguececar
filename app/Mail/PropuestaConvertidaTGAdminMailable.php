<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropuestaConvertidaTGAdminMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreAdmin;

    public function __construct(Trabajo $trabajo, string $nombreAdmin)
    {
        $this->trabajo = $trabajo;
        $this->nombreAdmin = $nombreAdmin;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "ALERTA ADMIN: Propuesta convertida a Trabajo de Grado [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta_convertida_tg_admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
