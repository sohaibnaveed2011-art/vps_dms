<?php

namespace App\Listeners;

use App\Events\CreditNotePosted;
use Illuminate\Support\Facades\Log;

class HandleCreditNotePosted
{
    public function handle(CreditNotePosted $event): void
    {
        Log::info('CreditNotePosted received', $event->payload);
        // Future: reverse stock transactions (return scenario)
    }
}
