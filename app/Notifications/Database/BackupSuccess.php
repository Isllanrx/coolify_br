<?php

namespace App\Notifications\Database;

use App\Models\ScheduledDatabaseBackup;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BackupSuccess extends CustomEmailNotification
{
    public string $name;

    public string $frequency;

    public function __construct(ScheduledDatabaseBackup $backup, public $database, public $database_name)
    {
        $this->onQueue('high');

        $this->name = $database->name;
        $this->frequency = $backup->frequency;
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('backup_success');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Backup successfully done for :name', ['name' => $this->database->name]));
        $mail->view('emails.backup-success', [
            'name' => $this->name,
            'database_name' => $this->database_name,
            'frequency' => $this->frequency,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':white_check_mark: '.__('Database backup successful'),
            description: __('Database backup for :name (db::db) was successful.', ['name' => $this->name, 'db' => $this->database_name]),
            color: DiscordMessage::successColor(),
        );

        $message->addField(__('Frequency'), $this->frequency, true);

        return $message;
    }

    public function toTelegram(): array
    {
        $message = __('Coolify: Database backup for :name (db::db) with frequency of :frequency was successful.', ['name' => $this->name, 'db' => $this->database_name, 'frequency' => $this->frequency]);

        return [
            'message' => $message,
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Database backup successful'),
            level: 'success',
            message: __('Database backup for :name (db::db) was successful.', ['name' => $this->name, 'db' => $this->database_name]).'<br/><br/><b>'.__('Frequency:').'</b> '.$this->frequency.'.',
        );
    }

    public function toSlack(): SlackMessage
    {
        $title = __('Database backup successful');
        $description = __('Database backup for :name (db::db) was successful.', ['name' => $this->name, 'db' => $this->database_name]);

        $description .= "\n\n*".__('Frequency:').'* '.$this->frequency;

        return new SlackMessage(
            title: $title,
            description: $description,
            color: SlackMessage::successColor()
        );
    }

    public function toWebhook(): array
    {
        $url = base_url().'/project/'.data_get($this->database, 'environment.project.uuid').'/environment/'.data_get($this->database, 'environment.uuid').'/database/'.$this->database->uuid;

        return [
            'success' => true,
            'message' => 'Database backup successful',
            'event' => 'backup_success',
            'database_name' => $this->name,
            'database_uuid' => $this->database->uuid,
            'database_type' => $this->database_name,
            'frequency' => $this->frequency,
            'url' => $url,
        ];
    }
}
