<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\Promotion;
use App\Exceptions\NotFoundException;
use App\Models\Inventory\PromotionScope;
use App\Models\Inventory\PromotionTarget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    private const CACHE_TTL = 900; // 15 minutes
    private const CACHE_KEY_PREFIX = 'promotion';

    protected array $relations = ['scopes', 'targets'];

    // ==================== CORE CRUD OPERATIONS ====================

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Promotion::query()
            ->with($this->relations)
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['stackable']), fn($q) => $q->where('stackable', (bool) $filters['stackable']))
            ->when(!empty($filters['start_date']), fn($q) => $q->where('start_date', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->where('end_date', '<=', $filters['end_date']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where('name', 'like', $term);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Promotion
    {
        $query = Promotion::query()->with($this->relations);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $promotion = $query->find($id);

        if (!$promotion) {
            throw new NotFoundException('Promotion not found.');
        }

        return $promotion;
    }

    public function findByName(string $name, int $orgId): ?Promotion
    {
        $cacheKey = $this->getCacheKey($orgId, $name);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name, $orgId) {
            return Promotion::where('name', $name)
                ->where('organization_id', $orgId)
                ->first();
        });
    }

    public function create(array $data): Promotion
    {
        return DB::transaction(function () use ($data) {
            $promotion = Promotion::create($this->extractPromotionData($data));
            
            if (!empty($data['scopes'])) {
                $this->batchInsertScopes($promotion->id, $data['scopes']);
            }
            
            if (!empty($data['targets'])) {
                $this->batchInsertTargets($promotion->id, $data['targets']);
            }
            
            $this->clearActivePromotionsCache($data['organization_id']);
            
            return $promotion->load($this->relations);
        });
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        return DB::transaction(function () use ($promotion, $data) {
            $updateData = $this->extractPromotionData($data);
            if (!empty($updateData)) {
                $promotion->update($updateData);
            }
            
            if (array_key_exists('scopes', $data)) {
                $this->syncScopes($promotion, $data['scopes'] ?? [], $data['scope_sync_mode'] ?? 'replace');
            }
            
            if (array_key_exists('targets', $data)) {
                $this->syncTargets($promotion, $data['targets'] ?? [], $data['target_sync_mode'] ?? 'replace');
            }
            
            $this->clearPromotionCache($promotion->organization_id, $promotion->name);
            $this->clearActivePromotionsCache($promotion->organization_id);
            
            return $promotion->fresh($this->relations);
        });
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
        $this->clearPromotionCache($promotion->organization_id, $promotion->name);
        $this->clearActivePromotionsCache($promotion->organization_id);
    }

    public function restore(Promotion $promotion): void
    {
        if (!$promotion->trashed()) {
            throw new NotFoundException('Promotion is not deleted.');
        }
        
        $promotion->restore();
        $this->clearActivePromotionsCache($promotion->organization_id);
    }

    public function forceDelete(Promotion $promotion): void
    {
        DB::transaction(function () use ($promotion) {
            $promotion->scopes()->forceDelete();
            $promotion->targets()->forceDelete();
            $promotion->forceDelete();
        });
        
        $this->clearPromotionCache($promotion->organization_id, $promotion->name);
        $this->clearActivePromotionsCache($promotion->organization_id);
    }

    // ==================== SYNC OPERATIONS ====================

    /**
     * Sync scopes for a promotion
     */
    public function syncScopes(Promotion $promotion, array $scopes, string $mode = 'replace'): Promotion
    {
        return DB::transaction(function () use ($promotion, $scopes, $mode) {
            switch ($mode) {
                case 'replace':
                    $promotion->scopes()->delete();
                    if (!empty($scopes)) {
                        $this->batchInsertScopes($promotion->id, $scopes);
                    }
                    break;
                    
                case 'merge':
                    $existingKeys = $promotion->scopes->map(function ($scope) {
                        return $scope->scopeable_type . '|' . $scope->scopeable_id;
                    })->toArray();
                    
                    $scopesToAdd = array_filter($scopes, function ($scope) use ($existingKeys) {
                        $key = $scope['scopeable_type'] . '|' . $scope['scopeable_id'];
                        return !in_array($key, $existingKeys);
                    });
                    
                    if (!empty($scopesToAdd)) {
                        $this->batchInsertScopes($promotion->id, $scopesToAdd);
                    }
                    break;
                    
                case 'remove':
                    foreach ($scopes as $scope) {
                        $promotion->scopes()
                            ->where('scopeable_type', $scope['scopeable_type'])
                            ->where('scopeable_id', $scope['scopeable_id'])
                            ->delete();
                    }
                    break;
            }
            
            $this->clearActivePromotionsCache($promotion->organization_id);
            
            return $promotion->fresh($this->relations);
        });
    }

    /**
     * Sync targets for a promotion
     */
    public function syncTargets(Promotion $promotion, array $targets, string $mode = 'replace'): Promotion
    {
        return DB::transaction(function () use ($promotion, $targets, $mode) {
            switch ($mode) {
                case 'replace':
                    $promotion->targets()->delete();
                    if (!empty($targets)) {
                        $this->batchInsertTargets($promotion->id, $targets);
                    }
                    break;
                    
                case 'merge':
                    $existingKeys = $promotion->targets->map(function ($target) {
                        return $target->targetable_type . '|' . $target->targetable_id;
                    })->toArray();
                    
                    $targetsToAdd = array_filter($targets, function ($target) use ($existingKeys) {
                        $key = $target['targetable_type'] . '|' . $target['targetable_id'];
                        return !in_array($key, $existingKeys);
                    });
                    
                    if (!empty($targetsToAdd)) {
                        $this->batchInsertTargets($promotion->id, $targetsToAdd);
                    }
                    break;
                    
                case 'remove':
                    foreach ($targets as $target) {
                        $promotion->targets()
                            ->where('targetable_type', $target['targetable_type'])
                            ->where('targetable_id', $target['targetable_id'])
                            ->delete();
                    }
                    break;
            }
            
            $this->clearActivePromotionsCache($promotion->organization_id);
            
            return $promotion->fresh($this->relations);
        });
    }

    // ==================== PROMOTION VALIDATION & APPLICATION ====================

    public function validatePromotion(int $promotionId, int $orgId, float $subtotal, ?int $customerId = null): array
    {
        $promotion = $this->find($promotionId, $orgId);
        
        $this->validatePromotionConditions($promotion, $subtotal, $customerId);
        
        $discount = $this->calculateDiscount($promotion, $subtotal);
        
        return [
            'valid' => true,
            'promotion' => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'type' => $promotion->type,
                'value' => $promotion->value,
                'stackable' => $promotion->stackable,
            ],
            'discount' => $discount,
            'final_total' => max(0, $subtotal - $discount),
        ];
    }

    public function applyPromotion(int $promotionId, int $orgId, float $subtotal, ?int $customerId = null): array
    {
        return DB::transaction(function () use ($promotionId, $orgId, $subtotal, $customerId) {
            /** @var Promotion|null $promotion */
            $promotion = Promotion::where('id', $promotionId)
                ->where('organization_id', $orgId)
                ->lockForUpdate()
                ->first();
            
            if (!$promotion) {
                throw new NotFoundException('Promotion not found.');
            }
            
            $this->validatePromotionConditions($promotion, $subtotal, $customerId);
            
            if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
                throw ValidationException::withMessages([
                    'promotion' => 'Promotion usage limit exceeded.'
                ]);
            }
            
            $discount = $this->calculateDiscount($promotion, $subtotal);
            
            $promotion->used_count = $promotion->used_count + 1;
            $promotion->save();
            
            $this->clearPromotionCache($orgId, $promotion->name);
            $this->clearActivePromotionsCache($orgId);
            
            return [
                'promotion_id' => $promotion->id,
                'promotion_name' => $promotion->name,
                'discount_amount' => $discount,
                'final_total' => max(0, $subtotal - $discount),
                'type' => $promotion->type,
                'value' => $promotion->value,
                'stackable' => $promotion->stackable,
            ];
        });
    }

    public function getBestApplicablePromotions(
        string $scopeType, 
        int $scopeId, 
        float $subtotal,
        ?string $targetType = null, 
        ?int $targetId = null
    ): array {
        $promotions = $this->getActivePromotions($scopeType, $scopeId, $targetType, $targetId);
        
        $applicablePromotions = [];
        
        foreach ($promotions as $promotion) {
            try {
                $this->validatePromotionConditions($promotion, $subtotal);
                $discount = $this->calculateDiscount($promotion, $subtotal);
                
                $applicablePromotions[] = [
                    'promotion_id' => $promotion->id,
                    'promotion_name' => $promotion->name,
                    'discount_amount' => $discount,
                    'final_total' => max(0, $subtotal - $discount),
                    'type' => $promotion->type,
                    'value' => $promotion->value,
                    'stackable' => $promotion->stackable,
                ];
            } catch (ValidationException $e) {
                continue;
            }
        }
        
        usort($applicablePromotions, function ($a, $b) {
            return $b['discount_amount'] <=> $a['discount_amount'];
        });
        
        return $applicablePromotions;
    }

    // ==================== PROMOTION QUERIES ====================

    public function getActivePromotions(
        string $scopeType, 
        int $scopeId, 
        ?string $targetType = null, 
        ?int $targetId = null
    ): \Illuminate\Database\Eloquent\Collection {
        $cacheKey = $this->getActivePromotionsCacheKey($scopeType, $scopeId, $targetType, $targetId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($scopeType, $scopeId, $targetType, $targetId) {
            $query = Promotion::active()
                ->with($this->relations)
                ->orderBy('priority', 'desc');
            
            $query->whereHas('scopes', function ($q) use ($scopeType, $scopeId) {
                $q->where('scopeable_type', $scopeType)
                    ->where('scopeable_id', $scopeId);
            });
            
            if ($targetType && $targetId) {
                $query->whereHas('targets', function ($q) use ($targetType, $targetId) {
                    $q->where('targetable_type', $targetType)
                        ->where('targetable_id', $targetId);
                });
            }
            
            return $query->get();
        });
    }

    public function getStatistics(int $orgId): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . ":stats:{$orgId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orgId) {
            $query = Promotion::where('organization_id', $orgId);
            $now = now();
            
            return [
                'total_promotions' => (clone $query)->count(),
                'active_promotions' => (clone $query)->active()->count(),
                'expired_promotions' => (clone $query)
                    ->where('end_date', '<', $now)
                    ->where('is_active', true)
                    ->count(),
                'upcoming_promotions' => (clone $query)
                    ->where('start_date', '>', $now)
                    ->where('is_active', true)
                    ->count(),
                'total_usages' => (clone $query)->sum('used_count'),
                'stackable_promotions' => (clone $query)->where('stackable', true)->count(),
                'promotion_by_type' => (clone $query)
                    ->select('type', DB::raw('count(*) as total'))
                    ->groupBy('type')
                    ->pluck('total', 'type')
                    ->toArray(),
                'average_discount_value' => (clone $query)->avg('value') ?? 0,
            ];
        });
    }

    // ==================== PROMOTION MANAGEMENT ====================

    public function duplicate(Promotion $promotion, ?int $createdBy = null): Promotion
    {
        return DB::transaction(function () use ($promotion, $createdBy) {
            $newName = $this->generateUniqueName($promotion->name);
            
            $duplicateData = $promotion->toArray();
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at'], $duplicateData['deleted_at']);
            $duplicateData['name'] = $newName;
            $duplicateData['used_count'] = 0;
            $duplicateData['created_by'] = $createdBy ?? $promotion->created_by;
            
            $newPromotion = Promotion::create($duplicateData);
            
            foreach ($promotion->scopes as $scope) {
                PromotionScope::create([
                    'promotion_id' => $newPromotion->id,
                    'scopeable_type' => $scope->scopeable_type,
                    'scopeable_id' => $scope->scopeable_id,
                ]);
            }
            
            foreach ($promotion->targets as $target) {
                PromotionTarget::create([
                    'promotion_id' => $newPromotion->id,
                    'targetable_type' => $target->targetable_type,
                    'targetable_id' => $target->targetable_id,
                ]);
            }
            
            $this->clearActivePromotionsCache($promotion->organization_id);
            
            return $newPromotion->load($this->relations);
        });
    }

    public function toggleStatus(Promotion $promotion): Promotion
    {
        $promotion->is_active = !$promotion->is_active;
        $promotion->save();
        
        $this->clearPromotionCache($promotion->organization_id, $promotion->name);
        $this->clearActivePromotionsCache($promotion->organization_id);
        
        return $promotion;
    }

    public function bulkAssignScopes(array $promotionIds, array $scopes, string $mode = 'merge'): array
    {
        return DB::transaction(function () use ($promotionIds, $scopes, $mode) {
            $result = ['added' => 0, 'removed' => 0, 'total' => 0];
            
            foreach ($promotionIds as $promotionId) {
                $promotion = Promotion::find($promotionId);
                if (!$promotion) continue;
                
                switch ($mode) {
                    case 'replace':
                        $result['removed'] += $promotion->scopes()->delete();
                        if (!empty($scopes)) {
                            $result['added'] += $this->batchInsertScopesCount($promotionId, $scopes);
                        }
                        break;
                        
                    case 'merge':
                        $existingKeys = $promotion->scopes->map(function ($scope) {
                            return $scope->scopeable_type . '|' . $scope->scopeable_id;
                        })->toArray();
                        
                        $scopesToAdd = array_filter($scopes, function ($scope) use ($existingKeys) {
                            $key = $scope['scopeable_type'] . '|' . $scope['scopeable_id'];
                            return !in_array($key, $existingKeys);
                        });
                        
                        if (!empty($scopesToAdd)) {
                            $result['added'] += $this->batchInsertScopesCount($promotionId, $scopesToAdd);
                        }
                        break;
                        
                    case 'remove':
                        foreach ($scopes as $scope) {
                            $result['removed'] += $promotion->scopes()
                                ->where('scopeable_type', $scope['scopeable_type'])
                                ->where('scopeable_id', $scope['scopeable_id'])
                                ->delete();
                        }
                        break;
                }
                
                $result['total'] += $promotion->scopes()->count();
                $this->clearActivePromotionsCache($promotion->organization_id);
            }
            
            return $result;
        });
    }

    // ==================== PRIVATE HELPER METHODS ====================

    private function extractPromotionData(array $data): array
    {
        $promotionFields = [
            'organization_id', 'name', 'type', 'value', 'priority', 'stackable',
            'min_order_amount', 'usage_limit', 'used_count', 'start_date', 
            'end_date', 'is_active', 'created_by'
        ];
        
        return array_filter($data, function ($key) use ($promotionFields) {
            return in_array($key, $promotionFields);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function batchInsertScopes(int $promotionId, array $scopes): void
    {
        $now = now();
        $records = array_map(fn($scope) => [
            'promotion_id' => $promotionId,
            'scopeable_type' => $scope['scopeable_type'],
            'scopeable_id' => $scope['scopeable_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $scopes);
        
        foreach (array_chunk($records, 500) as $chunk) {
            PromotionScope::insert($chunk);
        }
    }

    private function batchInsertScopesCount(int $promotionId, array $scopes): int
    {
        $now = now();
        $records = array_map(fn($scope) => [
            'promotion_id' => $promotionId,
            'scopeable_type' => $scope['scopeable_type'],
            'scopeable_id' => $scope['scopeable_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $scopes);
        
        $count = 0;
        foreach (array_chunk($records, 500) as $chunk) {
            $count += PromotionScope::insert($chunk);
        }
        
        return $count;
    }

    private function batchInsertTargets(int $promotionId, array $targets): void
    {
        $now = now();
        $records = array_map(fn($target) => [
            'promotion_id' => $promotionId,
            'targetable_type' => $target['targetable_type'],
            'targetable_id' => $target['targetable_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $targets);
        
        foreach (array_chunk($records, 500) as $chunk) {
            PromotionTarget::insert($chunk);
        }
    }

    private function validatePromotionConditions(Promotion $promotion, float $subtotal, ?int $customerId = null): void
    {
        if (!$promotion->is_active) {
            throw ValidationException::withMessages(['promotion' => 'Promotion is inactive.']);
        }
        
        $currentTime = now();
        
        if ($promotion->start_date && $currentTime->lt($promotion->start_date->startOfDay())) {
            throw ValidationException::withMessages(['promotion' => 'Promotion is not yet valid.']);
        }
        
        if ($promotion->end_date && $currentTime->gt($promotion->end_date->endOfDay())) {
            throw ValidationException::withMessages(['promotion' => 'Promotion has expired.']);
        }
        
        if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
            throw ValidationException::withMessages(['promotion' => 'Promotion usage limit exceeded.']);
        }
        
        if ($promotion->min_order_amount && $subtotal < $promotion->min_order_amount) {
            throw ValidationException::withMessages([
                'promotion' => "Minimum order amount of {$promotion->min_order_amount} required."
            ]);
        }
    }

    private function calculateDiscount(Promotion $promotion, float $subtotal): float
    {
        $discount = match ($promotion->type) {
            'percentage' => $subtotal * ($promotion->value / 100),
            'fixed' => $promotion->value,
            default => 0,
        };
        
        return min($discount, $subtotal);
    }

    private function getCacheKey(int $orgId, string $name): string
    {
        return self::CACHE_KEY_PREFIX . ":{$orgId}:" . md5($name);
    }

    private function getActivePromotionsCacheKey(string $scopeType, int $scopeId, ?string $targetType, ?int $targetId): string
    {
        $key = self::CACHE_KEY_PREFIX . ":active:{$scopeType}:{$scopeId}";
        if ($targetType && $targetId) {
            $key .= ":{$targetType}:{$targetId}";
        }
        return $key;
    }

    private function clearPromotionCache(int $orgId, string $name): void
    {
        Cache::forget($this->getCacheKey($orgId, $name));
    }

    private function clearActivePromotionsCache(int $orgId): void
    {
        // Clear using pattern - in production use Redis patterns or tags
        Cache::flush();
    }

    private function generateUniqueName(string $originalName): string
    {
        $newName = $originalName . ' (Copy)';
        $counter = 1;
        
        while (Promotion::where('name', $newName)->exists()) {
            $counter++;
            $newName = $originalName . " (Copy {$counter})";
        }
        
        return $newName;
    }
}