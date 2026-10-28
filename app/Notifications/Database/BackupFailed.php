<?php

namespace App\Notifications\Database;

use App\Models\ScheduledDatabaseBackup;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BackupFailed extends CustomEmailNotification
{
    public string $name;

    public string $frequency;

    public function __construct(ScheduledDatabaseBackup $backup, public $database, public $output, public $database_name)
    {
        $this->onQueue('high');
        $this->name = $database->name;
        $this->frequency = $backup->frequency;
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('backup_failure');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: [ACTION REQUIRED] Database Backup FAILED for :name', ['name' => $this->database->name]));
        $mail->view('emails.backup-failed', [
            'name' => $this->name,
            'database_name' => $this->database_name,
            'frequency' => $this->frequency,
            'output' => $this->output,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':cross_mark: '.__('Database backup failed'),
            description: __('Database backup for :name (db::db) has FAILED.', ['name' => $this->name, 'db' => $this->database_name]),
            color: DiscordMessage::errorColor(),
            isCritical: true,
        );

        $message->addField(__('Frequency'), $this->frequency, true);
        $message->addField(__('Output'), $this->output);

        return $message;
    }

    public function toTelegram(): array
    {
        $message = __('Coolify: Database backup for :name (db::db) with frequency of :frequency was FAILED.', ['name' => $this->name, 'db' => $this->database_name, 'frequency' => $this->frequency])."\n\n".__('Reason:')."\n{$this->output}";

        return [
            'message' => $message,
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Database backup failed'),
            level: 'error',
            message: __('Database backup for :name (db::db) was FAILED', ['name' => $this->name, 'db' => $this->database_name]).'<br/><br/><b>'.__('Frequency:').'</b> '.$this->frequency.' .<br/><b>'.__('Reason:').'</b> '.$this->output,
        );
    }

    public function toSlack(): SlackMessage
    {
        $title = __('Database backup failed');
        $description = __('Database backup for :name (db::db) has FAILED.', ['name' => $this->name, 'db' => $this->database_name]);

        $description .= "\n\n*".__('Frequency:').'* '.$this->frequency;
        $description .= "\n\n*".__('Error Output:').'* '.$this->output;

        return new SlackMessage(
            title: $title,
            description: $description,
            color: SlackMessage::errorColor()
        );
    }

    public function toWebhook(): array
    {
        $url = base_url().'/project/'.data_get($this->database, 'environment.project.uuid').'/environment/'.data_get($this->database, 'environment.uuid').'/database/'.$this->database->uuid;

        return [
            'success' => false,
            'message' => 'Database backup failed',
            'event' => 'backup_failed',
            'database_name' => $this->name,
            'database_uuid' => $this->database->uuid,
            'database_type' => $this->database_name,
            'frequency' => $this->frequency,
            'error_output' => $this->output,
            'url' => $url,
        ];
    }
}
