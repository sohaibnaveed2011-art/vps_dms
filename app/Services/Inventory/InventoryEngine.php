<?php

namespace App\Services\Inventory;

class InventoryEngine
{
    public function __construct(
        protected OutboundStockEngine $outbound,
        protected InboundStockEngine $inbound
    ) {}

    public function add(array $payload): void
    {
        $this->inbound->add($payload);
    }

    public function consume(array $payload): void
    {
        $this->outbound->consume($payload);
    }
}

