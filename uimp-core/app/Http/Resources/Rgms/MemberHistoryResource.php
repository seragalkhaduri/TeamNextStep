<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MemberHistoryResource
 *
 * @property-read \App\Models\MembershipHistory $resource
 *
 * SDD Reference: RGMS SDD §3.2.11
 */
final class MemberHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'membership_id' => $this->membership_id,
            'previous_role' => $this->previous_role,
            'new_role' => $this->new_role,
            'changed_by' => $this->changed_by,
            'changed_at' => $this->changed_at?->toIso8601String(),
            'change_reason' => $this->change_reason,
        ];
    }
}