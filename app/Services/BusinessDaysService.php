<?php

namespace App\Services;

use Carbon\Carbon;

class BusinessDaysService
{
    /**
     * Suma un número determinado de días hábiles (lunes a viernes) a una fecha dada.
     */
    public static function addBusinessDays(Carbon $date, int $days = 15): Carbon
    {
        $currentDate = $date->copy();
        $addedDays = 0;

        while ($addedDays < $days) {
            $currentDate->addDay();
            if (!$currentDate->isWeekend()) {
                $addedDays++;
            }
        }

        return $currentDate;
    }

    /**
     * Calcula los días hábiles restantes entre la fecha actual/inicio y la fecha límite.
     */
    public static function getRemainingBusinessDays(Carbon $fromDate, Carbon $deadline): int
    {
        $start = $fromDate->copy()->startOfDay();
        $end = $deadline->copy()->startOfDay();

        if ($start->greaterThanOrEqualTo($end)) {
            return 0;
        }

        $remaining = 0;
        $curr = $start->copy();

        while ($curr->lessThan($end)) {
            $curr->addDay();
            if (!$curr->isWeekend()) {
                $remaining++;
            }
        }

        return $remaining;
    }
}
