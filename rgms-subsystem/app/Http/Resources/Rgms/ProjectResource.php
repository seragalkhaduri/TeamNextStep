<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use App\Domain\ResearchGroups\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProjectResource
 *
 * @property-read \App\Models\ResearchProject $resource
 *
 * SDD Reference: RGMS SDD §3.3.11
 */
final class ProjectResource extends JsonResource
{
    /**
     * Risk status to badge color mapping (SDD §3.3.11).
     *
     * @var array<string, string>
     */
    private const RISK_BADGE_COLORS = [
        'Low' => 'green',
        'Medium' => 'amber',
        'High' => 'red',
        'Critical' => 'darkred',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $milestones = $this->relationLoaded('projectMilestones')
            ? $this->projectMilestones
            : $this->projectMilestones()->get();

        $milestoneCount = $milestones->count();
        $completedCount = $milestones->where('status', ProjectMilestone::STATUS_COMPLETED)->count();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'research_group_id' => $this->research_group_id,
            'funding_agency' => $this->funding_agency,
            'budget' => $this->budget,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'risk_status' => $this->risk_status,
            'compliance_status' => $this->compliance_status,
            'risk_description' => $this->risk_description,
            'mitigation_actions' => $this->mitigation_actions,
            'milestone_count' => $milestoneCount,
            'completion_percentage' => $milestoneCount > 0
                ? round(($completedCount / $milestoneCount) * 100, 2)
                : 0.0,
            'risk_badge_color' => self::RISK_BADGE_COLORS[$this->risk_status] ?? 'gray',
            'days_remaining' => $this->end_date?->diffInDays(now(), false) !== null
                ? (int) now()->diffInDays($this->end_date, false)
                : null,
            'contributors' => ProjectContributorResource::collection(
                $this->whenLoaded('projectContributors', fn () => $this->projectContributors, $this->projectContributors()->get()),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}