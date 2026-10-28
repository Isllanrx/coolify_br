<?php

namespace App\Notifications\Server;

use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class HetznerDeletionFailed extends CustomEmailNotification
{
    public function __construct(public int $hetznerServerId, public int $teamId, public string $errorMessage)
    {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('hetzner_deletion_failure');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__('Coolify: [ACTION REQUIRED] Failed to delete Hetzner server #:id', ['id' => $this->hetznerServerId]));
        $mail->view('emails.hetzner-deletion-failed', [
            'hetznerServerId' => $this->hetznerServerId,
            'errorMessage' => $this->errorMessage,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        return new DiscordMessage(
            title: ':cross_mark: '.__('Coolify: [ACTION REQUIRED] Failed to delete Hetzner server'),
            description: __('Failed to delete Hetzner server #:id from Hetzner Cloud.', ['id' => $this->hetznerServerId])."\n\n**".__('Error:').'** '.$this->errorMessage."\n\n".
                __('The server has been removed from Coolify, but may still exist in your Hetzner Cloud account. Please check your Hetzner Cloud console and manually delete the server if needed.'),
            color: DiscordMessage::errorColor(),
        );
    }

    public function toTelegram(): array
    {
        return [
            'message' => __('Coolify: [ACTION REQUIRED] Failed to delete Hetzner server #:id from Hetzner Cloud.', ['id' => $this->hetznerServerId])."\n\n".__('Error:').' '.$this->errorMessage."\n\n".
                __('The server has been removed from Coolify, but may still exist in your Hetzner Cloud account. Please check your Hetzner Cloud console and manually delete the server if needed.'),
        ];
    }

    public function toPushover(): PushoverMessage
    {
        return new PushoverMessage(
            title: __('Hetzner Server Deletion Failed'),
            level: 'error',
            message: __('[ACTION REQUIRED] Failed to delete Hetzner server #:id.', ['id' => $this->hetznerServerId])."\n\n".__('Error:').' '.$this->errorMessage."\n\n".
            __('The server has been removed from Coolify, but may still exist in your Hetzner Cloud account. Please check and manually delete if needed.'),
        );
    }

    public function toSlack(): SlackMessage
    {
        return new SlackMessage(
            title: __('Coolify: [ACTION REQUIRED] Hetzner Server Deletion Failed'),
            description: __('Failed to delete Hetzner server #:id from Hetzner Cloud.', ['id' => $this->hetznerServerId])."\n\n".__('Error:').' '.$this->errorMessage."\n\n".
            __('The server has been removed from Coolify, but may still exist in your Hetzner Cloud account. Please check your Hetzner Cloud console and manually delete the server if needed.'),
            color: SlackMessage::errorColor()
        );
    }

    public function toWebhook(): array
    {
        return [
            'success' => false,
            'message' => "[ACTION REQUIRED] Failed to delete Hetzner server #{$this->hetznerServerId} from Hetzner Cloud. The server has been removed from Coolify, but may still exist in your Hetzner Cloud account.",
            'event' => 'hetzner_deletion_failed',
            'hetzner_server_id' => $this->hetznerServerId,
            'error' => $this->errorMessage,
            'url' => base_url().'/servers',
        ];
    }
}
