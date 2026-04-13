<?php

namespace App\Jobs;

use App\Services\Inventory\Reservation\ReservationEngine;

class ExpireReservationsJob
{
    public function handle(ReservationEngine $engine): void
    {
        $engine->expire();
    }
}
