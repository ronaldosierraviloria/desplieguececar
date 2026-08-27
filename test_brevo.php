<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "============================================================\n";
echo "       TEST DE ENVIO DE CORREO BREVO - LARAVEL\n";
echo "============================================================\n\n";

echo "CONFIGURACION DE CORREO EN .ENV:\n";
echo "------------------------------------------------------------\n";
echo "  Mailer:     " . config('mail.default') . "\n";
echo "  Host:       " . config('mail.mailers.smtp.host') . "\n";
echo "  Port:       " . config('mail.mailers.smtp.port') . "\n";
echo "  Username:   " . config('mail.mailers.smtp.username') . "\n";
echo "  From:       " . config('mail.from.address') . " (" . config('mail.from.name') . ")\n";
echo "============================================================\n\n";

echo "Intentando enviar correo via Brevo SMTP...\n";

try {
    Mail::html('<strong>Hola</strong>, este es un correo de prueba enviado correctamente desde Brevo en el <em>Sistema de Grado</em>.', function ($message) {
        $message->to('sierraviloria10@gmail.com', 'Ronaldo Sierra Viloria')
                ->from(config('mail.from.address', 'sierraviloria10@gmail.com'), config('mail.from.name', 'Ronaldo Sierra Viloria'))
                ->replyTo('sierraviloria10@gmail.com', 'Ronaldo Sierra Viloria')
                ->subject('Prueba de correo con Brevo desde Laravel');
    });

    echo "✅ Email enviado con exito a sierraviloria10@gmail.com!\n";
    echo "Check format:\n";
    echo json_encode(['result' => true, 'message' => 'Email enviado'], JSON_PRETTY_PRINT) . "\n";

} catch (\Throwable $e) {
    echo "❌ Error al enviar el correo:\n";
    echo "   " . $e->getMessage() . "\n";
}
