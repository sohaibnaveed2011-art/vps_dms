<?php

namespace App\Listeners;

use App\Events\VoucherCancelled;
use Illuminate\Support\Facades\Log;

class HandleVoucherCancelled
{
    public function handle(VoucherCancelled $event): void
    {
        // placeholder: handle cancellations (may require reversing stock transactions)
        Log::info('VoucherCancelled received', $event->payload);
    }
}
