<?php

namespace App\Models\Core;

use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Branch;
use App\Models\Core\FinancialYear;
use App\Models\Core\Outlet;
use App\Models\Core\SectionCategory;
use App\Models\Core\Tax;
use App\Models\Core\Warehouse;
use App\Models\Inventory\Brand;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\Unit;
use App\Models\Partner\Customer;
use App\Models\Partner\PartnerCategory;
use App\Models\Partner\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'business_start_date',
        'ntn',
        'strn',
        'incorporation_no',
        'email',
        'contact_no',
        'website',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'longitude',
        'latitude',
        'logo',
        'favicon',
        'currency_code',
        'is_active',
        'policies_locked',
    ];

    protected $casts = [
        'business_start_date' => 'date',
        'is_active' => 'boolean',
        'policies_locked' => 'boolean',
    ];

    // Relationships
    public function financialYears(): HasMany
    {
        return $this->hasMany(FinancialYear::class);
    }
    public function activeFinancialYear()
    {
        return $this->hasOne(FinancialYear::class)->where('is_active', true);
    }


    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses(): HasMany // ADDED: Warehouses linked directly
    {
        return $this->hasMany(Warehouse::class);
    }

    public function outlets(): HasMany // ADDED: Outlets linked directly
    {
        return $this->hasMany(Outlet::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    // Master Data Relationships (REQUIRED for multi-tenancy)
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function sectionCategories(): HasMany
    {
        return $this->hasMany(SectionCategory::class);
    }

    // Partner Relationships
    public function partnerCategories(): HasMany
    {
        return $this->hasMany(PartnerCategory::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    // User Contexts & Permissions
    public function userContexts(): HasMany
    {
        return $this->hasMany(UserContext::class);
    }

    public function userAssignments(): HasMany
    {
        // Polymorphic relationship using HasMany and where clauses
        return $this->hasMany(UserAssignment::class, 'assignable_id')
            ->where('assignable_type', self::class);
    }
}
