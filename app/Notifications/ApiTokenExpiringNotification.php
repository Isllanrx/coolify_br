<?php

namespace App\Notifications;

use App\Models\PersonalAccessToken;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;

class ApiTokenExpiringNotification extends CustomEmailNotification
{
    protected string $tokenName;

    protected string $expiresAt;

    protected string $manageUrl;

    public function __construct(public PersonalAccessToken $token)
    {
        $this->onQueue('high');
        $this->tokenName = $token->name;
        $this->expiresAt = $token->expires_at?->format('Y-m-d H:i:s') ?? '';
        $this->manageUrl = base_url().'/security/api-tokens';
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('api_token_expiring');
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject(__("Coolify: API token ':name' expires in 24 hours", ['name' => $this->tokenName]));
        $mail->view('emails.api-token-expiring', [
            'tokenName' => $this->tokenName,
            'expiresAt' => $this->expiresAt,
            'manageUrl' => $this->manageUrl,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $message = new DiscordMessage(
            title: '🔑 '.__('API token expiring soon'),
            description: __('API token **:name** expires on :date.', ['name' => $this->tokenName, 'date' => $this->expiresAt])."\n\n**".__('Action Required:').'** '.__('Rotate this token before it expires to avoid API outages.'),
            color: DiscordMessage::warningColor(),
        );

        $message->addField(__('Manage tokens'), '['.__('Open API Tokens')."]({$this->manageUrl})");

        return $message;
    }

    public function toTelegram(): array
    {
        $message = __("Coolify: API token ':name' expires on :date.", ['name' => $this->tokenName, 'date' => $this->expiresAt])."\n\n".__('Action Required:').' '.__('Rotate this token before it expires to avoid API outages.');

        return [
            'message' => $message,
            'buttons' => [
                [
                    'text' => __('Manage API tokens'),
                    'url' => $this->manageUrl,
                ],
            ],
        ];
    }

    public function toPushover(): PushoverMessage
    {
        $message = __('API token <b>:name</b> expires on :date.', ['name' => $this->tokenName, 'date' => $this->expiresAt]).'<br/><br/>';
        $message .= '<b>'.__('Action Required:').'</b> '.__('Rotate this token before it expires to avoid API outages.');

        return new PushoverMessage(
            title: __('API token expiring soon'),
            level: 'warning',
            message: $message,
            buttons: [
                [
                    'text' => __('Manage API tokens'),
                    'url' => $this->manageUrl,
                ],
            ],
        );
    }

    public function toSlack(): SlackMessage
    {
        $description = __('API token *:name* expires on :date.', ['name' => $this->tokenName, 'date' => $this->expiresAt])."\n\n";
        $description .= '*'.__('Action Required:').'* '.__('Rotate this token before it expires to avoid API outages.')."\n\n";
        $description .= __('Manage tokens:')." {$this->manageUrl}";

        return new SlackMessage(
            title: '🔑 '.__('API token expiring soon'),
            description: $description,
            color: SlackMessage::warningColor(),
        );
    }

    public function toWebhook(): array
    {
        return [
            'success' => false,
            'message' => "API token '{$this->tokenName}' expires on {$this->expiresAt}. Rotate this token before it expires to avoid API outages.",
            'event' => 'api_token_expiring',
            'token_name' => $this->tokenName,
            'expires_at' => $this->expiresAt,
            'url' => $this->manageUrl,
        ];
    }
}
