<?php

namespace App\Listeners;

use App\Events\ReceiptNotePosted;
use Illuminate\Support\Facades\Log;

class HandleReceiptNotePosted
{
    public function handle(ReceiptNotePosted $event): void
    {
        Log::info('ReceiptNotePosted received', $event->payload);
        // Future: trigger stock increases for receipts
    }
}
