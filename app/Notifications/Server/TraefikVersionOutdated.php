<?php

namespace App\Notifications\Server;

use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class TraefikVersionOutdated extends CustomEmailNotification
{
    public function __construct(public Collection $servers)
    {
        $this->onQueue('high');
    }

    public function via(object $notifiable): array
    {
        return $notifiable->getEnabledChannels('traefik_outdated');
    }

    private function formatVersion(string $version): string
    {
        // Add 'v' prefix if not present for consistent display
        return str_starts_with($version, 'v') ? $version : "v{$version}";
    }

    private function getUpgradeTarget(array $info): string
    {
        // For minor upgrades, use the upgrade_target field (e.g., "v3.6")
        if (($info['type'] ?? 'patch_update') === 'minor_upgrade' && isset($info['upgrade_target'])) {
            return $this->formatVersion($info['upgrade_target']);
        }

        // For patch updates, show the full version
        return $this->formatVersion($info['latest'] ?? 'unknown');
    }

    public function toMail($notifiable = null): MailMessage
    {
        $mail = new MailMessage;
        $count = $this->servers->count();

        // Transform servers to include URLs
        $serversWithUrls = $this->servers->map(function ($server) {
            return [
                'name' => $server->name,
                'uuid' => $server->uuid,
                'url' => base_url().'/server/'.$server->uuid.'/proxy',
                'outdatedInfo' => $server->outdatedInfo ?? [],
            ];
        });

        $mail->subject(__('Coolify: Traefik proxy outdated on :count server(s)', ['count' => $count]));
        $mail->view('emails.traefik-version-outdated', [
            'servers' => $serversWithUrls,
            'count' => $count,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        $count = $this->servers->count();
        $hasUpgrades = $this->servers->contains(fn ($s) => ($s->outdatedInfo['type'] ?? 'patch_update') === 'minor_upgrade' ||
            isset($s->outdatedInfo['newer_branch_target'])
        );

        $description = '**'.__(':count server(s)', ['count' => $count]).'** '.__('running outdated Traefik proxy. Update recommended for security and features.')."\n\n";
        $description .= '**'.__('Affected servers:')."**\n";

        foreach ($this->servers as $server) {
            $info = $server->outdatedInfo ?? [];
            $current = $this->formatVersion($info['current'] ?? 'unknown');
            $latest = $this->formatVersion($info['latest'] ?? 'unknown');
            $upgradeTarget = $this->getUpgradeTarget($info);
            $isPatch = ($info['type'] ?? 'patch_update') === 'patch_update';
            $hasNewerBranch = isset($info['newer_branch_target']);

            if ($isPatch && $hasNewerBranch) {
                $newerBranchTarget = $info['newer_branch_target'];
                $newerBranchLatest = $this->formatVersion($info['newer_branch_latest']);
                $description .= __('• :name: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
                $description .= '  ↳ '.__('Also available: :branch (latest patch: :branchLatest) - new minor version', ['branch' => $newerBranchTarget, 'branchLatest' => $newerBranchLatest])."\n";
            } elseif ($isPatch) {
                $description .= __('• :name: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
            } else {
                $description .= __('• :name: :current (latest patch: :latest) → :target (new minor version available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget, 'latest' => $latest])."\n";
            }
        }

        $description .= "\n⚠️ ".__('It is recommended to test before switching the production version.');

        if ($hasUpgrades) {
            $description .= "\n\n📖 **".__('For minor version upgrades').'**: '.__('Read the Traefik changelog before upgrading to understand breaking changes and new features.');
        }

        return new DiscordMessage(
            title: ':warning: '.__('Coolify: Traefik proxy outdated'),
            description: $description,
            color: DiscordMessage::warningColor(),
        );
    }

    public function toTelegram(): array
    {
        $count = $this->servers->count();
        $hasUpgrades = $this->servers->contains(fn ($s) => ($s->outdatedInfo['type'] ?? 'patch_update') === 'minor_upgrade' ||
            isset($s->outdatedInfo['newer_branch_target'])
        );

        $message = '⚠️ '.__('Coolify: Traefik proxy outdated on :count server(s)!', ['count' => $count])."\n\n";
        $message .= __('Update recommended for security and features.')."\n";
        $message .= '📊 '.__('Affected servers:')."\n";

        foreach ($this->servers as $server) {
            $info = $server->outdatedInfo ?? [];
            $current = $this->formatVersion($info['current'] ?? 'unknown');
            $latest = $this->formatVersion($info['latest'] ?? 'unknown');
            $upgradeTarget = $this->getUpgradeTarget($info);
            $isPatch = ($info['type'] ?? 'patch_update') === 'patch_update';
            $hasNewerBranch = isset($info['newer_branch_target']);

            if ($isPatch && $hasNewerBranch) {
                $newerBranchTarget = $info['newer_branch_target'];
                $newerBranchLatest = $this->formatVersion($info['newer_branch_latest']);
                $message .= __('• :name: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
                $message .= '  ↳ '.__('Also available: :branch (latest patch: :branchLatest) - new minor version', ['branch' => $newerBranchTarget, 'branchLatest' => $newerBranchLatest])."\n";
            } elseif ($isPatch) {
                $message .= __('• :name: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
            } else {
                $message .= __('• :name: :current (latest patch: :latest) → :target (new minor version available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget, 'latest' => $latest])."\n";
            }
        }

        $message .= "\n⚠️ ".__('It is recommended to test before switching the production version.');

        if ($hasUpgrades) {
            $message .= "\n\n📖 ".__('For minor version upgrades: Read the Traefik changelog before upgrading to understand breaking changes and new features.');
        }

        return [
            'message' => $message,
            'buttons' => [],
        ];
    }

    public function toPushover(): PushoverMessage
    {
        $count = $this->servers->count();
        $hasUpgrades = $this->servers->contains(fn ($s) => ($s->outdatedInfo['type'] ?? 'patch_update') === 'minor_upgrade' ||
            isset($s->outdatedInfo['newer_branch_target'])
        );

        $message = __('Traefik proxy outdated on :count server(s)!', ['count' => $count])."\n";
        $message .= __('Affected servers:')."\n";

        foreach ($this->servers as $server) {
            $info = $server->outdatedInfo ?? [];
            $current = $this->formatVersion($info['current'] ?? 'unknown');
            $latest = $this->formatVersion($info['latest'] ?? 'unknown');
            $upgradeTarget = $this->getUpgradeTarget($info);
            $isPatch = ($info['type'] ?? 'patch_update') === 'patch_update';
            $hasNewerBranch = isset($info['newer_branch_target']);

            if ($isPatch && $hasNewerBranch) {
                $newerBranchTarget = $info['newer_branch_target'];
                $newerBranchLatest = $this->formatVersion($info['newer_branch_latest']);
                $message .= __('• :name: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
                $message .= '  '.__('Also: :branch (latest: :branchLatest) - new minor version', ['branch' => $newerBranchTarget, 'branchLatest' => $newerBranchLatest])."\n";
            } elseif ($isPatch) {
                $message .= __('• :name: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
            } else {
                $message .= __('• :name: :current (latest patch: :latest) → :target (new minor version available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget, 'latest' => $latest])."\n";
            }
        }

        $message .= "\n".__('It is recommended to test before switching the production version.');

        if ($hasUpgrades) {
            $message .= "\n\n".__('For minor version upgrades: Read the Traefik changelog before upgrading.');
        }

        return new PushoverMessage(
            title: __('Traefik proxy outdated'),
            level: 'warning',
            message: $message,
        );
    }

    public function toSlack(): SlackMessage
    {
        $count = $this->servers->count();
        $hasUpgrades = $this->servers->contains(fn ($s) => ($s->outdatedInfo['type'] ?? 'patch_update') === 'minor_upgrade' ||
            isset($s->outdatedInfo['newer_branch_target'])
        );

        $description = __('Traefik proxy outdated on :count server(s)!', ['count' => $count])."\n";
        $description .= '*'.__('Affected servers:')."*\n";

        foreach ($this->servers as $server) {
            $info = $server->outdatedInfo ?? [];
            $current = $this->formatVersion($info['current'] ?? 'unknown');
            $latest = $this->formatVersion($info['latest'] ?? 'unknown');
            $upgradeTarget = $this->getUpgradeTarget($info);
            $isPatch = ($info['type'] ?? 'patch_update') === 'patch_update';
            $hasNewerBranch = isset($info['newer_branch_target']);

            if ($isPatch && $hasNewerBranch) {
                $newerBranchTarget = $info['newer_branch_target'];
                $newerBranchLatest = $this->formatVersion($info['newer_branch_latest']);
                $description .= __('• `:name`: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
                $description .= '  ↳ '.__('Also available: :branch (latest patch: :branchLatest) - new minor version', ['branch' => $newerBranchTarget, 'branchLatest' => $newerBranchLatest])."\n";
            } elseif ($isPatch) {
                $description .= __('• `:name`: :current → :target (patch update available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget])."\n";
            } else {
                $description .= __('• `:name`: :current (latest patch: :latest) → :target (new minor version available)', ['name' => $server->name, 'current' => $current, 'target' => $upgradeTarget, 'latest' => $latest])."\n";
            }
        }

        $description .= "\n:warning: ".__('It is recommended to test before switching the production version.');

        if ($hasUpgrades) {
            $description .= "\n\n:book: ".__('For minor version upgrades: Read the Traefik changelog before upgrading to understand breaking changes and new features.');
        }

        return new SlackMessage(
            title: __('Coolify: Traefik proxy outdated'),
            description: $description,
            color: SlackMessage::warningColor()
        );
    }

    public function toWebhook(): array
    {
        $servers = $this->servers->map(function ($server) {
            $info = $server->outdatedInfo ?? [];

            $webhookData = [
                'name' => $server->name,
                'uuid' => $server->uuid,
                'current_version' => $info['current'] ?? 'unknown',
                'latest_version' => $info['latest'] ?? 'unknown',
                'update_type' => $info['type'] ?? 'patch_update',
            ];

            // For minor upgrades, include the upgrade target (e.g., "v3.6")
            if (($info['type'] ?? 'patch_update') === 'minor_upgrade' && isset($info['upgrade_target'])) {
                $webhookData['upgrade_target'] = $info['upgrade_target'];
            }

            // Include newer branch info if available
            if (isset($info['newer_branch_target'])) {
                $webhookData['newer_branch_target'] = $info['newer_branch_target'];
                $webhookData['newer_branch_latest'] = $info['newer_branch_latest'];
            }

            return $webhookData;
        })->toArray();

        return [
            'success' => false,
            'message' => 'Traefik proxy outdated',
            'event' => 'traefik_version_outdated',
            'affected_servers_count' => $this->servers->count(),
            'servers' => $servers,
        ];
    }
}
