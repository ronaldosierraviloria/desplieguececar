<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrabajoFinalAprobadoAdminMailable extends Mailable
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
            subject: "Habilitado Espacio para Acta de Sustentación - Trabajo de Grado Aprobado [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trabajo_final_aprobado_admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
