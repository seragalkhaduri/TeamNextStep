<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ProjectDeliverable
 *
 * Represents a deliverable linked to a ProjectMilestone, tracking
 * submission and approval workflow.
 *
 * NOTE: Unlike other RGMS primary entity tables, project_deliverables
 * has no deleted_by column (SDD §4.2.14 CREATE TABLE — inconsistency
 * flagged for resolution at Migration/Trait level).
 *
 * SDD Reference: RGMS SDD §3.4.3, §4.2.14
 *
 * @property string $id
 * @property string $milestone_id
 * @property string $description
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $submission_date
 * @property string $approval_status
 * @property string|null $submitted_by
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class ProjectDeliverable extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed approval_status ENUM values (SDD §4.2.14).
     */
    public const APPROVAL_PENDING = 'Pending';
    public const APPROVAL_SUBMITTED = 'Submitted';
    public const APPROVAL_APPROVED = 'Approved';
    public const APPROVAL_REJECTED = 'Rejected';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_deliverables';

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
        'milestone_id',
        'description',
        'due_date',
        'submission_date',
        'approval_status',
        'submitted_by',
        'approved_by',
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
            'submission_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The milestone this deliverable belongs to.
     */
    public function projectMilestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    /**
     * Scope a query to filter deliverables by approval status.
     */
    public function scopeByApprovalStatus(Builder $query, string $status): Builder
    {
        return $query->where('approval_status', $status);
    }
}