<?php

// app/Listeners/Accounts/HandleJournalPosted.php
namespace App\Listeners\Accounts;

use Illuminate\Support\Facades\Log;
use App\Events\Accounts\JournalPosted;

class HandleJournalPosted
{
    /**
     * Handle the event.
     */
    public function handle(JournalPosted $event): void
    {
        // Clear relevant caches
        $accounts = $event->journal->lines->pluck('account_id')->unique();
        
        foreach ($accounts as $accountId) {
            // Clear account balance cache if you're using caching
            // Cache::forget("account_balance_{$accountId}");
        }
        
        // Log the posting
        Log::info('Journal posted', [
            'journal_id'    => $event->journal->id,
            'voucher_no'    => $event->journal->voucher_no,
            'posted_by'     => auth()->id,
            'total_debit'   => $event->journal->total_debit,
            'total_credit'  => $event->journal->total_credit,
        ]);
        
        // You can also dispatch notifications, update accounting reports, etc.
    }
}