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
 * FundingSource
 *
 * Represents a grant/funding registration associated with a
 * ResearchGroup — agency, grant reference, allocated amount,
 * currency, and validity period.
 *
 * SDD Reference: RGMS SDD §3.5.3, §4.2.5
 *
 * @property string $id
 * @property string $research_group_id
 * @property string $agency_name
 * @property string $grant_reference
 * @property float $allocated_amount
 * @property string $currency_code
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class FundingSource extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.5).
     */
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_EXPIRED = 'Expired';
    public const STATUS_EXHAUSTED = 'Exhausted';
    public const STATUS_SUSPENDED = 'Suspended';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'funding_sources';

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
        'research_group_id',
        'agency_name',
        'grant_reference',
        'allocated_amount',
        'currency_code',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research group this funding source is registered under.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'research_group_id');
    }

    /**
     * The budget allocations drawn from this funding source.
     */
    public function budgetAllocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class, 'funding_source_id');
    }

    /**
     * Scope a query to only include Active funding sources.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}