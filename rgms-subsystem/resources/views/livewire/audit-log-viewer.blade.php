<div style="font-family: system-ui, -apple-system, sans-serif; background-color: #f3f4f6; min-height: 100vh; padding: 2rem;">
    <!-- Container -->
    <div style="max-width: 1200px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); padding: 2rem;">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 1.5rem; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 700; color: #1e1b4b; margin: 0;">Compliance Audit Logs</h1>
                <p style="font-size: 0.9rem; color: #6b7280; margin: 0.5rem 0 0 0;">System-wide immutable audit trail query viewer (FR-AUD-002)</p>
            </div>
            <div style="background-color: #e0e7ff; color: #4f46e5; font-size: 0.8rem; font-weight: 600; padding: 0.5rem 1rem; border-radius: 9999px;">
                Secured Audit Interface
            </div>
        </div>

        <!-- Filter Box -->
        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1rem; font-weight: 600; color: #374151; margin-top: 0; margin-bottom: 1rem;">Filter Audit Trail</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                
                <!-- Query Search -->
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 0.4rem;">Search text</label>
                    <input type="text" wire:model.live.debounce.300ms="q" placeholder="Type, IP, User Agent..." style="width: 90%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem;" />
                </div>

                <!-- Action Dropdown -->
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 0.4rem;">Action</label>
                    <select wire:model.live="action" style="width: 100%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; background-color: white;">
                        <option value="">All Actions</option>
                        @foreach($uniqueActions as $act)
                            <option value="{{ $act }}">{{ $act }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Entity Type Dropdown -->
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 0.4rem;">Resource Type</label>
                    <select wire:model.live="entityType" style="width: 100%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; background-color: white;">
                        <option value="">All Types</option>
                        @foreach($uniqueEntityTypes as $ent)
                            <option value="{{ $ent }}">{{ $ent }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 0.4rem;">Date From</label>
                    <input type="date" wire:model.live="dateFrom" style="width: 90%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem;" />
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 0.4rem;">Date To</label>
                    <input type="date" wire:model.live="dateTo" style="width: 90%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem;" />
                </div>

            </div>
        </div>

        <!-- Logs Table -->
        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 1rem; color: #374151; font-weight: 600;">Timestamp</th>
                        <th style="padding: 1rem; color: #374151; font-weight: 600;">Actor</th>
                        <th style="padding: 1rem; color: #374151; font-weight: 600;">Resource</th>
                        <th style="padding: 1rem; color: #374151; font-weight: 600;">Action</th>
                        <th style="padding: 1rem; color: #374151; font-weight: 600;">IP Address</th>
                        <th style="padding: 1rem; color: #374151; font-weight: 600;">Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr style="border-bottom: 1px solid #f3f4f6; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 1rem; white-space: nowrap; color: #4b5563;">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td style="padding: 1rem; color: #1f2937;">
                                @if($log->actorUser)
                                    <span style="font-weight: 600; color: #4f46e5;">👤 {{ $log->actorUser->username }}</span>
                                @elseif($log->actorSubsystem)
                                    <span style="font-weight: 600; color: #059669;">⚙️ {{ $log->actorSubsystem->name_en }}</span>
                                @else
                                    <span style="color: #9ca3af; font-style: italic;">System / Guest</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; color: #4b5563;">
                                <strong style="color: #374151;">{{ $log->entity_type }}</strong>
                                <br />
                                <span style="font-size: 0.8rem; font-family: monospace; color: #6b7280;">{{ $log->entity_id }}</span>
                            </td>
                            <td style="padding: 1rem; white-space: nowrap;">
                                @php
                                    $badgeColor = match($log->action) {
                                        'CREATE' => ['#d1fae5', '#065f46'],
                                        'UPDATE' => ['#fef3c7', '#92400e'],
                                        'DELETE' => ['#fee2e2', '#991b1b'],
                                        'LOGIN' => ['#e0f2fe', '#075985'],
                                        default => ['#f3f4f6', '#374151']
                                    };
                                @endphp
                                <span style="background-color: {{ $badgeColor[0] }}; color: {{ $badgeColor[1] }}; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td style="padding: 1rem; font-family: monospace; color: #4b5563;">
                                {{ $log->ip_address }}
                            </td>
                            <td style="padding: 1rem; max-width: 300px;">
                                @if($log->old_value || $log->new_value)
                                    <details style="cursor: pointer; font-size: 0.8rem; background-color: #f9fafb; padding: 0.5rem; border-radius: 4px; border: 1px solid #e5e7eb;">
                                        <summary style="font-weight: 600; color: #4f46e5;">View JSON Diff</summary>
                                        <div style="margin-top: 0.5rem; overflow-x: auto; max-height: 200px; font-family: monospace; text-align: left;">
                                            @if($log->old_value)
                                                <div style="color: #991b1b; background-color: #fef2f2; padding: 0.25rem; margin-bottom: 0.25rem;">
                                                    <strong>- Old:</strong> {{ json_encode($log->old_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                                </div>
                                            @endif
                                            @if($log->new_value)
                                                <div style="color: #065f46; background-color: #ecfdf5; padding: 0.25rem;">
                                                    <strong>+ New:</strong> {{ json_encode($log->new_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span style="color: #9ca3af; font-style: italic; font-size: 0.8rem;">No changes recorded</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem; text-align: center; color: #9ca3af; font-style: italic;">
                                No audit log records found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 1.5rem;">
            {{ $logs->links() }}
        </div>

    </div>
</div>
