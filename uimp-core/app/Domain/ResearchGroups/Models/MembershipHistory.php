<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MembershipHistory
 *
 * Immutable, append-only record of a single role or status change
 * applied to a GroupMembership.
 *
 * SDD Reference: RGMS SDD §3.2.9, §4.2.14
 *
 * @property string $id
 * @property string $membership_id
 * @property string|null $previous_role
 * @property string|null $new_role
 * @property string|null $previous_status
 * @property string|null $new_status
 * @property string|null $change_reason
 * @property string $changed_by
 * @property \Illuminate\Support\Carbon $changed_at
 */
final class MembershipHistory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'membership_history';

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
     * This table has no created_at / updated_at columns —
     * only changed_at (set via CURRENT_TIMESTAMP default).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'membership_id',
        'previous_role',
        'new_role',
        'previous_status',
        'new_status',
        'change_reason',
        'changed_by',
        'changed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * The membership this history record belongs to.
     */
    public function groupMembership(): BelongsTo
    {
        return $this->belongsTo(GroupMembership::class, 'membership_id');
    }
}