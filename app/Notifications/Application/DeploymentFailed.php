<?php

namespace App\Notifications\Application;

use App\Models\Application;
use App\Models\ApplicationDeploymentQueue;
use App\Models\ApplicationPreview;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use App\Traits\IncludesDeploymentCommitDetails;
use Illuminate\Notifications\Messages\MailMessage;

class DeploymentFailed extends CustomEmailNotification
{
    use IncludesDeploymentCommitDetails;

    public Application $application;

    public ?ApplicationPreview $preview = null;

    public string $deployment_uuid;

    public string $application_name;

    public string $project_uuid;

    public string $environment_uuid;

    public string $environment_name;

    public ?string $deployment_url = null;

    public ?string $fqdn = null;

    public function __construct(Application $application, string $deployment_uuid, ?ApplicationPreview $preview = null, ?ApplicationDeploymentQueue $deployment = null)
    {
        $this->onQueue('high');
        $this->application = $application;
        $this->deployment_uuid = $deployment_uuid;
        $this->preview = $preview;
        $this->application_name = data_get($application, 'name');
        $this->project_uuid = data_get($application, 'environment.project.uuid');
        $this->environment_uuid = data_get($application, 'environment.uuid');
        $this->environment_name = data_get($application, 'environment.name');
        $this->fqdn = data_get($application, 'fqdn');
        if (str($this->fqdn)->explode(',')->count() > 1) {
            $this->fqdn = str($this->fqdn)->explode(',')->first();
        }
        $this->deployment_url = base_url()."/project/{$this->project_uuid}/environment/{$this->environment_uuid}/application/{$this->application->uuid}/deployment/{$this->deployment_uuid}";
        $this->resolveDeploymentCommitDetails($application, $deployment, $preview);
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('deployment_failure');
    }

    public function toMail(?object $notifiable = null): MailMessage
    {
        $mail = new MailMessage;
        $pull_request_id = data_get($this->preview, 'pull_request_id', 0);
        $fqdn = $this->fqdn;
        if ($pull_request_id === 0) {
            $mail->subject(__('Coolify: Deployment failed of :name.', ['name' => $this->application_name]));
        } else {
            $fqdn = $this->preview->fqdn;
            $mail->subject(__('Coolify: Deployment failed of pull request #:id of :name.', ['id' => $this->preview->pull_request_id, 'name' => $this->application_name]));
        }
        $mail->view('emails.application-deployment-failed', [
            'commit' => $this->deploymentCommitDetailsForMail($notifiable),
            'name' => $this->application_name,
            'fqdn' => $fqdn,
            'deployment_url' => $this->deployment_url,
            'pull_request_id' => data_get($this->preview, 'pull_request_id', 0),
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        if ($this->preview) {
            $message = new DiscordMessage(
                title: ':cross_mark: '.__('Deployment failed'),
                description: __('Pull request: :id', ['id' => $this->preview->pull_request_id]),
                color: DiscordMessage::errorColor(),
                isCritical: true,
            );

            $message->addField(__('Project'), data_get($this->application, 'environment.project.name'), true);
            $message->addField(__('Environment'), $this->environment_name, true);
            $message->addField(__('Name'), $this->application_name, true);

            $message->addField(__('Deployment Logs'), '['.__('Link').']('.$this->deployment_url.')');
            if ($this->fqdn) {
                $message->addField(__('Domain'), $this->fqdn, true);
            }
        } else {
            if ($this->fqdn) {
                $description = '['.__('Open application').']('.$this->fqdn.')';
            } else {
                $description = '';
            }
            $message = new DiscordMessage(
                title: ':cross_mark: '.__('Deployment failed'),
                description: $description,
                color: DiscordMessage::errorColor(),
                isCritical: true,
            );

            $message->addField(__('Project'), data_get($this->application, 'environment.project.name'), true);
            $message->addField(__('Environment'), $this->environment_name, true);
            $message->addField(__('Name'), $this->application_name, true);

            $message->addField(__('Deployment Logs'), '['.__('Link').']('.$this->deployment_url.')');
        }

        return $message;
    }

    public function toTelegram(): array
    {
        if ($this->preview) {
            $message = __('Coolify: Pull request #:id of :name (:fqdn) deployment failed: ', ['id' => $this->preview->pull_request_id, 'name' => $this->application_name, 'fqdn' => $this->preview->fqdn]);
        } else {
            $message = __('Coolify: Deployment failed of :name (:fqdn): ', ['name' => $this->application_name, 'fqdn' => $this->fqdn]);
        }
        $buttons[] = [
            'text' => __('Deployment logs'),
            'url' => $this->deployment_url,
        ];

        return [
            'message' => $message,
            'buttons' => [
                ...$buttons,
            ],
        ];
    }

    public function toPushover(): PushoverMessage
    {
        if ($this->preview) {
            $title = __('Pull request #:id deployment failed', ['id' => $this->preview->pull_request_id]);
            $message = __('Pull request deployment failed for :name', ['name' => $this->application_name]);
        } else {
            $title = __('Deployment failed');
            $message = __('Deployment failed for :name', ['name' => $this->application_name]);
        }

        $buttons[] = [
            'text' => __('Deployment logs'),
            'url' => $this->deployment_url,
        ];

        return new PushoverMessage(
            title: $title,
            level: 'error',
            message: $message,
            buttons: [
                ...$buttons,
            ],
        );
    }

    public function toSlack(): SlackMessage
    {
        if ($this->preview) {
            $title = __('Pull request #:id deployment failed', ['id' => $this->preview->pull_request_id]);
            $description = __('Pull request deployment failed for :name', ['name' => $this->application_name]);
            if ($this->preview->fqdn) {
                $description .= "\n".__('Preview URL: :url', ['url' => $this->preview->fqdn]);
            }
        } else {
            $title = __('Deployment failed');
            $description = __('Deployment failed for :name', ['name' => $this->application_name]);
            if ($this->fqdn) {
                $description .= "\n".__('Application URL: :url', ['url' => $this->fqdn]);
            }
        }

        $description .= "\n\n*".__('Project:').'* '.data_get($this->application, 'environment.project.name');
        $description .= "\n*".__('Environment:').'* '.$this->environment_name;
        $description .= "\n*<{$this->deployment_url}|".__('Deployment Logs').'>*';

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
            'message' => 'Deployment failed',
            'event' => 'deployment_failed',
            'application_name' => $this->application_name,
            'application_uuid' => $this->application->uuid,
            'deployment_uuid' => $this->deployment_uuid,
            'deployment_url' => $this->deployment_url,
            'project' => data_get($this->application, 'environment.project.name'),
            'environment' => $this->environment_name,
        ];

        if ($this->preview) {
            $data['pull_request_id'] = $this->preview->pull_request_id;
            $data['preview_fqdn'] = $this->preview->fqdn;
        }

        if ($this->fqdn) {
            $data['fqdn'] = $this->fqdn;
        }

        return $data;
    }
}
