<?php

namespace App\Notifications\Server;

use App\Models\Server;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class ForceEnabled extends CustomEmailNotification
{
    public function __construct(public Server $server)
    {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('server_force_enabled');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Server (:name) enabled again!', ['name' => $this->server->name]));
        $mail->view('emails.server-force-enabled', [
            'name' => $this->server->name,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        return new DiscordMessage(
            title: ':white_check_mark: '.__('Server enabled'),
            description: __("Server ':name' enabled again!", ['name' => $this->server->name]),
            color: DiscordMessage::successColor(),
        );
    }

    public function toTelegram(): array
    {
        return [
            'message' => __('Coolify: Server (:name) enabled again!', ['name' => $this->server->name]),
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Server enabled'),
            level: 'success',
            message: __('Server (:name) enabled again!', ['name' => $this->server->name]),
        );
    }

    public function toSlack(): SlackMessage
    {
        return new SlackMessage(
            title: __('Server enabled'),
            description: __("Server ':name' enabled again!", ['name' => $this->server->name]),
            color: SlackMessage::successColor()
        );
    }

    public function toWebhook(): array
    {
        return [
            'success' => true,
            'message' => "Server ({$this->server->name}) enabled again!",
            'event' => 'server_force_enabled',
            'server_name' => $this->server->name,
            'server_uuid' => $this->server->uuid,
            'url' => base_url().'/server/'.$this->server->uuid,
        ];
    }
}
