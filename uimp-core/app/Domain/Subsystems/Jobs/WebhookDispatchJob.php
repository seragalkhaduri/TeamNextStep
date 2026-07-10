<?php

namespace App\Domain\Subsystems\Jobs;

use App\Domain\Subsystems\Models\Subsystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $auditLogId,
        protected string $eventType,
        protected string $entityType,
        protected string $entityId,
        protected string $action,
        protected ?array $newValue,
        protected ?array $oldValue,
        protected ?string $actorUserId,
        protected ?string $actorSubsystemId,
        protected string $timestamp
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find all active subsystems with a webhook URL configured
        $subsystems = Subsystem::where('status', \App\Domain\Subsystems\Enums\SubsystemStatus::ACTIVE->value)
            ->whereNotNull('webhook_url')
            ->whereNull('deleted_at')
            ->get();

        if ($subsystems->isEmpty()) {
            return;
        }

        // Construct the webhook event payload
        $payload = [
            'eventId' => $this->auditLogId,
            'eventType' => strtolower($this->entityType) . '.' . strtolower($this->action),
            'timestamp' => $this->timestamp,
            'actor' => [
                'userId' => $this->actorUserId,
                'subsystemId' => $this->actorSubsystemId,
            ],
            'resource' => [
                'type' => $this->entityType,
                'id' => $this->entityId,
            ],
            'data' => [
                'old' => $this->oldValue,
                'new' => $this->newValue,
            ],
        ];

        $jsonPayload = json_encode($payload);

        foreach ($subsystems as $subsystem) {
            $this->dispatchToSubsystem($subsystem, $jsonPayload);
        }
    }

    /**
     * Deliver the webhook payload to a single subsystem with HMAC signature.
     */
    protected function dispatchToSubsystem(Subsystem $subsystem, string $jsonPayload): void
    {
        $signature = hash_hmac('sha256', $jsonPayload, $subsystem->webhook_secret ?? '');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-UIMP-Signature' => $signature,
                'User-Agent' => 'UIMP-Webhook-Agent/1.0',
            ])
            ->timeout(5)
            ->post($subsystem->webhook_url, json_decode($jsonPayload, true));

            if (!$response->successful()) {
                Log::warning("Webhook delivery failed for Subsystem {$subsystem->id} (HTTP {$response->status()})");
                throw new \Exception("HTTP status {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error("Webhook error for Subsystem {$subsystem->id}: " . $e->getMessage());
            // If running on sync queue, do not re-throw to avoid crashing write requests in development
            if (config('queue.default') === 'sync') {
                return;
            }
            // Re-throw so the job is marked as failed and queued for retry
            throw $e;
        }
    }
}
