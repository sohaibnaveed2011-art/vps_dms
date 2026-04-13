<?php

namespace App\Models\Voucher;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'prefix',
        'module',
        'next_number',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
