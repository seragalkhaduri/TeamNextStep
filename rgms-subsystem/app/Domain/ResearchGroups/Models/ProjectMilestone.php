<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ProjectMilestone
 *
 * Represents a milestone belonging to a ResearchProject. The "Overdue"
 * status is set exclusively by MilestoneService::detectOverdue() —
 * never accepted via any incoming request (SDD §3.4.5).
 *
 * SDD Reference: RGMS SDD §3.4.3, §4.2.4
 *
 * @property string $id
 * @property string $project_id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $completion_date
 * @property string $status
 * @property string|null $completion_notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class ProjectMilestone extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.4).
     */
    public const STATUS_PENDING = 'Pending';
    public const STATUS_IN_PROGRESS = 'In-Progress';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_OVERDUE = 'Overdue';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_milestones';

    /**
     * The primary key type (UUID via HasUuids trait).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates the primary key is not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'due_date',
        'completion_date',
        'status',
        'completion_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completion_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research project this milestone belongs to.
     */
    public function researchProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    /**
     * The deliverables linked to this milestone.
     */
    public function projectDeliverables(): HasMany
    {
        return $this->hasMany(ProjectDeliverable::class, 'milestone_id');
    }

    /**
     * Scope a query to filter milestones by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include Overdue milestones.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }
}