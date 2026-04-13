<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryLedger;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class InventoryBatchService
{
    /**
     * Common query logic to enforce organization scoping through ProductVariant relationship.
     */
    protected function scopedQuery(?int $orgId = null)
    {
        return InventoryBatch::query()
            ->when($orgId, function ($q) use ($orgId) {
                $q->whereHas('productVariant', fn($v) => $v->where('organization_id', $orgId));
            });
    }

    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): InventoryBatch
    {
        $query = $this->scopedQuery($orgId);

        if ($withTrashed) $query->withTrashed();

        return $query->find($id) ?? throw new NotFoundException('Inventory Batch not found.');
    }

    public function create(array $data): InventoryBatch
    {
        return InventoryBatch::create([
            'product_variant_id' => $data['product_variant_id'],
            'batch_number'       => $data['batch_number'],
            'manufacturing_date' => $data['manufacturing_date'] ?? null,
            'expiry_date'        => $data['expiry_date'] ?? null,
            'initial_cost'       => $data['initial_cost'],
            'is_recalled'        => false,
            'status'             => 'open',
        ]);
    }

    public function update(InventoryBatch $batch, array $data): InventoryBatch
    {
        if ($batch->balances()->where('quantity', '>', 0)->exists()) {
            throw new RuntimeException('Cannot update batch with active stock.');
        }

        $batch->update($data);
        return $batch;
    }

    public function delete(InventoryBatch $batch): void
    {
        if ($batch->balances()->where('quantity', '>', 0)->exists()) {
            throw new RuntimeException('Cannot delete batch with remaining stock.');
        }
        $batch->delete();
    }

    public function restore(InventoryBatch $batch): void
    {
        $batch->restore();
    }

    public function forceDelete(InventoryBatch $batch): void
    {
        if (InventoryLedger::where('inventory_batch_id', $batch->id)->exists()) {
            throw new RuntimeException('Cannot permanently delete batch with ledger history.');
        }
        $batch->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | Strategy Lookups (Scoping by organization is vital here)
    |--------------------------------------------------------------------------
    */

    public function getAvailableBatchesFIFO(int $variantId, int $locationId, ?int $orgId = null): Collection
    {
        return $this->scopedQuery($orgId)
            ->where('product_variant_id', $variantId)
            ->whereHas('balances', function ($q) use ($locationId) {
                $q->where('stock_location_id', $locationId)->where('quantity', '>', 0);
            })
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
    }

    public function getAvailableBatchesFEFO(int $variantId, int $locationId, ?int $orgId = null): Collection
    {
        return $this->scopedQuery($orgId)
            ->where('product_variant_id', $variantId)
            ->whereHas('balances', function ($q) use ($locationId) {
                $q->where('stock_location_id', $locationId)->where('quantity', '>', 0);
            })
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date', 'asc')
            ->lockForUpdate()
            ->get();
    }
}
