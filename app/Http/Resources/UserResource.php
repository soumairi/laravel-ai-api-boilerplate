<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'status' => $this->resource->status->value,
            'email_verified_at' => $this->resource->email_verified_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->resource->roles->pluck('name')->values()),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->resource->permissions->pluck('name')->values()),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
