<?php

namespace App\Listeners;

use App\Events\DeliveryNotePosted;
use Illuminate\Support\Facades\Log;

class HandleDeliveryNotePosted
{
    public function handle(DeliveryNotePosted $event): void
    {
        Log::info('DeliveryNotePosted received', $event->payload);
        // Future: trigger stock reductions for delivered items
    }
}
