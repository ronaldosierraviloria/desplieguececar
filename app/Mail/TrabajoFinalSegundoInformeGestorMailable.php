<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrabajoFinalSegundoInformeGestorMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreDestinatario;
    public string $rolDestinatario; // 'Gestor' o 'Evaluador'

    public function __construct(Trabajo $trabajo, string $nombreDestinatario, string $rolDestinatario = 'Gestor')
    {
        $this->trabajo = $trabajo;
        $this->nombreDestinatario = $nombreDestinatario;
        $this->rolDestinatario = $rolDestinatario;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "ALERTA: Se requiere segundo informe / corrección en Informe Final [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trabajo_final_segundo_informe_gestor',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
