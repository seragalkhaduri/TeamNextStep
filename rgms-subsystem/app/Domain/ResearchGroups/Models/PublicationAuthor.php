<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PublicationAuthor
 *
 * Pure join record linking a Publication to a UIMP member author
 * (member_uimp_id), preserving author order and contribution type.
 * No timestamps, no soft delete (SDD §4.2.14).
 *
 * SDD Reference: RGMS SDD §3.6.5, §4.2.14
 *
 * @property string $id
 * @property string $publication_id
 * @property string $member_uimp_id
 * @property int $author_order
 * @property string|null $contribution_type
 */
final class PublicationAuthor extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'publication_authors';

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
        'publication_id',
        'member_uimp_id',
        'author_order',
        'contribution_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'author_order' => 'integer',
        ];
    }

    /**
     * The publication this author record belongs to.
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'publication_id');
    }
}