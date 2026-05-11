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
use App\Models\Inventory\CouponScope;
use App\Models\Inventory\Product;
use App\Models\Inventory\Unit;
use App\Models\Partner\Customer;
use App\Models\Partner\PartnerCategory;
use App\Models\Partner\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property \Illuminate\Support\Carbon|null $business_start_date
 * @property string|null $ntn
 * @property string|null $strn
 * @property string|null $incorporation_no
 * @property string|null $email
 * @property string|null $contact_no
 * @property string|null $website
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string $country
 * @property string|null $zip_code
 * @property numeric|null $longitude
 * @property numeric|null $latitude
 * @property string|null $logo
 * @property string|null $favicon
 * @property string $currency_code
 * @property bool $is_active
 * @property bool $policies_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read FinancialYear|null $activeFinancialYear
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Branch> $branches
 * @property-read int|null $branches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Brand> $brands
 * @property-read int|null $brands_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponScope> $couponScopes
 * @property-read int|null $coupon_scopes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Customer> $customers
 * @property-read int|null $customers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FinancialYear> $financialYears
 * @property-read int|null $financial_years_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Outlet> $outlets
 * @property-read int|null $outlets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PartnerCategory> $partnerCategories
 * @property-read int|null $partner_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SectionCategory> $sectionCategories
 * @property-read int|null $section_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Supplier> $suppliers
 * @property-read int|null $suppliers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tax> $taxes
 * @property-read int|null $taxes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Unit> $units
 * @property-read int|null $units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserAssignment> $userAssignments
 * @property-read int|null $user_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserContext> $userContexts
 * @property-read int|null $user_contexts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Warehouse> $warehouses
 * @property-read int|null $warehouses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereBusinessStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereFavicon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereIncorporationNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereNtn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization wherePoliciesLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereStrn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization withoutTrashed()
 * @mixin \Eloquent
 */
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

    public function couponScopes(): MorphMany
    {
        return $this->morphMany(CouponScope::class, 'Scopeable');
    }
}
