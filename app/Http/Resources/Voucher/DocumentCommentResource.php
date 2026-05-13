<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'is_internal' => $this->is_internal,
            'attachments' => $this->attachments,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}