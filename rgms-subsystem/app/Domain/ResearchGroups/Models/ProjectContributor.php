<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProjectContributor
 *
 * Pure join record linking a ResearchProject to a UIMP member
 * (member_uimp_id) with an optional free-text contributor role.
 * No timestamps, no soft delete, no audit columns (SDD §4.2.14).
 *
 * SDD Reference: RGMS SDD §3.3.9, §3.3.13, §4.2.14
 *
 * @property string $id
 * @property string $project_id
 * @property string $member_uimp_id
 * @property string|null $contributor_role
 */
final class ProjectContributor extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_contributors';

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
        'project_id',
        'member_uimp_id',
        'contributor_role',
    ];

    /**
     * The research project this contributor is linked to.
     */
    public function researchProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }
}