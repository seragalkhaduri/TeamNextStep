<?php

namespace App\Domain\Notifications\Services;

use App\Domain\BaseService;
use App\Domain\Notifications\Models\NotificationLog;
use App\Domain\Notifications\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService extends BaseService
{
    /**
     * Dispatch a notification to a recipient using a template and data variables.
     */
    public function send(Model $recipient, string $templateId, array $data = []): NotificationLog
    {
        $template = NotificationTemplate::findOrFail($templateId);
        
        // Determine recipient preferred language (defaults to Arabic)
        $lang = $recipient->preferred_language ?? 'ar';

        // Choose template strings based on language
        $subjectTemplate = ($lang === 'en') ? $template->subject_en : $template->subject_ar;
        $bodyTemplate = ($lang === 'en') ? $template->body_en : $template->body_ar;

        // Compile placeholders: e.g. {{ name }} -> value
        $subject = $this->compileTemplate($subjectTemplate ?? '', $data);
        $body = $this->compileTemplate($bodyTemplate, $data);

        // Initialize status tracker
        $channelsStatus = [];
        foreach ($template->channels as $channel) {
            $channelsStatus[$channel] = 'PENDING';
        }

        // Create log record
        $log = NotificationLog::create([
            'recipient_type' => get_class($recipient),
            'recipient_id' => $recipient->getKey(),
            'template_id' => $templateId,
            'status' => 'PENDING',
            'channels_status' => $channelsStatus,
            'data' => $data,
        ]);

        $this->processDelivery($log, $recipient, $subject, $body);

        return $log;
    }

    /**
     * Parse template body and swap double curly brace placeholders with variables.
     */
    protected function compileTemplate(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{ ' . $key . ' }}', (string) $value, $text);
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }

    /**
     * Process delivery across email, SMS, and in-app channels.
     */
    public function processDelivery(NotificationLog $log, Model $recipient, string $subject, string $body): void
    {
        $channelsStatus = $log->channels_status;
        $allSuccess = true;
        $errors = [];

        foreach ($channelsStatus as $channel => $status) {
            if ($status === 'SENT') {
                continue;
            }

            try {
                if ($channel === 'email') {
                    $this->deliverEmail($recipient, $subject, $body);
                } elseif ($channel === 'sms') {
                    $this->deliverSms($recipient, $body);
                } elseif ($channel === 'in_app') {
                    $this->deliverInApp($recipient, $subject, $body);
                }

                $channelsStatus[$channel] = 'SENT';
            } catch (\Exception $e) {
                $allSuccess = false;
                $channelsStatus[$channel] = 'FAILED';
                $errors[] = $channel . ': ' . $e->getMessage();
            }
        }

        $log->update([
            'channels_status' => $channelsStatus,
            'status' => $allSuccess ? 'SENT' : 'FAILED',
            'error_message' => empty($errors) ? null : implode(' | ', $errors),
            'sent_at' => $allSuccess ? now() : null,
            'failed_at' => !$allSuccess ? now() : null,
        ]);
    }

    /**
     * Email Delivery Hub (stubbed with Log and Mail support).
     */
    protected function deliverEmail(Model $recipient, string $subject, string $body): void
    {
        $email = $recipient->email;
        if (!$email) {
            throw new \Exception('Recipient does not have an email address.');
        }

        // Write to log channel for auditing
        Log::info("📧 Sending email to {$email}: Subject: {$subject} | Body: {$body}");

        // In local/test environment, Mail::raw will output to logs if MAIL_MAILER=log
        Mail::raw($body, function ($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
        });
    }

    /**
     * SMS Delivery Hub (stubbed with Twilio helper structure).
     */
    protected function deliverSms(Model $recipient, string $body): void
    {
        $phone = $recipient->phone;
        if (!$phone) {
            throw new \Exception('Recipient does not have a phone number.');
        }

        Log::info("📱 Sending SMS to {$phone}: Body: {$body}");
        
        // Simulating carrier gateway dispatch
        // Under production twilio API client will be triggered here
    }

    /**
     * In-app Notification Delivery Hub.
     */
    protected function deliverInApp(Model $recipient, string $subject, string $body): void
    {
        Log::info("🔔 Sending In-App Notification to " . get_class($recipient) . " {$recipient->getKey()}: Title: {$subject}");
    }
}
