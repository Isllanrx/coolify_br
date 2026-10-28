<?php

namespace App\Notifications\Database;

use App\Models\ScheduledDatabaseBackup;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BackupSuccessWithS3Warning extends CustomEmailNotification
{
    public string $name;

    public string $frequency;

    public ?string $s3_storage_url = null;

    public function __construct(ScheduledDatabaseBackup $backup, public $database, public $database_name, public $s3_error)
    {
        $this->onQueue('high');

        $this->name = $database->name;
        $this->frequency = $backup->frequency;

        if ($backup->s3) {
            $this->s3_storage_url = base_url().'/storages/'.$backup->s3->uuid;
        }
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('backup_failure');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Backup succeeded locally but S3 upload failed for :name', ['name' => $this->database->name]));
        $mail->view('emails.backup-success-with-s3-warning', [
            'name' => $this->name,
            'database_name' => $this->database_name,
            'frequency' => $this->frequency,
            's3_error' => $this->s3_error,
            's3_storage_url' => $this->s3_storage_url,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':warning: '.__('Database backup succeeded locally, S3 upload failed'),
            description: __('Database backup for :name (db::db) was created successfully on local storage, but failed to upload to S3.', ['name' => $this->name, 'db' => $this->database_name]),
            color: DiscordMessage::warningColor(),
        );

        $message->addField(__('Frequency'), $this->frequency, true);
        $message->addField(__('S3 Error'), $this->s3_error);

        if ($this->s3_storage_url) {
            $message->addField(__('S3 Storage'), '['.__('Check Configuration').']('.$this->s3_storage_url.')');
        }

        return $message;
    }

    public function toTelegram(): array
    {
        $message = __('Coolify: Database backup for :name (db::db) with frequency of :frequency succeeded locally but failed to upload to S3.', ['name' => $this->name, 'db' => $this->database_name, 'frequency' => $this->frequency])."\n\n".__('S3 Error:')."\n{$this->s3_error}";

        if ($this->s3_storage_url) {
            $message .= "\n\n".__('Check S3 Configuration:')." {$this->s3_storage_url}";
        }

        return [
            'message' => $message,
        ];
    }

    public function toPushover(): PushoverMessage
    {
        $message = __('Database backup for :name (db::db) was created successfully on local storage, but failed to upload to S3.', ['name' => $this->name, 'db' => $this->database_name]).'<br/><br/><b>'.__('Frequency:').'</b> '.$this->frequency.'.<br/><b>'.__('S3 Error:').'</b> '.$this->s3_error;

        if ($this->s3_storage_url) {
            $message .= "<br/><br/><a href=\"{$this->s3_storage_url}\">".__('Check S3 Configuration').'</a>';
        }

        return new PushoverMessage(
            title: __('Database backup succeeded locally, S3 upload failed'),
            level: 'warning',
            message: $message,
        );
    }

    public function toSlack(): SlackMessage
    {
        $title = __('Database backup succeeded locally, S3 upload failed');
        $description = __('Database backup for :name (db::db) was created successfully on local storage, but failed to upload to S3.', ['name' => $this->name, 'db' => $this->database_name]);

        $description .= "\n\n*".__('Frequency:').'* '.$this->frequency;
        $description .= "\n\n*".__('S3 Error:').'* '.$this->s3_error;

        if ($this->s3_storage_url) {
            $description .= "\n\n*".__('S3 Storage:')."* <{$this->s3_storage_url}|".__('Check Configuration').'>';
        }

        return new SlackMessage(
            title: $title,
            description: $description,
            color: SlackMessage::warningColor()
        );
    }

    public function toWebhook(): array
    {
        $url = base_url().'/project/'.data_get($this->database, 'environment.project.uuid').'/environment/'.data_get($this->database, 'environment.uuid').'/database/'.$this->database->uuid;

        $data = [
            'success' => true,
            'message' => 'Database backup succeeded locally, S3 upload failed',
            'event' => 'backup_success_with_s3_warning',
            'database_name' => $this->name,
            'database_uuid' => $this->database->uuid,
            'database_type' => $this->database_name,
            'frequency' => $this->frequency,
            's3_error' => $this->s3_error,
            'url' => $url,
        ];

        if ($this->s3_storage_url) {
            $data['s3_storage_url'] = $this->s3_storage_url;
        }

        return $data;
    }
}
