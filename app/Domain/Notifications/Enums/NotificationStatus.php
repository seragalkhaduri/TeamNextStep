<?php

namespace App\Domain\Notifications\Enums;

/**
 * Notification status enum (SDD §4.2 — notifications table).
 */
enum NotificationStatus: string
{
    case PENDING = 'PENDING';
    case SENT = 'SENT';
    case FAILED = 'FAILED';
}
