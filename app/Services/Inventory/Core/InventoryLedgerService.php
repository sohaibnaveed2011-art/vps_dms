<?php

namespace App\Services\Inventory\Core;

use App\Models\Inventory\InventoryLedger;

class InventoryLedgerService
{
    public function record(array $data): InventoryLedger
    {
        return InventoryLedger::create($data);
    }
}
