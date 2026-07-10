# University Integrated Management Platform (UIMP)

> Core/Parent Platform — Laravel Implementation

## Overview

The University Integrated Management Platform (UIMP) is the core platform for managing shared university data: students, employees, faculties, departments, campuses, buildings, rooms, and users/roles. It exposes versioned REST APIs (`/api/v1/`) so external subsystems can read and write shared data without duplication.

## Stack

| Component | Technology |
|---|---|
| Backend | Laravel 13.x, PHP 8.5+ |
| Frontend | Blade + Livewire 3 + Tailwind CSS |
| Database | PostgreSQL 15 |
| Cache/Queue | Redis |
| Auth | Laravel Sanctum (15-min tokens + custom 7-day refresh) |
| RBAC | spatie/laravel-permission |
| PDF Reports | barryvdh/laravel-dompdf |
| Excel Reports | openspout/openspout |
| API Docs | knuckleswtf/scribe |

## Architecture

Domain-oriented structure under `app/Domain/`:

```
app/Domain/
├── Auth/          # Users, roles, login, lockout, password reset
├── Students/      # Student records, enrollment, dedup
├── Employees/     # Staff records, academic rank, department assignment
├── Organization/  # Faculties, departments, programs
├── Facilities/    # Campuses, buildings, rooms
├── Subsystems/    # External subsystem registration, API keys, webhooks
├── Audit/         # Immutable audit logs, compliance
└── Notifications/ # Email, SMS, in-app notifications
```

Each domain contains: `Models/`, `Services/`, `Policies/`, `Requests/`, `Resources/`, `Events/`, `Listeners/`.

## RBAC Roles (SDD §3.1)

Hierarchy: `SYSTEM_ADMIN > UNIVERSITY_ADMIN > DEPARTMENT_ADMIN > REGISTRAR_STAFF > HR_STAFF > ACADEMIC_STAFF > STUDENT > EMPLOYEE`

Cross-cutting: `AUDITOR` (read-only), `SUBSYSTEM_DEVELOPER` (API access only)

## Setup

### Prerequisites

- PHP 8.3+ with extensions: `pdo_pgsql`, `redis`, `mbstring`, `openssl`, `xml`
- PostgreSQL 15+
- Redis 7+
- Composer

### Installation

```bash
# Clone and install dependencies
composer install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Create the PostgreSQL database
createdb uimp

# Run migrations and seed roles
php artisan migrate
php artisan db:seed

# Start the development server
php artisan serve
```

### Environment Variables

See `.env.example` for all configuration options. Key UIMP-specific settings:

| Variable | Default | Description |
|---|---|---|
| `SANCTUM_TOKEN_EXPIRATION` | `15` | API access token lifetime (minutes) |
| `UIMP_REFRESH_TOKEN_DAYS` | `7` | Refresh token lifetime (days) |
| `UIMP_LOCKOUT_ATTEMPTS` | `5` | Failed login attempts before lockout |
| `UIMP_LOCKOUT_WINDOW_MINUTES` | `15` | Window for counting failed attempts |
| `UIMP_LOCKOUT_DURATION_MINUTES` | `15` | Duration of account lockout |
| `UIMP_PASSWORD_RESET_EXPIRY_MINUTES` | `60` | Password reset token lifetime |

## API Documentation

After setup, generate API docs:

```bash
php artisan scribe:generate
```

Docs will be available at `/docs`.

## Security

- **Bcrypt cost 12** for password hashing
- **15-minute** API access token expiry
- **30-minute** web session inactivity timeout
- **Account lockout** after 5 failed attempts
- **Audit logging** on every write operation (immutable, append-only)
- **Security headers**: CSP, X-Frame-Options DENY, nosniff, XSS protection
- **Soft deletes only** — hard deletion is never exposed

## Bilingual Support (SDD §4.5)

All user-visible entities store both `name_en` and `name_ar` (NOT NULL). API responses include both fields and select localized `name` based on `Accept-Language` header or user's `preferred_language`. Arabic text is NFC-normalized before persistence.

## License

Proprietary — University of [TBD]
