<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ComplianceRecord
 *
 * Represents a permanent regulatory compliance condition tied to a
 * ResearchProject. No SoftDeletes — records are permanent regulatory
 * evidence; they may be updated with resolution notes but are never
 * deleted (SDD §4.2.12).
 *
 * SDD Reference: RGMS SDD §3.10.3, §4.2.12
 *
 * @property string $id
 * @property string $project_id
 * @property string $condition_type
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property string|null $regulatory_reference
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $alert_dispatched_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 */
final class ComplianceRecord extends Model
{
    use HasFactory;
    use HasUuids;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.12).
     */
    public const STATUS_COMPLIANT = 'Compliant';
    public const STATUS_UNDER_REVIEW = 'Under-Review';
    public const STATUS_NON_COMPLIANT = 'Non-Compliant';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'compliance_records';

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
        'condition_type',
        'description',
        'due_date',
        'status',
        'regulatory_reference',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
        'alert_dispatched_at',
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
            'resolved_at' => 'datetime',
            'alert_dispatched_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The project this compliance condition belongs to.
     */
    public function researchProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    /**
     * Scope a query to filter compliance records by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include Non-Compliant records.
     */
    public function scopeNonCompliant(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NON_COMPLIANT);
    }
}