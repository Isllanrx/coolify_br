<?php

namespace App\Notifications\Server;

use App\Models\Server;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class Reachable extends CustomEmailNotification
{
    protected bool $isRateLimited = false;

    public function __construct(public Server $server)
    {
        $this->onQueue('high');
        $this->isRateLimited = isEmailRateLimited(
            limiterKey: 'server-reachable:'.$this->server->id,
        );
    }

    public function via(object $notifiable): array
    {
        if ($this->isRateLimited) {
            return [];
        }

        return $notifiable->getEnabledChannels('server_reachable');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Server (:name) revived.', ['name' => $this->server->name]));
        $mail->view('emails.server-revived', [
            'name' => $this->server->name,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        return new DiscordMessage(
            title: ':white_check_mark: '.__("Server ':name' revived", ['name' => $this->server->name]),
            description: __('All automations & integrations are turned on again!'),
            color: DiscordMessage::successColor(),
        );
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Server revived'),
            message: __("Server ':name' revived. All automations & integrations are turned on again!", ['name' => $this->server->name]),
            level: 'success',
        );
    }

    public function toTelegram(): array
    {
        return [
            'message' => __("Coolify: Server ':name' revived. All automations & integrations are turned on again!", ['name' => $this->server->name]),
        ];
    }

    public function toSlack(): SlackMessage
    {
        return new SlackMessage(
            title: __('Server revived'),
            description: __("Server ':name' revived.", ['name' => $this->server->name])."\n".__('All automations & integrations are turned on again!'),
            color: SlackMessage::successColor()
        );
    }

    public function toWebhook(): array
    {
        $url = base_url().'/server/'.$this->server->uuid;

        return [
            'success' => true,
            'message' => 'Server revived',
            'event' => 'server_reachable',
            'server_name' => $this->server->name,
            'server_uuid' => $this->server->uuid,
            'url' => $url,
        ];
    }
}
