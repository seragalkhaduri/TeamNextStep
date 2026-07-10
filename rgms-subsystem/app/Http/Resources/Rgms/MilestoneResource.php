<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MilestoneResource
 *
 * @property-read \App\Models\ProjectMilestone $resource
 *
 * SDD Reference: RGMS SDD §3.4.3
 */
final class MilestoneResource extends JsonResource
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
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'completion_date' => $this->completion_date?->toDateString(),
            'status' => $this->status,
            'completion_notes' => $this->completion_notes,
            'deliverables' => DeliverableResource::collection(
                $this->whenLoaded('projectDeliverables', fn () => $this->projectDeliverables, $this->projectDeliverables()->get()),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}