<?php

namespace App\Domain\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'subject_en',
        'subject_ar',
        'body_en',
        'body_ar',
        'channels',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
        ];
    }

    public function notifications()
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }
}
