<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Models\NotificationLog;
use App\Domain\Notifications\Services\NotificationService;
use Illuminate\Console\Command;

class RetryFailedNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'uimp:notifications:retry';

    /**
     * The console command description.
     */
    protected $description = 'Retry delivery of failed notifications up to 3 attempts with backoff';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $failedLogs = NotificationLog::where('status', 'FAILED')
            ->where('retry_count', '<', 3)
            ->with('template')
            ->get();

        if ($failedLogs->isEmpty()) {
            $this->info('No failed notifications eligible for retry.');
            return 0;
        }

        $this->info('Processing ' . $failedLogs->count() . ' failed notifications...');

        foreach ($failedLogs as $log) {
            $recipient = $log->recipient;
            if (!$recipient) {
                $this->error("Recipient missing for notification {$log->id}. Skipping.");
                $log->update(['status' => 'FAILED_PERMANENTLY', 'error_message' => 'Recipient not found.']);
                continue;
            }

            $template = $log->template;
            if (!$template) {
                $this->error("Template missing for notification {$log->id}. Skipping.");
                $log->update(['status' => 'FAILED_PERMANENTLY', 'error_message' => 'Template not found.']);
                continue;
            }

            // Determine language and compile templates
            $lang = $recipient->preferred_language ?? 'ar';
            $subjectTemplate = ($lang === 'en') ? $template->subject_en : $template->subject_ar;
            $bodyTemplate = ($lang === 'en') ? $template->body_en : $template->body_ar;

            $subject = $this->compileTemplate($subjectTemplate ?? '', $log->data ?? []);
            $body = $this->compileTemplate($bodyTemplate, $log->data ?? []);

            $this->info("Retrying notification {$log->id} (Attempt #" . ($log->retry_count + 1) . ")");

            // Increment retry count
            $log->increment('retry_count');

            // Re-attempt delivery
            $notificationService->processDelivery($log, $recipient, $subject, $body);
        }

        $this->info('Retry run complete.');
        return 0;
    }

    protected function compileTemplate(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{ ' . $key . ' }}', (string) $value, $text);
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }
}
