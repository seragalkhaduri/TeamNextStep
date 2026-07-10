<?php

namespace App\Domain\Notifications\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'template_id',
        'status',
        'channels_status',
        'data',
        'retry_count',
        'error_message',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'channels_status' => 'array',
            'data' => 'array',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
