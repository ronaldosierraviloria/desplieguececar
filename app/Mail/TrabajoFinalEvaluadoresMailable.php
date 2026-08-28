<?php

namespace App\Mail;

use App\Models\Trabajo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrabajoFinalEvaluadoresMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreEvaluador;
    public string $fechaLimiteFormatted;
    public int $diasHabiles;

    public function __construct(Trabajo $trabajo, string $nombreEvaluador, Carbon $fechaLimite, int $diasHabiles = 15)
    {
        $this->trabajo = $trabajo;
        $this->nombreEvaluador = $nombreEvaluador;
        $this->fechaLimiteFormatted = $fechaLimite->format('d/m/Y');
        $this->diasHabiles = $diasHabiles;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Evaluación de INFORME FINAL de Trabajo de Grado [{$codigo}] - Plazo: {$this->diasHabiles} Días Hábiles",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trabajo_final_evaluadores',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
