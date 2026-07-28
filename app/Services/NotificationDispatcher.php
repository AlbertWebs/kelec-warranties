<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\WarrantyStatus;
use App\Jobs\SendWarrantyConfirmation;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyNote;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationDispatcher
{
    public function __construct(
        protected SettingsService $settingsService,
        protected SmsService $smsService,
        protected AuditLogger $auditLogger,
    ) {}

    public function sendWarrantyNotification(Warranty $warranty, string $type, bool $queue = true): void
    {
        if ($queue) {
            SendWarrantyConfirmation::dispatch($warranty->id, $type);

            return;
        }

        $this->sendNow($warranty, $type);
    }

    public function sendNow(Warranty $warranty, string $type): void
    {
        $template = NotificationTemplate::query()->where('key', $type)->where('is_active', true)->first();
        $customer = $warranty->customer;
        $replacements = [
            '{{customer_name}}' => $customer->full_name,
            '{{product_name}}' => $warranty->displayProductName(),
            '{{product_model}}' => $warranty->displayModel() ?? '',
            '{{serial_number}}' => $warranty->serial_number,
            '{{warranty_reference}}' => $warranty->reference,
            '{{warranty_start_date}}' => optional($warranty->warranty_start_date)->format('d M Y') ?? 'Pending',
            '{{warranty_expiry_date}}' => optional($warranty->warranty_expiry_date)->format('d M Y') ?? 'Pending',
            '{{warranty_status}}' => $warranty->status instanceof WarrantyStatus ? $warranty->status->label() : (string) $warranty->status,
            '{{support_phone}}' => $this->settingsService->get('support_phone', ''),
            '{{support_email}}' => $this->settingsService->get('support_email', ''),
            '{{lookup_link}}' => url('/warranty-lookup?reference='.$warranty->reference),
        ];

        $emailBody = strtr($template?->email_body ?? $this->defaultEmailBody($type), $replacements);
        $smsBody = strtr($template?->sms_body ?? $this->defaultSmsBody($type), $replacements);
        $subject = strtr($template?->subject ?? 'K-Elec Warranty Update', $replacements);
        $channel = $template?->channel ?? NotificationChannel::Both;

        $emailBody = $this->ensureLookupDetails($emailBody, $replacements['{{lookup_link}}'], false);
        $smsBody = $this->ensureLookupDetails($smsBody, $replacements['{{lookup_link}}'], true);

        if (in_array($channel, [NotificationChannel::Email, NotificationChannel::Both], true) && $customer->email) {
            $this->sendEmail($warranty, $customer, $type, $customer->email, $subject, $emailBody);
        }

        if (in_array($channel, [NotificationChannel::Sms, NotificationChannel::Both], true) && $customer->mobile_normalized) {
            $this->sendSms($warranty, $customer, $type, $customer->mobile_normalized, $smsBody);
        }
    }

    public function sendCustomMessage(
        Customer $customer,
        ?Warranty $warranty,
        string $type,
        string $subject,
        string $emailBody,
        string $smsBody,
    ): void {
        if ($customer->email) {
            $this->sendEmail($warranty, $customer, $type, $customer->email, $subject, $emailBody);
        }

        if ($customer->mobile_normalized) {
            $this->sendSms($warranty, $customer, $type, $customer->mobile_normalized, $smsBody);
        }
    }

    public function resend(Warranty $warranty, string $type = 'warranty_activated'): void
    {
        $this->sendWarrantyNotification($warranty, $type, false);
        $this->auditLogger->log('notification_resent', $warranty, null, ['type' => $type]);
    }

    protected function sendEmail(?Warranty $warranty, Customer $customer, string $type, string $recipient, string $subject, string $body): void
    {
        $log = NotificationLog::create([
            'warranty_id' => $warranty?->id,
            'customer_id' => $customer->id,
            'notification_type' => $type,
            'channel' => NotificationChannel::Email,
            'recipient' => $recipient,
            'message' => $body,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        try {
            Mail::raw($body, function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            });
            $log->update(['status' => 'sent', 'sent_at' => now(), 'provider_response' => 'mail_sent']);
            if ($warranty) {
                $this->auditLogger->log('notification_sent', $warranty, null, ['channel' => 'email', 'type' => $type]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'failed_at' => now(),
                'provider_response' => Str::limit($e->getMessage(), 500),
                'retry_count' => ((int) ($log->retry_count ?? 0)) + 1,
            ]);
            if ($warranty) {
                $this->auditLogger->log('notification_failed', $warranty, null, ['channel' => 'email', 'type' => $type]);
            }
        }
    }

    protected function sendSms(?Warranty $warranty, Customer $customer, string $type, string $recipient, string $body): void
    {
        $log = NotificationLog::create([
            'warranty_id' => $warranty?->id,
            'customer_id' => $customer->id,
            'notification_type' => $type,
            'channel' => NotificationChannel::Sms,
            'recipient' => $recipient,
            'message' => $body,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        $result = $this->smsService->send($recipient, $body, $type);
        $retryCount = (int) ($log->retry_count ?? 0);

        $log->update([
            'status' => $result['ok'] ? 'sent' : 'failed',
            'sent_at' => $result['ok'] ? now() : null,
            'failed_at' => $result['ok'] ? null : now(),
            'provider_response' => $result['response'] ?? null,
            'retry_count' => $result['ok'] ? $retryCount : $retryCount + 1,
        ]);

        if ($warranty) {
            $this->auditLogger->log($result['ok'] ? 'notification_sent' : 'notification_failed', $warranty, null, [
                'channel' => 'sms',
                'type' => $type,
            ]);
        }
    }

    public function addNote(Warranty $warranty, User $user, string $body, bool $internal = true): WarrantyNote
    {
        $note = WarrantyNote::create([
            'warranty_id' => $warranty->id,
            'user_id' => $user->id,
            'is_internal' => $internal,
            'body' => $body,
        ]);

        $this->auditLogger->log('administrator_note_added', $warranty, null, ['note_id' => $note->id]);

        return $note;
    }

    protected function defaultEmailBody(string $type): string
    {
        return match ($type) {
            'warranty_pending_verification' => "Hello {{customer_name}},\n\nYour warranty registration {{warranty_reference}} for {{product_name}} ({{serial_number}}) has been received and is pending verification.\nLookup: {{lookup_link}} (use your reference and registered mobile number)\n\nSupport: {{support_phone}} / {{support_email}}",
            'warranty_rejected' => "Hello {{customer_name}},\n\nYour warranty registration {{warranty_reference}} could not be approved. Please contact support for assistance.\n\nSupport: {{support_phone}} / {{support_email}}",
            'pos_warranty_registered' => "Hello {{customer_name}},\n\nYour Brand Shop purchase has been registered automatically.\nWarranty {{warranty_reference}} for {{product_name}} ({{serial_number}}) is {{warranty_status}}.\nExpiry: {{warranty_expiry_date}}\nLookup: {{lookup_link}}",
            'customer_details_completion' => "Hello {{customer_name}},\n\nYour K-Elec warranty {{warranty_reference}} was created from a Brand Shop purchase. Please complete your contact details using the secure link sent by SMS/email.",
            default => "Hello {{customer_name}},\n\nYour warranty {{warranty_reference}} for {{product_name}} ({{serial_number}}) is {{warranty_status}}.\nStart: {{warranty_start_date}}\nExpiry: {{warranty_expiry_date}}\nLookup: {{lookup_link}}\n\nSupport: {{support_phone}} / {{support_email}}",
        };
    }

    protected function defaultSmsBody(string $type): string
    {
        return match ($type) {
            'warranty_pending_verification' => 'K-Elec: Warranty {{warranty_reference}} received and pending verification. Lookup: {{lookup_link}} using your registered mobile.',
            'warranty_rejected' => 'K-Elec: Warranty {{warranty_reference}} was not approved. Lookup: {{lookup_link}} or contact support.',
            'pos_warranty_registered' => 'K-Elec: Brand Shop warranty {{warranty_reference}} is active. Expiry {{warranty_expiry_date}}.',
            'customer_details_completion' => 'K-Elec: Complete your warranty {{warranty_reference}} details using the secure link provided.',
            default => 'K-Elec: Warranty {{warranty_reference}} for {{product_name}} is {{warranty_status}}. Expiry {{warranty_expiry_date}}. Lookup {{lookup_link}}',
        };
    }

    protected function ensureLookupDetails(string $message, string $lookupLink, bool $sms): string
    {
        if (str_contains($message, $lookupLink) || str_contains(strtolower($message), 'lookup')) {
            return $message;
        }

        if ($sms) {
            return rtrim($message).' Lookup: '.$lookupLink.' (use your registered mobile).';
        }

        return rtrim($message)."\nLookup: {$lookupLink} (use your warranty reference and registered mobile number).";
    }
}
