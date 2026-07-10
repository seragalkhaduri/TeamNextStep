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
 * Publication
 *
 * Represents a research publication registry entry belonging to a
 * ResearchGroup — journal article, conference paper, book chapter,
 * or technical report — with DOI enforcement and citation tracking.
 *
 * SDD Reference: RGMS SDD §3.6.3, §4.2.8
 *
 * @property string $id
 * @property string $research_group_id
 * @property string $title
 * @property string $publication_type
 * @property int $publication_year
 * @property string $status
 * @property string|null $doi
 * @property string|null $journal_name
 * @property string|null $conference_name
 * @property string|null $issn
 * @property string|null $publisher
 * @property float|null $impact_factor
 * @property int $citation_count
 * @property \Illuminate\Support\Carbon|null $citation_updated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class Publication extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed publication_type ENUM values (SDD §4.2.8).
     */
    public const TYPE_JOURNAL_ARTICLE = 'Journal-Article';
    public const TYPE_CONFERENCE_PAPER = 'Conference-Paper';
    public const TYPE_BOOK_CHAPTER = 'Book-Chapter';
    public const TYPE_TECHNICAL_REPORT = 'Technical-Report';

    /**
     * Allowed status ENUM values (SDD §4.2.8).
     */
    public const STATUS_IN_PREPARATION = 'In-Preparation';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_UNDER_REVIEW = 'Under-Review';
    public const STATUS_ACCEPTED = 'Accepted';
    public const STATUS_PUBLISHED = 'Published';
    public const STATUS_RETRACTED = 'Retracted';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'publications';

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
        'publication_type',
        'publication_year',
        'status',
        'doi',
        'journal_name',
        'conference_name',
        'issn',
        'publisher',
        'impact_factor',
        'citation_count',
        'citation_updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'impact_factor' => 'decimal:3',
            'citation_count' => 'integer',
            'citation_updated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research group this publication belongs to.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'research_group_id');
    }

    /**
     * The ordered authors linked to this publication.
     */
    public function publicationAuthors(): HasMany
    {
        return $this->hasMany(PublicationAuthor::class, 'publication_id');
    }

    /**
     * Scope a query to filter publications by lifecycle status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter publications by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('publication_type', $type);
    }
}