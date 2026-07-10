<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GroupStatusHistory
 *
 * Immutable, append-only record of a single status transition
 * (old_status -> new_status) applied to a ResearchGroup.
 *
 * SDD Reference: RGMS SDD §3.1.13, §4.2.14
 *
 * @property string $id
 * @property string $group_id
 * @property string $old_status
 * @property string $new_status
 * @property string|null $justification
 * @property string $transitioned_by
 * @property \Illuminate\Support\Carbon $transitioned_at
 */
final class GroupStatusHistory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group_status_history';

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
     * only transitioned_at (set via CURRENT_TIMESTAMP default).
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
        'group_id',
        'old_status',
        'new_status',
        'justification',
        'transitioned_by',
        'transitioned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transitioned_at' => 'datetime',
        ];
    }

    /**
     * The research group this status transition belongs to.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'group_id');
    }
}