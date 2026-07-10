<?php

namespace App\Domain\Audit\Enums;

/**
 * Audit action enum (SDD §4.2 — audit_logs table).
 */
enum AuditAction: string
{
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case LOGIN = 'LOGIN';
    case LOGOUT = 'LOGOUT';
    case LOGIN_FAILED = 'LOGIN_FAILED';
    case PASSWORD_RESET = 'PASSWORD_RESET';
    case ROLE_ASSIGNED = 'ROLE_ASSIGNED';
    case SUBSYSTEM_REGISTERED = 'SUBSYSTEM_REGISTERED';
}
