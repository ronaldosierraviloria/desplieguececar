<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Trabajo;
use App\Models\Profesor;
use App\Services\BusinessDaysService;
use App\Mail\EvaluadorAlertaMailable;

class EnviarAlertasEvaluadores extends Command
{
    /**
     * El nombre y firma del comando en la consola.
     */
    protected $signature = 'evaluadores:enviar-alertas';

    /**
     * Descripción del comando.
     */
    protected $description = 'Envía alertas por correo electrónico a evaluadores cuando faltan 10, 5, 3 y 1 días hábiles para vencer el plazo de revisión.';

    /**
     * Ejecutar el comando.
     */
    public function handle(): int
    {
        $this->info('Iniciando verificación de plazos de evaluadores...');

        $ahora = Carbon::now();

        // Obtener asignaciones activas donde el evaluador no ha finalizado
        $asignaciones = DB::table('trabajo_profesor')
            ->whereNotIn('estado_revision', ['Finalizado', 'Rechazado'])
            ->whereNotNull('fecha_limite_revision')
            ->get();

        $enviados = 0;

        foreach ($asignaciones as $asig) {
            $fechaLimite = Carbon::parse($asig->fecha_limite_revision);
            $diasHabilesRestantes = BusinessDaysService::getRemainingBusinessDays($ahora, $fechaLimite);

            // Verificar si el día hábil restante coincide exactamente con 10, 5, 3 o 1
            if (in_array($diasHabilesRestantes, [10, 5, 3, 1], true)) {
                $trabajo = Trabajo::with(['tipo', 'estudiante'])->find($asig->id_trabajo);
                $profesor = Profesor::with('usuario')->find($asig->id_profesor);

                if ($trabajo && $profesor && $profesor->usuario && !empty($profesor->usuario->correo)) {
                    try {
                        $nombreEvaluador = trim($profesor->usuario->nombre . ' ' . $profesor->usuario->apellido);
                        Mail::to($profesor->usuario->correo)->send(new EvaluadorAlertaMailable(
                            $trabajo,
                            $nombreEvaluador,
                            $diasHabilesRestantes,
                            $fechaLimite
                        ));

                        $enviados++;
                        $this->line("✅ Alerta ({$diasHabilesRestantes} días hábiles) enviada a {$profesor->usuario->correo} para proyecto #{$trabajo->id_trabajo}");
                    } catch (\Throwable $e) {
                        Log::error("Error al enviar alerta a evaluador #{$profesor->id_profesor}: " . $e->getMessage());
                        $this->error("❌ Error enviando a {$profesor->usuario->correo}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("Proceso finalizado. Total alertas enviadas: {$enviados}");
        return Command::SUCCESS;
    }
}
