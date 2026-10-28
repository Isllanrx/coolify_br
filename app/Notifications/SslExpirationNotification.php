<?php

namespace App\Notifications;

use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class SslExpirationNotification extends CustomEmailNotification
{
    protected Collection $resources;

    protected array $urls = [];

    public function __construct(array|Collection $resources)
    {
        $this->onQueue('high');
        $this->resources = collect($resources);
        $this->urls = $this->resources->mapWithKeys(fn ($resource) => [
            $resource->name => base_url().'/project/'.data_get($resource, 'environment.project.uuid').'/environment/'.data_get($resource, 'environment.uuid')."/database/{$resource->uuid}",
        ])->all();
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('ssl_certificate_renewal');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: [Action Required] SSL Certificates Renewed - Manual Redeployment Needed'));
        $mail->view('emails.ssl-certificate-renewed', [
            'resources' => $this->resources,
            'urls' => $this->urls,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $resourceNames = $this->resources->pluck('name')->join(', ');

        $message = new DiscordMessage(
            title: '🔒 '.__('SSL Certificates Renewed'),
            description: __('SSL certificates have been renewed for: :names.', ['names' => $resourceNames])."\n\n**".__('Action Required:').'** '.__('These resources need to be redeployed manually.'),
            color: DiscordMessage::warningColor(),
        );

        foreach ($this->urls as $name => $url) {
            $message->addField($name, '['.__('View Resource')."]({$url})");
        }

        return $message;
    }

    public function toTelegram(): array
    {
        $resourceNames = $this->resources->pluck('name')->join(', ');
        $message = __('Coolify: SSL certificates have been renewed for: :names.', ['names' => $resourceNames])."\n\n".__('Action Required:').' '.__('These resources need to be redeployed manually for the new SSL certificates to take effect.');

        $buttons = [];
        foreach ($this->urls as $name => $url) {
            $buttons[] = [
                'text' => __('View :name', ['name' => $name]),
                'url' => $url,
            ];
        }

        return [
            'message' => $message,
            'buttons' => $buttons,
        ];
    }

    public function toPushover(): PushoverMessage
    {
        $resourceNames = $this->resources->pluck('name')->join(', ');
        $message = __('SSL certificates have been renewed for: :names', ['names' => $resourceNames]).'<br/><br/>';
        $message .= '<b>'.__('Action Required:').'</b> '.__('These resources need to be redeployed manually for the new SSL certificates to take effect.');

        $buttons = [];
        foreach ($this->urls as $name => $url) {
            $buttons[] = [
                'text' => __('View :name', ['name' => $name]),
                'url' => $url,
            ];
        }

        return new PushoverMessage(
            title: __('SSL Certificates Renewed'),
            level: 'warning',
            message: $message,
            buttons: $buttons,
        );
    }

    public function toSlack(): SlackMessage
    {
        $resourceNames = $this->resources->pluck('name')->join(', ');
        $description = __('SSL certificates have been renewed for: :names', ['names' => $resourceNames])."\n\n";
        $description .= '**'.__('Action Required:').'** '.__('These resources need to be redeployed manually for the new SSL certificates to take effect.');

        if (! empty($this->urls)) {
            $description .= "\n\n**".__('Resource URLs:')."**\n";
            foreach ($this->urls as $name => $url) {
                $description .= "• {$name}: {$url}\n";
            }
        }

        return new SlackMessage(
            title: '🔒 '.__('SSL Certificates Renewed'),
            description: $description,
            color: SlackMessage::warningColor()
        );
    }

    public function toWebhook(): array
    {
        $resourceNames = $this->resources->pluck('name');

        return [
            'success' => false,
            'message' => "SSL certificates have been renewed for: {$resourceNames->join(', ')}. These resources need to be redeployed manually for the new SSL certificates to take effect.",
            'event' => 'ssl_certificate_renewal',
            'resources' => $resourceNames->values()->all(),
            'urls' => $this->urls,
            'url' => base_url(),
        ];
    }
}
