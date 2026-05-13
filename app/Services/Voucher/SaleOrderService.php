<?php

namespace App\Services\Voucher;

use App\Exceptions\NotFoundException;
use App\Exceptions\WorkflowException;
use App\Models\Vouchers\SaleOrder;
use App\Models\Vouchers\DocumentItem;
use App\Models\Vouchers\DocumentNumberSequence;
use App\Services\Inventory\ProductVariantService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleOrderService
{
    protected array $relations = [
        'customer',
        'branch',
        'warehouse',
        'outlet',
        'voucherType',
        'financialYear',
        'items',
        'items.productVariant',
        'items.productVariant.units',
        'items.tax',
        'creator',
        'reviewer',
        'approver',
        'rejector',
        'attachments',
        'comments',
        'statusHistory',
        'rejectionHistory',
    ];

    public function __construct(protected ProductVariantService $variantService)
    {}

    /**
     * Get paginated list of sale orders
     */
    public function paginate(?array $filters, int $perPage): LengthAwarePaginator
    {
        return SaleOrder::query()
            ->with($this->relations)
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['branch_id']), fn($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(isset($filters['customer_id']), fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when(isset($filters['voucher_type_id']), fn($q) => $q->where('voucher_type_id', $filters['voucher_type_id']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn($q) => $q->whereDate('order_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->whereDate('order_date', '<=', $filters['date_to']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('document_number', 'like', $term)
                        ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', $term));
                });
            })
            ->when(isset($filters['need_review']), fn($q) => $q->needReview())
            ->when(isset($filters['need_approval']), fn($q) => $q->needApproval())
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a sale order by ID
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): SaleOrder
    {
        $query = SaleOrder::query();
        $query->with($this->relations);

        if ($withTrashed) {
            $query->withTrashed();
        }
        
        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        return $query->find($id) ?? throw new NotFoundException('Sale order not found.');
    }

    /**
     * Find by document number
     */
    public function findByDocumentNumber(string $documentNumber, int $orgId): SaleOrder
    {
        return SaleOrder::where('organization_id', $orgId)
            ->where('document_number', $documentNumber)
            ->first() ?? throw new NotFoundException('Sale order not found.');
    }

    /**
     * Create a new sale order
     */
    public function create(array $data): SaleOrder
    {
        return DB::transaction(function () use ($data) {
            // Generate document number if not provided
            if (empty($data['document_number'])) {
                $data['document_number'] = $this->generateDocumentNumber($data);
            }

            // Calculate grand total from items
            $data['grand_total'] = $this->calculateGrandTotal($data['items'] ?? []);

            // Create sale order
            $saleOrder = SaleOrder::create($this->extractOrderData($data));

            // Create items
            if (!empty($data['items'])) {
                $this->createItems($saleOrder, $data['items']);
            }

            // Log creation
            $saleOrder->logEvent('created', null, $data);

            return $saleOrder->load($this->relations);
        });
    }

    /**
     * Update a sale order
     */
    public function update(SaleOrder $saleOrder, array $data): SaleOrder
    {
        // Check if order is editable
        if (!$saleOrder->isEditable()) {
            throw new WorkflowException('Sale order cannot be edited in its current status: ' . $saleOrder->status);
        }

        return DB::transaction(function () use ($saleOrder, $data) {
            $oldData = $saleOrder->toArray();

            // Update grand total if items changed
            if (isset($data['items'])) {
                $data['grand_total'] = $this->calculateGrandTotal($data['items']);
            }

            // Update order
            $saleOrder->update($this->extractOrderData($data));

            // Update items
            if (isset($data['items'])) {
                $this->syncItems($saleOrder, $data['items']);
            }

            // Log update
            $saleOrder->logEvent('updated', $oldData, $saleOrder->toArray());

            return $saleOrder->load($this->relations);
        });
    }

    /**
     * Submit for review/approval
     */
    public function submitForApproval(SaleOrder $saleOrder, ?string $note = null): SaleOrder
    {
        if (!$saleOrder->canTransitionTo('submitted')) {
            throw new WorkflowException('Sale order cannot be submitted from status: ' . $saleOrder->status);
        }

        return DB::transaction(function () use ($saleOrder, $note) {
            $saleOrder->markSubmitted();
            
            if ($note) {
                $saleOrder->addComment($note, true);
            }
            
            $saleOrder->logEvent('submitted', null, ['status' => $saleOrder->status]);
            
            return $saleOrder;
        });
    }

    /**
     * Review sale order (first level approval)
     */
    public function review(SaleOrder $saleOrder, ?string $note = null, ?int $userId = null): SaleOrder
    {
        if (!$saleOrder->isReviewable()) {
            throw new WorkflowException('Sale order is not in a reviewable state.');
        }

        return DB::transaction(function () use ($saleOrder, $note, $userId) {
            $saleOrder->markReviewed($userId);
            $saleOrder->changeStatus('reviewed', $note);
            
            if ($note) {
                $saleOrder->addComment($note, true);
            }
            
            $saleOrder->logEvent('reviewed', null, ['reviewed_by' => $userId ?? Auth::id()]);
            
            return $saleOrder;
        });
    }

    /**
     * Approve sale order (second level approval)
     */
    public function approve(SaleOrder $saleOrder, ?string $note = null, ?int $userId = null): SaleOrder
    {
        if (!$saleOrder->isApprovable()) {
            throw new WorkflowException('Sale order is not in an approvable state.');
        }

        return DB::transaction(function () use ($saleOrder, $note, $userId) {
            $saleOrder->markApproved($userId);
            $saleOrder->changeStatus('approved', $note);
            
            if ($note) {
                $saleOrder->addComment($note, true);
            }
            
            $saleOrder->logEvent('approved', null, ['approved_by' => $userId ?? Auth::id()]);
            
            return $saleOrder;
        });
    }

    /**
     * Reject sale order
     */
    public function reject(SaleOrder $saleOrder, string $reason, ?array $details = null, ?int $userId = null): SaleOrder
    {
        if (!$saleOrder->canTransitionTo('rejected')) {
            throw new WorkflowException('Sale order cannot be rejected from status: ' . $saleOrder->status);
        }

        return DB::transaction(function () use ($saleOrder, $reason, $details, $userId) {
            $saleOrder->markRejected($reason, $details, $userId);
            $saleOrder->logEvent('rejected', null, [
                'reason' => $reason,
                'details' => $details,
                'rejected_by' => $userId ?? Auth::id(),
            ]);
            
            return $saleOrder;
        });
    }

    /**
     * Confirm sale order (after approval)
     */
    public function confirm(SaleOrder $saleOrder, ?string $note = null): SaleOrder
    {
        if ($saleOrder->status !== 'approved') {
            throw new WorkflowException('Only approved sale orders can be confirmed.');
        }

        return DB::transaction(function () use ($saleOrder, $note) {
            $saleOrder->changeStatus('confirmed', $note);
            
            // Update inventory reservations if needed
            $this->reserveInventory($saleOrder);
            
            $saleOrder->logEvent('confirmed', null, ['status' => $saleOrder->status]);
            
            return $saleOrder;
        });
    }

    /**
     * Cancel sale order
     */
    public function cancel(SaleOrder $saleOrder, string $reason): SaleOrder
    {
        if (!$saleOrder->canTransitionTo('cancelled')) {
            throw new WorkflowException('Sale order cannot be cancelled from status: ' . $saleOrder->status);
        }

        return DB::transaction(function () use ($saleOrder, $reason) {
            $saleOrder->changeStatus('cancelled', $reason);
            
            // Release inventory reservations
            $this->releaseInventory($saleOrder);
            
            $saleOrder->logEvent('cancelled', null, ['reason' => $reason]);
            
            return $saleOrder;
        });
    }

    /**
     * Delete sale order (soft delete)
     */
    public function delete(SaleOrder $saleOrder): void
    {
        if (!$saleOrder->isDeletable()) {
            throw new WorkflowException('Sale order cannot be deleted in its current status.');
        }

        $saleOrder->delete();
        $saleOrder->logEvent('deleted', null, null);
    }

    /**
     * Restore soft-deleted sale order
     */
    public function restore(SaleOrder $saleOrder): void
    {
        $saleOrder->restore();
        $saleOrder->logEvent('restored', null, null);
    }

    /**
     * Force delete sale order
     */
    public function forceDelete(SaleOrder $saleOrder): void
    {
        if ($saleOrder->items()->exists()) {
            throw new WorkflowException('Cannot permanently delete sale order with existing items.');
        }
        
        $saleOrder->forceDelete();
    }

    /**
     * Generate document number
     */
    protected function generateDocumentNumber(array $data): string
    {
        return DocumentNumberSequence::getNextNumberFor(
            $data['organization_id'],
            $data['voucher_type_id'],
            $data['financial_year_id']
        );
    }

    /**
     * Calculate grand total from items
     */
    protected function calculateGrandTotal(array $items): float
    {
        return round(array_sum(array_column($items, 'line_total')), 4);
    }

    /**
     * Extract sale order data from request
     */
    protected function extractOrderData(array $data): array
    {
        return [
            'organization_id' => $data['organization_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'outlet_id' => $data['outlet_id'] ?? null,
            'financial_year_id' => $data['financial_year_id'],
            'customer_id' => $data['customer_id'],
            'voucher_type_id' => $data['voucher_type_id'],
            'document_number' => $data['document_number'] ?? null,
            'order_date' => $data['order_date'],
            'delivery_date' => $data['delivery_date'] ?? null,
            'grand_total' => $data['grand_total'] ?? 0,
            'status' => $data['status'] ?? 'draft',
        ];
    }

    /**
     * Create sale order items
     */
    protected function createItems(SaleOrder $saleOrder, array $items): void
    {
        foreach ($items as $item) {
            $saleOrder->items()->create([
                'organization_id' => $saleOrder->organization_id,
                'product_variant_id' => $item['product_variant_id'],
                'tax_id' => $item['tax_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $this->calculateItemTotal($item),
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    /**
     * Sync sale order items (update existing, create new, delete removed)
     */
    protected function syncItems(SaleOrder $saleOrder, array $items): void
    {
        $incomingIds = collect($items)->pluck('id')->filter()->toArray();
        
        // Delete removed items
        $saleOrder->items()->whereNotIn('id', $incomingIds)->delete();
        
        foreach ($items as $itemData) {
            if (isset($itemData['id']) && $itemData['id']) {
                // Update existing item
                $item = DocumentItem::find($itemData['id']);
                if ($item) {
                    $item->update([
                        'product_variant_id' => $itemData['product_variant_id'],
                        'tax_id' => $itemData['tax_id'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'discount_amount' => $itemData['discount_amount'] ?? 0,
                        'tax_rate' => $itemData['tax_rate'] ?? 0,
                        'line_total' => $this->calculateItemTotal($itemData),
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            } else {
                // Create new item
                $saleOrder->items()->create([
                    'organization_id' => $saleOrder->organization_id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'tax_id' => $itemData['tax_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'line_total' => $this->calculateItemTotal($itemData),
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        }
        
        // Update grand total
        $saleOrder->grand_total = $this->calculateGrandTotal($items);
        $saleOrder->saveQuietly();
    }

    /**
     * Calculate item total
     */
    protected function calculateItemTotal(array $item): float
    {
        $subtotal = $item['quantity'] * $item['unit_price'];
        $discount = $item['discount_amount'] ?? 0;
        $taxAmount = ($subtotal - $discount) * (($item['tax_rate'] ?? 0) / 100);
        
        return round($subtotal - $discount + $taxAmount, 4);
    }

    /**
     * Reserve inventory (placeholder for inventory system integration)
     */
    protected function reserveInventory(SaleOrder $saleOrder): void
    {
        // Implement inventory reservation logic here
        // This would integrate with your inventory management system
    }

    /**
     * Release inventory (placeholder for inventory system integration)
     */
    protected function releaseInventory(SaleOrder $saleOrder): void
    {
        // Implement inventory release logic here
    }
}