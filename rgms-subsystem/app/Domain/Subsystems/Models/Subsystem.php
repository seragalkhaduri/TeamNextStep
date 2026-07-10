<?php

namespace App\Domain\Subsystems\Models;

use App\Domain\Audit\Traits\Auditable;
use App\Domain\Subsystems\Enums\SubsystemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Normalizer;

class Subsystem extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'api_key_hash',
        'status',
        'webhook_url',
        'webhook_secret',
        'contact_email',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubsystemStatus::class,
        ];
    }

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    protected static function booted(): void
    {
        static::saving(function (Subsystem $subsystem) {
            if ($subsystem->isDirty('name_ar') && function_exists('normalizer_normalize') && $subsystem->name_ar) {
                $subsystem->name_ar = Normalizer::normalize($subsystem->name_ar, Normalizer::FORM_C);
            }
            if ($subsystem->isDirty('description_ar') && function_exists('normalizer_normalize') && $subsystem->description_ar) {
                $subsystem->description_ar = Normalizer::normalize($subsystem->description_ar, Normalizer::FORM_C);
            }
        });
    }
}
