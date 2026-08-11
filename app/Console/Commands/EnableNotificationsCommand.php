<?php

namespace App\Console\Commands;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class EnableNotificationsCommand extends Command
{
    protected $signature = 'notifications:enable
                            {--disable : Turn SMS live sending off (templates stay active)}
                            {--sms-only : Enable SMS only}
                            {--email-only : Ensure email/templates only (do not enable SMS)}';

    protected $description = 'Activate SMS and/or email notifications for warranty messages';

    public function handle(SettingsService $settings): int
    {
        if ($this->option('disable')) {
            $settings->set('sms_enabled', false, 'sms', 'boolean');
            $this->warn('SMS live sending is now OFF (messages will be logged as mock).');
            $this->printStatus($settings);

            return self::SUCCESS;
        }

        $enableSms = ! $this->option('email-only');
        $enableEmail = ! $this->option('sms-only');

        if ($enableSms) {
            $settings->set('sms_enabled', true, 'sms', 'boolean');
            $this->info('SMS live sending enabled.');
        }

        if ($enableEmail) {
            $fromAddress = (string) $settings->get('mail_from_address', '');
            $fromName = (string) $settings->get('mail_from_name', '');

            if ($fromAddress === '') {
                $settings->set(
                    'mail_from_address',
                    (string) config('mail.from.address', 'warranties@k-elec.co.ke'),
                    'email',
                    'string'
                );
            }

            if ($fromName === '') {
                $settings->set(
                    'mail_from_name',
                    (string) config('mail.from.name', 'K-Elec Warranties'),
                    'email',
                    'string'
                );
            }

            $this->info('Email notification settings confirmed.');
        }

        $templatesUpdated = NotificationTemplate::query()->update([
            'is_active' => true,
            'channel' => NotificationChannel::Both,
        ]);

        $this->info("Notification templates activated with SMS & Email channel ({$templatesUpdated} templates).");
        $this->newLine();
        $this->printStatus($settings);

        return self::SUCCESS;
    }

    protected function printStatus(SettingsService $settings): void
    {
        $smsEnabled = (bool) $settings->get('sms_enabled', false);
        $partnerId = trim((string) $settings->get('sms_partner_id', ''));
        $senderId = trim((string) $settings->get('sms_sender_id', ''));
        $apiKey = trim((string) $settings->get('sms_api_key', ''));
        $mailer = (string) config('mail.default');
        $fromAddress = (string) $settings->get('mail_from_address', config('mail.from.address'));
        $activeTemplates = NotificationTemplate::query()->where('is_active', true)->count();

        $this->table(
            ['Setting', 'Value'],
            [
                ['SMS enabled', $smsEnabled ? 'yes' : 'no'],
                ['SMS partner ID', $partnerId !== '' ? $partnerId : 'MISSING'],
                ['SMS sender ID', $senderId !== '' ? $senderId : 'MISSING'],
                ['SMS API key', $apiKey !== '' ? 'set' : 'MISSING'],
                ['Mailer', $mailer],
                ['Mail from', $fromAddress !== '' ? $fromAddress : 'MISSING'],
                ['Active templates', (string) $activeTemplates],
            ]
        );

        if ($smsEnabled && ($partnerId === '' || $senderId === '' || $apiKey === '')) {
            $this->warn('SMS is enabled but credentials are incomplete. Configure them in Admin → SMS → Settings.');
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("Mailer is \"{$mailer}\". Set a real MAIL_MAILER in .env (smtp/ses) for live email delivery.");
        }

        $this->line('Queue workers must be running for registration notifications to send:');
        $this->line('  php artisan queue:work');
    }
}
