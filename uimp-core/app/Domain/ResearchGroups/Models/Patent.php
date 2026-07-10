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
 * Patent
 *
 * Represents a patent record belonging to a ResearchGroup, tracked
 * through its lifecycle: Filed -> Under-Examination -> Granted /
 * Rejected; Granted -> Expired (SDD §3.7.2, FR-PUB-007).
 *
 * SDD Reference: RGMS SDD §3.7.3, §4.2.9
 *
 * @property string $id
 * @property string $research_group_id
 * @property string $title
 * @property string|null $patent_number
 * @property string $registration_authority
 * @property \Illuminate\Support\Carbon $filing_date
 * @property \Illuminate\Support\Carbon|null $grant_date
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class Patent extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.9).
     */
    public const STATUS_FILED = 'Filed';
    public const STATUS_UNDER_EXAMINATION = 'Under-Examination';
    public const STATUS_GRANTED = 'Granted';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_EXPIRED = 'Expired';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'patents';

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
        'title',
        'patent_number',
        'registration_authority',
        'filing_date',
        'grant_date',
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
            'filing_date' => 'date',
            'grant_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research group this patent belongs to.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'research_group_id');
    }

    /**
     * The ordered inventors linked to this patent.
     */
    public function patentInventors(): HasMany
    {
        return $this->hasMany(PatentInventor::class, 'patent_id');
    }

    /**
     * Scope a query to filter patents by lifecycle status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}