<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class RubricaFinalPDFEstudianteMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreEstudiante;
    public ?float $notaFinal;
    public string $resultado;
    public string $observaciones;
    public ?string $rawPdfContent;

    public function __construct(
        Trabajo $trabajo,
        string $nombreEstudiante,
        ?float $notaFinal,
        string $resultado,
        string $observaciones = '',
        ?string $rawPdfContent = null
    ) {
        $this->trabajo = $trabajo;
        $this->nombreEstudiante = $nombreEstudiante;
        $this->notaFinal = $notaFinal;
        $this->resultado = $resultado;
        $this->observaciones = $observaciones;
        $this->rawPdfContent = $rawPdfContent;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Rúbrica de Evaluación Final y Retroalimentación [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rubrica_final_pdf_estudiante',
        );
    }

    public function attachments(): array
    {
        if (!empty($this->rawPdfContent)) {
            $codigo = $this->trabajo->codigo_proyecto ?? 'trabajo_final';
            return [
                Attachment::fromData(fn() => $this->rawPdfContent, "Rubrica_Informe_Final_{$codigo}.pdf")
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
