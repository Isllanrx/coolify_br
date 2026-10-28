<?php

namespace App\Notifications\Server;

use App\Contracts\ThrottledNotification;
use App\Models\Server;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class HighDiskUsage extends CustomEmailNotification implements ThrottledNotification
{
    /**
     * Percentage points below the threshold that disk usage must reach before a new spike alerts
     * again, so usage that moves around the threshold does not send an alert on every change.
     */
    public const RECOVERY_MARGIN = 5;

    public static function hasRecovered(int|float $diskUsage, int|float $threshold): bool
    {
        return $diskUsage <= $threshold - self::RECOVERY_MARGIN;
    }

    public function __construct(public Server $server, public int $disk_usage, public int $server_disk_usage_notification_threshold)
    {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('server_disk_usage');
    }

    public function throttleSubject(): ?Model
    {
        return $this->server;
    }

    public function throttleIntervalMinutes(): int
    {
        return max(1, (int) data_get($this->server, 'settings.server_disk_usage_notification_interval_hours', 24)) * 60;
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Server (:name) high disk usage detected!', ['name' => $this->server->name]));
        $mail->view('emails.high-disk-usage', [
            'name' => $this->server->name,
            'disk_usage' => $this->disk_usage,
            'threshold' => $this->server_disk_usage_notification_threshold,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':cross_mark: '.__('High disk usage detected'),
            description: __("Server ':name' high disk usage detected!", ['name' => $this->server->name]),
            color: DiscordMessage::errorColor(),
            isCritical: true,
        );

        $message->addField(__('Disk usage'), "{$this->disk_usage}%", true);
        $message->addField(__('Threshold'), "{$this->server_disk_usage_notification_threshold}%", true);
        $message->addField(__('What to do?'), '['.__('Link').'](https://coolify.io/docs/knowledge-base/server/automated-cleanup)', true);
        $message->addField(__('Change Settings'), '['.__('Threshold').']('.base_url().'/server/'.$this->server->uuid.'#advanced) | ['.__('Notification').']('.base_url().'/notifications/discord)');

        return $message;
    }

    public function toTelegram(): array
    {
        return [
            'message' => __("Coolify: Server ':name' high disk usage detected!", ['name' => $this->server->name])."\n".__('Disk usage: :usage%. Threshold: :threshold%.', ['usage' => $this->disk_usage, 'threshold' => $this->server_disk_usage_notification_threshold])."\n".__('Please cleanup your disk to prevent data-loss.')."\n".__('Here are some tips:').' https://coolify.io/docs/knowledge-base/server/automated-cleanup.',
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('High disk usage detected'),
            level: 'warning',
            message: __("Server ':name' high disk usage detected!", ['name' => $this->server->name]).'<br/><br/><b>'.__('Disk usage:').'</b> '.$this->disk_usage.'%.<br/><b>'.__('Threshold:').'</b> '.$this->server_disk_usage_notification_threshold.'%.<br/>'.__('Please cleanup your disk to prevent data-loss.'),
            buttons: [
                'Change settings' => base_url().'/server/'.$this->server->uuid.'#advanced',
                'Tips for cleanup' => 'https://coolify.io/docs/knowledge-base/server/automated-cleanup',
            ],
        );
    }

    public function toSlack(): SlackMessage
    {
        $description = __("Server ':name' high disk usage detected!", ['name' => $this->server->name])."\n";
        $description .= __('Disk usage:')." {$this->disk_usage}%\n";
        $description .= __('Threshold:')." {$this->server_disk_usage_notification_threshold}%\n\n";
        $description .= __('Please cleanup your disk to prevent data-loss.')."\n";
        $description .= __('Tips for cleanup:')." https://coolify.io/docs/knowledge-base/server/automated-cleanup\n";
        $description .= __('Change settings:')."\n";
        $description .= '- '.__('Threshold:').' '.base_url().'/server/'.$this->server->uuid."#advanced\n";
        $description .= '- '.__('Notifications:').' '.base_url().'/notifications/slack';

        return new SlackMessage(
            title: __('High disk usage detected'),
            description: $description,
            color: SlackMessage::errorColor()
        );
    }

    public function toWebhook(): array
    {
        return [
            'success' => false,
            'message' => 'High disk usage detected',
            'event' => 'high_disk_usage',
            'server_name' => $this->server->name,
            'server_uuid' => $this->server->uuid,
            'disk_usage' => $this->disk_usage,
            'threshold' => $this->server_disk_usage_notification_threshold,
            'url' => base_url().'/server/'.$this->server->uuid,
        ];
    }
}
