<?php

namespace App\Livewire;

use App\Domain\Audit\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    // Filters properties
    public $action = '';
    public $entityType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $q = '';

    // URL Query strings tracking
    protected $queryString = [
        'action' => ['except' => ''],
        'entityType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'q' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Auto-login for local development preview
        if (app()->environment('local') && !auth()->check()) {
            $sysadmin = \App\Domain\Auth\Models\User::where('username', 'sysadmin')->first();
            if ($sysadmin) {
                auth()->login($sysadmin);
            }
        }

        // Enforce compliance boundaries on mount
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['AUDITOR', 'SYSTEM_ADMIN']),
            403,
            'Forbidden'
        );
    }

    public function updating(): void
    {
        // Reset pagination page on filter change
        $this->resetPage();
    }

    public function render()
    {
        // Enforce compliance boundaries on render as well
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['AUDITOR', 'SYSTEM_ADMIN']),
            403,
            'Forbidden'
        );

        $query = AuditLog::query()->with(['actorUser', 'actorSubsystem']);

        if ($this->action) {
            $query->where('action', $this->action);
        }

        if ($this->entityType) {
            $query->where('entity_type', $this->entityType);
        }

        if ($this->dateFrom) {
            $query->where('created_at', '>=', $this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', $this->dateTo . ' 23:59:59');
        }

        if ($this->q) {
            $search = $this->q;
            $query->where(function ($q) use ($search) {
                $q->where('entity_type', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);

        return view('livewire.audit-log-viewer', [
            'logs' => $logs,
            'uniqueActions' => AuditLog::select('action')->distinct()->pluck('action'),
            'uniqueEntityTypes' => AuditLog::select('entity_type')->distinct()->pluck('entity_type'),
        ])->layout('components.layouts.app');
    }
}
