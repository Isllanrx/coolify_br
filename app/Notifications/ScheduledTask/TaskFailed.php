<?php

namespace App\Notifications\ScheduledTask;

use App\Models\Application;
use App\Models\ScheduledTask;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class TaskFailed extends CustomEmailNotification
{
    public ?string $url = null;

    public function __construct(public ScheduledTask $task, public string $output)
    {
        $this->onQueue('high');
        $resource = $task->application ?? $task->service;
        if ($resource) {
            $type = $resource instanceof Application ? 'application' : 'service';
            $this->url = base_url().'/project/'.data_get($resource, 'environment.project.uuid').'/environment/'.data_get($resource, 'environment.uuid')."/{$type}/{$resource->uuid}/tasks/{$task->uuid}";
        }
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('scheduled_task_failure');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: [ACTION REQUIRED] Scheduled task (:name) failed.', ['name' => $this->task->name]));
        $mail->view('emails.scheduled-task-failed', [
            'task' => $this->task,
            'url' => $this->url,
            'output' => $this->output,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':cross_mark: '.__('Scheduled task failed'),
            description: __('Scheduled task (:name) failed.', ['name' => $this->task->name]),
            color: DiscordMessage::errorColor(),
        );

        if ($this->url) {
            $message->addField(__('Scheduled task'), '['.__('Link').']('.$this->url.')');
        }

        return $message;
    }

    public function toTelegram(): array
    {
        $message = __('Coolify: Scheduled task (:name) failed with output: :output', ['name' => $this->task->name, 'output' => $this->output]);
        if ($this->url) {
            $buttons[] = [
                'text' => __('Open task in Coolify'),
                'url' => (string) $this->url,
            ];
        }

        return [
            'message' => $message,
        ];
    }

    public function toPushover(): PushoverMessage
    {
        $message = __('Scheduled task (:name) failed', ['name' => $this->task->name]).'<br/>';

        if ($this->output) {
            $message .= '<br/><b>'.__('Error Output:').'</b>'.$this->output;
        }

        $buttons = [];
        if ($this->url) {
            $buttons[] = [
                'text' => __('Open task in Coolify'),
                'url' => (string) $this->url,
            ];
        }

        return new PushoverMessage(
            title: __('Scheduled task failed'),
            level: 'error',
            message: $message,
            buttons: $buttons,
        );
    }

    public function toSlack(): SlackMessage
    {
        $title = __('Scheduled task failed');
        $description = __('Scheduled task (:name) failed.', ['name' => $this->task->name]);

        if ($this->output) {
            $description .= "\n\n*".__('Error Output:').'* '.$this->output;
        }

        if ($this->url) {
            $description .= "\n\n*".__('Task URL:').'* '.$this->url;
        }

        return new SlackMessage(
            title: $title,
            description: $description,
            color: SlackMessage::errorColor()
        );
    }

    public function toWebhook(): array
    {
        $data = [
            'success' => false,
            'message' => 'Scheduled task failed',
            'event' => 'task_failed',
            'task_name' => $this->task->name,
            'task_uuid' => $this->task->uuid,
            'output' => $this->output,
        ];

        if ($this->task->application) {
            $data['application_uuid'] = $this->task->application->uuid;
        } elseif ($this->task->service) {
            $data['service_uuid'] = $this->task->service->uuid;
        }

        if ($this->url) {
            $data['url'] = $this->url;
        }

        return $data;
    }
}
