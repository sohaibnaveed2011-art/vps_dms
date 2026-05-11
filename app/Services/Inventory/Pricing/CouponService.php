<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\NotFoundException;
use App\Models\Inventory\CouponScope;
use App\Models\Inventory\CouponTarget;
use App\Models\Inventory\CustomerCoupon;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CouponService
{
    private const CACHE_TTL = 900; // 15 minutes
    
    protected array $relations = ['scopes', 'targets', 'customerCoupons.customer'];

    // ==================== EXISTING METHODS ====================
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::query()
            ->with($this->relations)
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['code']), fn($q) => $q->where('code', $filters['code']))
            ->when(!empty($filters['valid_from']), fn($q) => $q->where('valid_from', '>=', $filters['valid_from']))
            ->when(!empty($filters['valid_to']), fn($q) => $q->where('valid_to', '<=', $filters['valid_to']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                if ($this->hasFullTextSupport()) {
                    $q->whereFullText(['code', 'name'], $filters['search']);
                } else {
                    $term = "%{$filters['search']}%";
                    $q->where('code', 'like', $term)->orWhere('name', 'like', $term);
                }
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new coupon with scopes, targets, and customer assignments
     */
    public function create(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {
            // Validate unique code
            $this->validateUniqueCode($data['code'], $data['organization_id']);
            
            // Create coupon
            $coupon = Coupon::create($this->extractCouponData($data));
            
            // Create scopes
            if (!empty($data['scopes'])) {
                $this->batchInsertScopes($coupon->id, $data['scopes']);
            }
            
            // Create targets
            if (!empty($data['targets'])) {
                $this->batchInsertTargets($coupon->id, $data['targets']);
            }
            
            // Assign customers
            if (!empty($data['customers'])) {
                $this->batchAssignCustomers($coupon->id, $data['customers']);
            }
            
            return $coupon->load($this->relations);
        });
    }

    /**
     * Update an existing coupon
     */
    public function update(Coupon $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            // Validate unique code if changing
            if (isset($data['code']) && $data['code'] !== $coupon->code) {
                $this->validateUniqueCode($data['code'], $coupon->organization_id, $coupon->id);
            }
            
            // Update coupon basic data
            $updateData = $this->extractCouponData($data);
            if (!empty($updateData)) {
                $coupon->update($updateData);
            }
            
            // Sync scopes
            if (array_key_exists('scopes', $data)) {
                $this->syncScopes($coupon, $data['scopes'] ?? [], $data['scope_sync_mode'] ?? 'merge');
            }
            
            // Sync targets
            if (array_key_exists('targets', $data)) {
                $this->syncTargets($coupon, $data['targets'] ?? [], $data['target_sync_mode'] ?? 'merge');
            }
            
            // Sync customer assignments
            if (array_key_exists('customers', $data)) {
                $this->syncCustomers($coupon, $data['customers'] ?? [], $data['customer_sync_mode'] ?? 'merge');
            }
            
            // Invalidate cache
            Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
            
            return $coupon->fresh($this->relations);
        });
    }

    /**
     * Find a coupon by ID with optional organization scope
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Coupon
    {
        $query = Coupon::query()->with($this->relations);
        
        if ($withTrashed) {
            $query->withTrashed();
        }
        
        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }
        
        $coupon = $query->find($id);
        
        if (!$coupon) {
            throw new NotFoundException('Coupon not found.');
        }
        
        return $coupon;
    }

    /**
     * Validate a coupon without applying it
     */
    public function validateCoupon(string $code, int $orgId, float $subtotal, ?int $customerId = null, array $items = []): array
    {
        $cacheKey = $this->getCacheKey($orgId, $code);
        
        $coupon = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($code, $orgId) {
            return Coupon::where('code', $code)
                ->where('organization_id', $orgId)
                ->with(['scopes', 'targets'])
                ->first();
        });
        
        $this->validateCouponConditions($coupon, $subtotal, $customerId);
        
        // Validate scope applicability
        if ($customerId) {
            $this->validateCustomerScope($coupon, $customerId);
        }
        
        // Validate targets (products/categories)
        if (!empty($items)) {
            $this->validateTargets($coupon, $items);
        }
        
        $discount = $this->calculateDiscount($coupon, $subtotal);
        
        return [
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'value' => $coupon->value,
            ],
            'discount' => $discount,
            'final_total' => max(0, $subtotal - $discount),
        ];
    }

    /**
     * Apply a coupon (uses validation and increments usage)
     */
    public function applyCoupon(string $code, int $orgId, float $subtotal, ?int $customerId = null, array $items = []): array
    {
        return DB::transaction(function () use ($code, $orgId, $subtotal, $customerId, $items) {
            // Lock for update to prevent race conditions
            $coupon = Coupon::where('code', $code)
                ->where('organization_id', $orgId)
                ->lockForUpdate()
                ->with(['scopes', 'targets'])
                ->first();
            
            $this->validateCouponConditions($coupon, $subtotal, $customerId);
            
            // Validate scope
            if ($customerId) {
                $this->validateCustomerScope($coupon, $customerId);
            }
            
            // Validate targets
            if (!empty($items)) {
                $this->validateTargets($coupon, $items);
            }
            
            // Handle customer-specific coupon
            if ($customerId && $coupon->customerCoupons()->exists()) {
                $this->markCustomerCouponUsed($customerId, $coupon->id);
            }
            
            $discount = $this->calculateDiscount($coupon, $subtotal);
            $coupon->increment('used_count');
            
            // Invalidate cache after usage
            Cache::forget($this->getCacheKey($orgId, $code));
            
            return [
                'coupon_code' => $coupon->code,
                'discount_amount' => $discount,
                'final_total' => max(0, $subtotal - $discount),
                'type' => $coupon->type,
                'value' => $coupon->value,
            ];
        });
    }

    /**
     * Bulk assign coupon to multiple customers
     */
    public function bulkAssignToCustomers(int $couponId, array $customerIds, string $syncMode = 'merge'): array
    {
        return DB::transaction(function () use ($couponId, $customerIds, $syncMode) {
            $coupon = Coupon::find($couponId);
            
            if (!$coupon) {
                throw new NotFoundException('Coupon not found.');
            }
            
            $customerIds = array_unique($customerIds);
            $result = ['added' => 0, 'removed' => 0, 'total' => 0];
            
            switch ($syncMode) {
                case 'replace':
                    // Remove all existing
                    $result['removed'] = $coupon->customerCoupons()->delete();
                    $result['added'] = $this->batchAssignCustomers($couponId, $customerIds);
                    break;
                    
                case 'merge':
                    // Add new customers only
                    $existingIds = $coupon->customerCoupons()
                        ->pluck('customer_id')
                        ->toArray();
                    
                    $newCustomerIds = array_diff($customerIds, $existingIds);
                    $result['added'] = !empty($newCustomerIds) 
                        ? $this->batchAssignCustomers($couponId, $newCustomerIds) 
                        : 0;
                    break;
                    
                case 'remove':
                    // Remove specified customers
                    $result['removed'] = $coupon->customerCoupons()
                        ->whereIn('customer_id', $customerIds)
                        ->where('is_used', false)
                        ->delete();
                    break;
            }
            
            $result['total'] = $coupon->customerCoupons()->count();
            
            // Invalidate cache
            Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
            
            return $result;
        });
    }

    /**
     * Sync scopes for a coupon
     */
    public function syncScopes(Coupon $coupon, array $scopes, string $mode = 'replace'): Coupon
    {
        return DB::transaction(function () use ($coupon, $scopes, $mode) {
            switch ($mode) {
                case 'replace':
                    $coupon->scopes()->delete();
                    if (!empty($scopes)) {
                        $this->batchInsertScopes($coupon->id, $scopes);
                    }
                    break;
                    
                case 'merge':
                    $existingKeys = $coupon->scopes->map(function ($scope) {
                        return $scope->scopeable_type . '|' . $scope->scopeable_id;
                    })->toArray();
                    
                    $scopesToAdd = array_filter($scopes, function ($scope) use ($existingKeys) {
                        $key = $scope['scopeable_type'] . '|' . $scope['scopeable_id'];
                        return !in_array($key, $existingKeys);
                    });
                    
                    if (!empty($scopesToAdd)) {
                        $this->batchInsertScopes($coupon->id, $scopesToAdd);
                    }
                    break;
                    
                case 'remove':
                    foreach ($scopes as $scope) {
                        $coupon->scopes()
                            ->where('scopeable_type', $scope['scopeable_type'])
                            ->where('scopeable_id', $scope['scopeable_id'])
                            ->delete();
                    }
                    break;
            }
            
            Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
            
            return $coupon->fresh($this->relations);
        });
    }

    /**
     * Sync targets for a coupon
     */
    public function syncTargets(Coupon $coupon, array $targets, string $mode = 'replace'): Coupon
    {
        return DB::transaction(function () use ($coupon, $targets, $mode) {
            switch ($mode) {
                case 'replace':
                    $coupon->targets()->delete();
                    if (!empty($targets)) {
                        $this->batchInsertTargets($coupon->id, $targets);
                    }
                    break;
                    
                case 'merge':
                    $existingKeys = $coupon->targets->map(function ($target) {
                        return $target->targetable_type . '|' . $target->targetable_id;
                    })->toArray();
                    
                    $targetsToAdd = array_filter($targets, function ($target) use ($existingKeys) {
                        $key = $target['targetable_type'] . '|' . $target['targetable_id'];
                        return !in_array($key, $existingKeys);
                    });
                    
                    if (!empty($targetsToAdd)) {
                        $this->batchInsertTargets($coupon->id, $targetsToAdd);
                    }
                    break;
                    
                case 'remove':
                    foreach ($targets as $target) {
                        $coupon->targets()
                            ->where('targetable_type', $target['targetable_type'])
                            ->where('targetable_id', $target['targetable_id'])
                            ->delete();
                    }
                    break;
            }
            
            Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
            
            return $coupon->fresh($this->relations);
        });
    }

    /**
     * Sync customer assignments
     */
    public function syncCustomers(Coupon $coupon, array $customerIds, string $mode = 'replace'): Coupon
    {
        return DB::transaction(function () use ($coupon, $customerIds, $mode) {
            switch ($mode) {
                case 'replace':
                    $coupon->customerCoupons()->delete();
                    if (!empty($customerIds)) {
                        $this->batchAssignCustomers($coupon->id, $customerIds);
                    }
                    break;
                    
                case 'merge':
                    $existingIds = $coupon->customerCoupons()
                        ->pluck('customer_id')
                        ->toArray();
                    
                    $newCustomerIds = array_diff($customerIds, $existingIds);
                    if (!empty($newCustomerIds)) {
                        $this->batchAssignCustomers($coupon->id, $newCustomerIds);
                    }
                    break;
                    
                case 'remove':
                    $coupon->customerCoupons()
                        ->whereIn('customer_id', $customerIds)
                        ->where('is_used', false)
                        ->delete();
                    break;
            }
            
            Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
            
            return $coupon->fresh($this->relations);
        });
    }

    /**
     * Get coupon statistics for dashboard
     */
    public function getStatistics(int $orgId): array
    {
        $cacheKey = "coupon:stats:{$orgId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId) {
            $query = Coupon::where('organization_id', $orgId);
            
            return [
                'total_coupons' => $query->count(),
                'active_coupons' => (clone $query)->where('is_active', true)->count(),
                'expired_coupons' => (clone $query)
                    ->where('valid_to', '<', now())
                    ->where('is_active', true)
                    ->count(),
                'upcoming_coupons' => (clone $query)
                    ->where('valid_from', '>', now())
                    ->where('is_active', true)
                    ->count(),
                'total_usages' => (clone $query)->sum('used_count'),
                'customer_specific_coupons' => (clone $query)->has('customerCoupons')->count(),
                'average_discount_rate' => $this->calculateAverageDiscountRate($orgId),
                'most_used_coupon' => $this->getMostUsedCoupon($orgId),
                'coupon_by_type' => $this->getCouponCountByType($orgId),
            ];
        });
    }

    /**
     * Duplicate an existing coupon
     */
    public function duplicate(Coupon $coupon, ?int $createdBy = null): Coupon
    {
        return DB::transaction(function () use ($coupon, $createdBy) {
            // Generate unique code
            $newCode = $this->generateUniqueCode($coupon->code);
            
            // Create duplicate data
            $duplicateData = $coupon->toArray();
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at'], $duplicateData['deleted_at']);
            $duplicateData['code'] = $newCode;
            $duplicateData['name'] = $coupon->name . ' (Copy)';
            $duplicateData['used_count'] = 0;
            $duplicateData['created_by'] = $createdBy ?? $coupon->created_by;
            
            $newCoupon = Coupon::create($duplicateData);
            
            // Duplicate scopes
            foreach ($coupon->scopes as $scope) {
                CouponScope::create([
                    'coupon_id' => $newCoupon->id,
                    'scopeable_type' => $scope->scopeable_type,
                    'scopeable_id' => $scope->scopeable_id,
                ]);
            }
            
            // Duplicate targets
            foreach ($coupon->targets as $target) {
                CouponTarget::create([
                    'coupon_id' => $newCoupon->id,
                    'targetable_type' => $target->targetable_type,
                    'targetable_id' => $target->targetable_id,
                ]);
            }
            
            // Note: Do NOT duplicate customer assignments
            
            return $newCoupon->load($this->relations);
        });
    }

    /**
     * Toggle coupon active status
     */
    public function toggleStatus(Coupon $coupon): Coupon
    {
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();
        
        Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
        
        return $coupon;
    }

    /**
     * Get coupons assigned to a specific customer
     */
    public function getCustomerCoupons(int $customerId, int $orgId, bool $onlyAvailable = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = Coupon::where('organization_id', $orgId)
            ->whereHas('customerCoupons', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->with(['customerCoupons' => function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            }]);
        
        if ($onlyAvailable) {
            $query->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_to', '>=', now())
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhereRaw('used_count < usage_limit');
                })
                ->whereHas('customerCoupons', function ($q) use ($customerId) {
                    $q->where('customer_id', $customerId)
                        ->where('is_used', false);
                });
        }
        
        return $query->get();
    }

    // ==================== EXISTING PUBLIC METHODS ====================
    
    public function findByCode(string $code, int $orgId): ?Coupon
    {
        $cacheKey = $this->getCacheKey($orgId, $code);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($code, $orgId) {
            return Coupon::where('code', $code)
                ->where('organization_id', $orgId)
                ->first();
        });
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
        Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
    }

    public function restore(Coupon $coupon): void
    {
        if (!$coupon->trashed()) {
            throw new NotFoundException('Coupon is not deleted.');
        }
        
        $coupon->restore();
    }

    public function forceDelete(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            $coupon->scopes()->forceDelete();
            $coupon->targets()->forceDelete();
            $coupon->customerCoupons()->forceDelete();
            $coupon->forceDelete();
        });
        
        Cache::forget($this->getCacheKey($coupon->organization_id, $coupon->code));
    }

    // ==================== PRIVATE HELPER METHODS ====================

    private function extractCouponData(array $data): array
    {
        $couponFields = [
            'organization_id', 'code', 'name', 'description', 'type', 'value',
            'min_order_amount', 'max_discount', 'valid_from', 'valid_to',
            'usage_limit', 'usage_limit_per_customer', 'is_active', 'created_by'
        ];
        
        return array_filter($data, function ($key) use ($couponFields) {
            return in_array($key, $couponFields);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function batchInsertScopes(int $couponId, array $scopes): void
    {
        $now = now();
        $records = array_map(fn($scope) => [
            'coupon_id' => $couponId,
            'scopeable_type' => $scope['scopeable_type'],
            'scopeable_id' => $scope['scopeable_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $scopes);
        
        CouponScope::insert($records);
    }

    private function batchInsertTargets(int $couponId, array $targets): void
    {
        $now = now();
        $records = array_map(fn($target) => [
            'coupon_id' => $couponId,
            'targetable_type' => $target['targetable_type'],
            'targetable_id' => $target['targetable_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $targets);
        
        CouponTarget::insert($records);
    }

    private function batchAssignCustomers(int $couponId, array $customerIds): int
    {
        $now = now();
        $chunks = array_chunk($customerIds, 500);
        $total = 0;
        
        foreach ($chunks as $chunk) {
            $records = array_map(fn($customerId) => [
                'customer_id' => $customerId,
                'coupon_id' => $couponId,
                'is_used' => false,
                'used_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk);
            
            CustomerCoupon::insert($records);
            $total += count($records);
        }
        
        return $total;
    }

    private function validateCouponConditions(?Coupon $coupon, float $subtotal, ?int $customerId): void
    {
        if (!$coupon) {
            throw ValidationException::withMessages(['code' => 'Invalid coupon code.']);
        }

        // Ensure we are comparing against the start of the current minute to avoid millisecond drift
        $currentTime = now();
        if (!$coupon->is_active) {
            throw ValidationException::withMessages(['code' => 'Coupon is inactive.']);
        }

        // Check valid_from (Start of day)
        if ($coupon->valid_from && $currentTime->lt($coupon->valid_from->startOfDay())) {
            throw ValidationException::withMessages(['code' => 'Coupon is not yet valid.']);
        }

        // Check valid_to (End of day)
        // We use endOfDay() so a coupon valid to 2026-08-31 works until 23:59:59
        if ($coupon->valid_to && $currentTime->gt($coupon->valid_to->endOfDay())) {
            throw ValidationException::withMessages(['code' => 'Coupon has expired.']);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['code' => 'Coupon usage limit exceeded.']);
        }

        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'code' => "Minimum order amount of {$coupon->min_order_amount} required."
            ]);
        }

        if ($customerId && $coupon->usage_limit_per_customer) {
            $customerUsage = CustomerCoupon::where('customer_id', $customerId)
                ->where('coupon_id', $coupon->id)
                ->value('used_count') ?? 0;

            if ($customerUsage >= $coupon->usage_limit_per_customer) {
                throw ValidationException::withMessages([
                    'code' => "You have reached the usage limit for this coupon."
                ]);
            }
        }
    }

    private function validateCustomerScope(Coupon $coupon, int $customerId): void
    {
        // If coupon has customer assignments, check if customer is assigned
        if ($coupon->customerCoupons()->exists()) {
            $isAssigned = $coupon->customerCoupons()
                ->where('customer_id', $customerId)
                ->exists();
            
            if (!$isAssigned) {
                throw ValidationException::withMessages([
                    'code' => 'This coupon is not available for you.'
                ]);
            }
        }
    }

    private function validateTargets(Coupon $coupon, array $items): void
    {
        // If no targets defined, coupon applies to all items
        if (!$coupon->targets()->exists()) {
            return;
        }
        
        $targetableTypes = $coupon->targets->pluck('targetable_type')->unique();
        $targetableIds = $coupon->targets->pluck('targetable_id')->toArray();
        
        $hasValidItem = false;
        
        foreach ($items as $item) {
            $itemType = $this->getModelClass($item['type']);
            $itemId = $item['id'];
            
            // Check if item type and ID match any target
            if ($targetableTypes->contains($itemType) && in_array($itemId, $targetableIds)) {
                $hasValidItem = true;
                break;
            }
            
            // For categories, check if product belongs to category
            if ($item['type'] === 'product' && $coupon->targets()->where('targetable_type', 'App\\Models\\Inventory\\Category')->exists()) {
                $productCategories = $this->getProductCategories($itemId);
                $categoryIds = $coupon->targets
                    ->where('targetable_type', 'App\\Models\\Inventory\\Category')
                    ->pluck('targetable_id')
                    ->toArray();
                
                if (array_intersect($productCategories, $categoryIds)) {
                    $hasValidItem = true;
                    break;
                }
            }
        }
        
        if (!$hasValidItem) {
            throw ValidationException::withMessages([
                'code' => 'This coupon does not apply to any items in your cart.'
            ]);
        }
    }

    private function getModelClass(string $type): string
    {
        $mapping = [
            'product' => 'App\\Models\\Inventory\\Product',
            'category' => 'App\\Models\\Inventory\\Category',
            'variant' => 'App\\Models\\Inventory\\Variant',
        ];
        
        return $mapping[$type] ?? $type;
    }

    private function getProductCategories(int $productId): array
    {
        // Implement based on your product-category relationship
        $product = \App\Models\Inventory\Product::find($productId);
        return $product ? $product->categories()->pluck('categories.id')->toArray() : [];
    }

    private function markCustomerCouponUsed(int $customerId, int $couponId): void
    {
        $customerCoupon = CustomerCoupon::where('customer_id', $customerId)
            ->where('coupon_id', $couponId)
            ->where('is_used', false)
            ->first();
        
        if (!$customerCoupon) {
            throw ValidationException::withMessages(['code' => 'Coupon not available for this customer.']);
        }
        
        $customerCoupon->update([
            'is_used' => true,
            'used_at' => now(),
            'used_count' => DB::raw('used_count + 1')
        ]);
    }

    private function getCacheKey(int $orgId, string $code): string
    {
        return "coupon:{$orgId}:{$code}";
    }

    private function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = match ($coupon->type) {
            'percentage' => $subtotal * ($coupon->value / 100),
            'fixed' => $coupon->value,
            default => 0,
        };
        
        // Apply max discount if set
        if ($coupon->max_discount && $discount > $coupon->max_discount) {
            $discount = $coupon->max_discount;
        }
        
        return min($discount, $subtotal);
    }

    protected function validateUniqueCode(string $code, int $organizationId, ?int $ignoreId = null): void
    {
        $query = Coupon::where('organization_id', $organizationId)
                    ->where('code', $code);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'code' => 'Coupon code must be unique within the organization.'
            ]);
        }
    }

    private function generateUniqueCode(string $originalCode): string
    {
        $newCode = $originalCode . '_copy';
        $counter = 1;
        
        while (Coupon::where('code', $newCode)->exists()) {
            $newCode = $originalCode . '_copy_' . $counter;
            $counter++;
        }
        
        return $newCode;
    }

    private function calculateAverageDiscountRate(int $orgId): float
    {
        return Coupon::where('organization_id', $orgId)
            ->where('type', 'percentage')
            ->avg('value') ?? 0;
    }

    private function getMostUsedCoupon(int $orgId): ?array
    {
        $coupon = Coupon::where('organization_id', $orgId)
            ->orderBy('used_count', 'desc')
            ->first();
        
        return $coupon ? [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'used_count' => $coupon->used_count,
        ] : null;
    }

    private function getCouponCountByType(int $orgId): array
    {
        return Coupon::where('organization_id', $orgId)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();
    }

    private function hasFullTextSupport(): bool
    {
        $driver = DB::connection()->getDriverName();
        return in_array($driver, ['mysql', 'pgsql']);
    }
}