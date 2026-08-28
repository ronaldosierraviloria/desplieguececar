<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar alertas diarias a evaluadores a las 08:00 AM
Schedule::command('evaluadores:enviar-alertas')->dailyAt('08:00');
