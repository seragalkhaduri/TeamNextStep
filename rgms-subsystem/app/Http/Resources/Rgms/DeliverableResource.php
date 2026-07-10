<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DeliverableResource
 *
 * @property-read \App\Models\ProjectDeliverable $resource
 *
 * SDD Reference: RGMS SDD §3.4.3, §4.2.14
 */
final class DeliverableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'milestone_id' => $this->milestone_id,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'submission_date' => $this->submission_date?->toDateString(),
            'approval_status' => $this->approval_status,
            'submitted_by' => $this->submitted_by,
            'approved_by' => $this->approved_by,
        ];
    }
}