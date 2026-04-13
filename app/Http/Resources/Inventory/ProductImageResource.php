<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => Storage::disk($this->disk)
                ->url($this->path),
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }
}
