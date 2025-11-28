<?php

namespace App\Helpers;

use App\Models\Holiday;
use Carbon\Carbon;

class HolidayHelper
{
    public static function isHoliday(Carbon|string $date): bool
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return Holiday::isHolidayDate($date);
    }
}


