<?php

namespace App\Notifications\Server;

use App\Models\Server;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class Unreachable extends CustomEmailNotification
{
    protected bool $isRateLimited = false;

    public function __construct(public Server $server)
    {
        $this->onQueue('high');
        $this->isRateLimited = isEmailRateLimited(
            limiterKey: 'server-unreachable:'.$this->server->id,
        );
    }

    public function via(object $notifiable): array
    {
        if ($this->isRateLimited) {
            return [];
        }

        return $notifiable->getEnabledChannels('server_unreachable');
    }

    public function toMail(): ?MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: Your server (:name) is unreachable.', ['name' => $this->server->name]));
        $mail->view('emails.server-lost-connection', [
            'name' => $this->server->name,
        ]);

        return $mail;
    }

    public function toDiscord(): ?DiscordMessage
    {
        $message = new DiscordMessage(
            title: ':cross_mark: '.__('Server unreachable'),
            description: __("Your server ':name' is unreachable.", ['name' => $this->server->name]),
            color: DiscordMessage::errorColor(),
        );

        $message->addField(__('IMPORTANT'), __('We automatically try to revive your server and turn on all automations & integrations.'));

        return $message;
    }

    public function toTelegram(): ?array
    {
        return [
            'message' => __("Coolify: Your server ':name' is unreachable. All automations & integrations are turned off! Please check your server! IMPORTANT: We automatically try to revive your server and turn on all automations & integrations.", ['name' => $this->server->name]),
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Server unreachable'),
            level: 'error',
            message: __("Your server ':name' is unreachable.", ['name' => $this->server->name]).'<br/>'.__('All automations & integrations are turned off!').'<br/><br/><b>'.__('IMPORTANT:').'</b> '.__('We automatically try to revive your server and turn on all automations & integrations.'),
        );
    }

    public function toSlack(): SlackMessage
    {
        $description = __("Your server ':name' is unreachable.", ['name' => $this->server->name])."\n";
        $description .= __('All automations & integrations are turned off!')."\n\n";
        $description .= '*'.__('IMPORTANT:').'* '.__('We automatically try to revive your server and turn on all automations & integrations.');

        return new SlackMessage(
            title: __('Server unreachable'),
            description: $description,
            color: SlackMessage::errorColor()
        );
    }

    public function toWebhook(): array
    {
        $url = base_url().'/server/'.$this->server->uuid;

        return [
            'success' => false,
            'message' => 'Server unreachable',
            'event' => 'server_unreachable',
            'server_name' => $this->server->name,
            'server_uuid' => $this->server->uuid,
            'url' => $url,
        ];
    }
}
