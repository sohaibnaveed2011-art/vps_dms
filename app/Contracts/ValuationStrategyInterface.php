<?php

namespace App\Contracts;

use App\Models\Inventory\ProductVariant;

interface ValuationStrategyInterface
{
    public function consume(array $payload, ProductVariant $variant): void;
}

