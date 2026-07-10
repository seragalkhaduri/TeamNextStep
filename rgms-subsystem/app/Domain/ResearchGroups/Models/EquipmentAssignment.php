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
 * EquipmentAssignment
 *
 * Represents an equipment booking/reservation. Deliberately does NOT
 * use SoftDeletes — booking records are retained permanently as
 * operational history; cancellations are flagged via status only
 * (SDD §3.9.7, §4.2.11).
 *
 * SDD Reference: RGMS SDD §3.9.7, §4.2.11
 *
 * @property string $id
 * @property string $equipment_id
 * @property string $requester_uimp_id
 * @property \Illuminate\Support\Carbon $start_datetime
 * @property \Illuminate\Support\Carbon $end_datetime
 * @property string $purpose
 * @property string $status
 * @property string|null $requester_notes
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 */
final class EquipmentAssignment extends Model
{
    use HasFactory;
    use HasUuids;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.11).
     */
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_COMPLETED = 'Completed';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'equipment_assignments';

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
        'equipment_id',
        'requester_uimp_id',
        'start_datetime',
        'end_datetime',
        'purpose',
        'status',
        'requester_notes',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The equipment this booking is for.
     */
    public function researchEquipment(): BelongsTo
    {
        return $this->belongsTo(ResearchEquipment::class, 'equipment_id');
    }

    /**
     * Scope a query to only include Confirmed bookings.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope a query to only include future confirmed bookings.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED)
            ->where('start_datetime', '>', now());
    }

    /**
     * Scope a query to only include bookings currently active
     * (current time falls within the booking window).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED)
            ->where('start_datetime', '<=', now())
            ->where('end_datetime', '>=', now());
    }
}