<?php

namespace App\Listeners;

use App\Events\DebitNotePosted;
use Illuminate\Support\Facades\Log;

class HandleDebitNotePosted
{
    public function handle(DebitNotePosted $event): void
    {
        Log::info('DebitNotePosted received', $event->payload);
        // Future: adjust stock for debit scenarios (corrections, adjustments)
    }
}
