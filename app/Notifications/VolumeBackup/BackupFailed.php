<?php

namespace App\Notifications\VolumeBackup;

use App\Models\ScheduledVolumeBackup;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BackupFailed extends CustomEmailNotification
{
    public string $resourceName;

    public string $target;

    public string $frequency;

    public ?string $url = null;

    public function __construct(ScheduledVolumeBackup $backup, public string $output)
    {
        $this->onQueue('high');
        $resource = $backup->targetResource();
        $this->resourceName = $resource?->name ?? 'Unknown resource';
        $this->target = $backup->targetType().' '.$backup->targetName();
        $this->frequency = $backup->frequency;

        $this->url = $backup->resourceUrl();
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('backup_failure');
    }

    public function summary(): string
    {
        return __('Storage backup of :target (:resource) has FAILED.', ['target' => $this->target, 'resource' => $this->resourceName]);
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: [ACTION REQUIRED] Storage backup FAILED for :name', ['name' => $this->resourceName]));
        $mail->view('emails.volume-backup-result', [
            'summary' => $this->summary(),
            'frequency' => $this->frequency,
            'output' => $this->output,
            'url' => $this->url,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':cross_mark: '.__('Storage backup failed'),
            description: $this->summary(),
            color: DiscordMessage::errorColor(),
            isCritical: true,
        );

        $message->addField(__('Frequency'), $this->frequency, true);
        $message->addField(__('Output'), $this->output);
        if ($this->url) {
            $message->addField(__('Resource'), '['.__('Link').']('.$this->url.')');
        }

        return $message;
    }

    public function toTelegram(): array
    {
        $data = ['message' => __('Coolify: :summary', ['summary' => $this->summary()])."\n\n".__('Frequency:')." {$this->frequency}\n\n".__('Reason:')."\n{$this->output}"];

        if ($this->url) {
            $data['buttons'] = [['text' => __('Open resource in Coolify'), 'url' => $this->url]];
        }

        return $data;
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Storage backup failed'),
            level: 'error',
            message: $this->summary().'<br/><br/><b>'.__('Frequency:').'</b> '.$this->frequency.'.<br/><b>'.__('Reason:').'</b> '.$this->output,
            buttons: $this->url ? [['text' => __('Open resource in Coolify'), 'url' => $this->url]] : [],
        );
    }

    public function toSlack(): SlackMessage
    {
        return new SlackMessage(
            title: __('Storage backup failed'),
            description: $this->summary()."\n\n*".__('Frequency:')."* {$this->frequency}\n\n*".__('Error Output:')."* {$this->output}".($this->url ? "\n\n<{$this->url}|".__('Open resource in Coolify').'>' : ''),
            color: SlackMessage::errorColor(),
        );
    }

    public function toWebhook(): array
    {
        return [
            'success' => false,
            'message' => 'Storage backup failed',
            'event' => 'volume_backup_failed',
            'resource_name' => $this->resourceName,
            'storage' => $this->target,
            'frequency' => $this->frequency,
            'error_output' => $this->output,
            'url' => $this->url,
        ];
    }
}
