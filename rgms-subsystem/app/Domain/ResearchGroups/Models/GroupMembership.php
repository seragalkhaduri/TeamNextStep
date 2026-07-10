<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * GroupMembership
 *
 * Represents the allocation of a single UIMP-validated member
 * (Staff, Student, or External Collaborator) to a research group,
 * with an assigned research role and workload percentage.
 *
 * SDD Reference: RGMS SDD §3.2.9, §4.2.2
 *
 * @property string $id
 * @property string $group_id
 * @property string $member_uimp_id
 * @property string $member_type
 * @property string $role
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int $workload_percentage
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class GroupMembership extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed member_type ENUM values (SDD §4.2.2).
     */
    public const MEMBER_TYPE_STAFF = 'Staff';
    public const MEMBER_TYPE_STUDENT = 'Student';
    public const MEMBER_TYPE_EXTERNAL = 'External';

    /**
     * Allowed role ENUM values (SDD §4.2.2).
     */
    public const ROLE_PI = 'PI';
    public const ROLE_CO_I = 'Co-I';
    public const ROLE_RESEARCH_ASSISTANT = 'Research-Assistant';
    public const ROLE_GRADUATE_RESEARCHER = 'Graduate-Researcher';
    public const ROLE_EXTERNAL_COLLABORATOR = 'External-Collaborator';

    /**
     * Allowed status ENUM values (SDD §4.2.2).
     */
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_SUSPENDED = 'Suspended';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group_memberships';

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
        'group_id',
        'member_uimp_id',
        'member_type',
        'role',
        'start_date',
        'end_date',
        'workload_percentage',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'workload_percentage' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research group this membership belongs to.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'group_id');
    }

    /**
     * The full role/status change history of this membership.
     */
    public function membershipHistory(): HasMany
    {
        return $this->hasMany(MembershipHistory::class, 'membership_id');
    }

    /**
     * Whether this member's total active workload across all groups
     * exceeds 100%. Pure data aggregation — no business decision is
     * made here (SDD §3.2.2, FR-MEM-011).
     */
    public function isOverAllocated(): bool
    {
        $totalWorkload = self::query()
            ->where('member_uimp_id', $this->member_uimp_id)
            ->where('status', self::STATUS_ACTIVE)
            ->sum('workload_percentage');

        return $totalWorkload > 100;
    }
}