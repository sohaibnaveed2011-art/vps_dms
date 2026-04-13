<?php

namespace App\Listeners;

use App\Events\VoucherApproved;
use Illuminate\Support\Facades\Log;

class HandleVoucherApproved
{
    public function handle(VoucherApproved $event): void
    {
        // placeholder: extend to handle approved vouchers (e.g., moves/transfers)
        Log::info('VoucherApproved received', $event->payload);
    }
}
