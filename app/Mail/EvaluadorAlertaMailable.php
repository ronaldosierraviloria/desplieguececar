<?php

namespace App\Mail;

use App\Models\Trabajo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluadorAlertaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreEvaluador;
    public int $diasFaltantes;
    public string $fechaLimiteFormatted;

    public function __construct(Trabajo $trabajo, string $nombreEvaluador, int $diasFaltantes, Carbon $fechaLimite)
    {
        $this->trabajo = $trabajo;
        $this->nombreEvaluador = $nombreEvaluador;
        $this->diasFaltantes = $diasFaltantes;
        $this->fechaLimiteFormatted = $fechaLimite->format('d/m/Y');
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        $plural = $this->diasFaltantes === 1 ? 'Día Hábil' : 'Días Hábiles';
        return new Envelope(
            subject: "RECORDATORIO: Falta{$this->pluralSuffix()} {$this->diasFaltantes} {$plural} para Finalizar Evaluación [{$codigo}]",
        );
    }

    private function pluralSuffix(): string
    {
        return $this->diasFaltantes === 1 ? 'n' : 'n';
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.evaluador_alerta',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
