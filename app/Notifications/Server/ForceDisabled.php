<?php

namespace App\Notifications\Server;

use App\Models\Server;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class ForceDisabled extends CustomEmailNotification
{
    public function __construct(public Server $server)
    {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('server_force_disabled');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Server (:name) disabled because it is not paid!', ['name' => $this->server->name]));
        $mail->view('emails.server-force-disabled', [
            'name' => $this->server->name,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':cross_mark: '.__('Server disabled'),
            description: __('Server (:name) disabled because it is not paid!', ['name' => $this->server->name]),
            color: DiscordMessage::errorColor(),
        );

        $message->addField(__('Please update your subscription to enable the server again!'), '['.__('Link').'](https://app.coolify.io/subscription)');

        return $message;
    }

    public function toTelegram(): array
    {
        return [
            'message' => __('Coolify: Server (:name) disabled because it is not paid!', ['name' => $this->server->name])."\n ".__('All automations and integrations are stopped.')."\n".__('Please update your subscription to enable the server again').' ['.__('here').'](https://app.coolify.io/subscription).',
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Server disabled'),
            level: 'error',
            message: __('Server (:name) disabled because it is not paid!', ['name' => $this->server->name])."\n ".__('All automations and integrations are stopped.').'<br/>'.__('Please update your subscription to enable the server again').' ['.__('here').'](https://app.coolify.io/subscription).',
        );
    }

    public function toSlack(): SlackMessage
    {
        $title = __('Server disabled');
        $description = __('Server (:name) disabled because it is not paid!', ['name' => $this->server->name])."\n";
        $description .= __('All automations and integrations are stopped.')."\n\n";
        $description .= __('Please update your subscription to enable the server again:').' https://app.coolify.io/subscription';

        return new SlackMessage(
            title: $title,
            description: $description,
            color: SlackMessage::errorColor()
        );
    }

    public function toWebhook(): array
    {
        return [
            'success' => false,
            'message' => "Server ({$this->server->name}) disabled because it is not paid! All automations and integrations are stopped.",
            'event' => 'server_force_disabled',
            'server_name' => $this->server->name,
            'server_uuid' => $this->server->uuid,
            'url' => base_url().'/server/'.$this->server->uuid,
        ];
    }
}
