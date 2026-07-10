<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PatentInventor
 *
 * Pure join record linking a Patent to a UIMP member inventor
 * (member_uimp_id), preserving inventor order. No timestamps,
 * no soft delete (SDD §4.2.14).
 *
 * SDD Reference: RGMS SDD §3.7.2, §4.2.14
 *
 * @property string $id
 * @property string $patent_id
 * @property string $member_uimp_id
 * @property int $inventor_order
 */
final class PatentInventor extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'patent_inventors';

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
     * This table has no created_at / updated_at columns.
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
        'patent_id',
        'member_uimp_id',
        'inventor_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inventor_order' => 'integer',
        ];
    }

    /**
     * The patent this inventor record belongs to.
     */
    public function patent(): BelongsTo
    {
        return $this->belongsTo(Patent::class, 'patent_id');
    }
}