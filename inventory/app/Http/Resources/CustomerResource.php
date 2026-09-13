<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => (string) $this->id, 'full_name' => $this->full_name, 'email' => $this->email, 'phone' => $this->phone, 'version' => (string) $this->version, 'archived_at' => $this->archived_at?->toIso8601String(), 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
