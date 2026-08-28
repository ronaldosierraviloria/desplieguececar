<?php

namespace App\Mail;

use App\Models\Trabajo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class PropuestaCalificadaEstudianteMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Trabajo $trabajo;
    public string $nombreEstudiante;
    public ?float $notaPromedio;
    public string $resultado;
    public string $observaciones;
    public string $pdfUrl;
    public ?string $rawPdfContent;

    public function __construct(
        Trabajo $trabajo,
        string $nombreEstudiante,
        ?float $notaPromedio,
        string $resultado,
        string $observaciones = '',
        string $pdfUrl = '',
        ?string $rawPdfContent = null
    ) {
        $this->trabajo = $trabajo;
        $this->nombreEstudiante = $nombreEstudiante;
        $this->notaPromedio = $notaPromedio;
        $this->resultado = $resultado;
        $this->observaciones = $observaciones;
        $this->pdfUrl = $pdfUrl;
        $this->rawPdfContent = $rawPdfContent;
    }

    public function envelope(): Envelope
    {
        $codigo = $this->trabajo->codigo_proyecto ?? 'N/A';
        return new Envelope(
            subject: "Resultado de Evaluación de Propuesta de Grado [{$codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta_calificada_estudiante',
        );
    }

    public function attachments(): array
    {
        if (!empty($this->rawPdfContent)) {
            $codigo = $this->trabajo->codigo_proyecto ?? 'propuesta';
            return [
                Attachment::fromData(fn() => $this->rawPdfContent, "Rubrica_Evaluacion_{$codigo}.pdf")
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
