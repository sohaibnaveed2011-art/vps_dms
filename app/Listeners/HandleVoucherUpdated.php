<?php

namespace App\Listeners;

use App\Events\VoucherUpdated;
use Illuminate\Support\Facades\Log;

class HandleVoucherUpdated
{
    public function handle(VoucherUpdated $event): void
    {
        // placeholder: handle updates (e.g., send diffs to UI or trigger reconciliations)
        Log::info('VoucherUpdated received', $event->payload);
    }
}
