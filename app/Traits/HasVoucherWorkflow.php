<?php

namespace App\Traits;

trait HasVoucherWorkflow
{
    /* ======================
     |  State Guards
     ====================== */

    public function canBeReviewed(): bool
    {
        return is_null($this->reviewed_at)
            && is_null($this->approved_at)
            && ! $this->trashed();
    }

    public function canBeApproved(): bool
    {
        return ! is_null($this->reviewed_at)
            && is_null($this->approved_at)
            && ! $this->trashed();
    }

    public function isReviewed(): bool
    {
        return ! is_null($this->reviewed_at);
    }

    public function isApproved(): bool
    {
        return ! is_null($this->approved_at);
    }
}
