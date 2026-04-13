<?php

namespace App\Contracts;

/**
 * Any entity that participates in context-based authorization
 * (Org / Branch / Warehouse / Outlet) must implement this.
 */
interface ContextScope
{
    public function organizationId(): int;

    public function branchId(): ?int;

    public function warehouseId(): ?int;

    public function outletId(): ?int;
}
