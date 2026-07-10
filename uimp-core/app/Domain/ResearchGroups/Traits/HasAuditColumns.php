<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Traits;

/**
 * HasAuditColumns
 *
 * Provides audit column tracking (created_by, updated_by, deleted_by)
 * for RGMS models. These columns are optional and may be null if the
 * action was performed by the system without an authenticated user context.
 */
trait HasAuditColumns
{
    /**
     * Boot the trait — automatically fill audit columns on create/update.
     */
    public static function bootHasAuditColumns(): void
    {
        static::creating(function ($model): void {
            if (! isset($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (! isset($model->updated_by) && auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model): void {
            if (auth()->check() && in_array('deleted_by', $model->getFillable())) {
                $model->deleted_by = auth()->id();
                $model->saveQuietly();
            }
        });
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Domain\Auth\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Domain\Auth\Models\User::class, 'updated_by');
    }
}
