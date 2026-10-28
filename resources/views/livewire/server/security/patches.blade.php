<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('Security') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <x-process-dialog @startupdate.window="processDialogOpen = true" closeWithX size="xl">
        <x-slot:title>{{ __('Package Update Logs') }}</x-slot:title>
        <x-slot:content>
            <div class="flex h-full min-h-0 flex-col">
                <livewire:activity-monitor fullHeight />
            </div>
        </x-slot:content>
    </x-process-dialog>

    @php
        $updates = collect(data_get($result, 'updates', []))
            ->sortBy(fn ($update) => [data_get($update, 'security') ? 0 : 1, filled(data_get($update, 'risk')) ? 0 : 1, data_get($update, 'package')])
            ->values()
            ->all();
        $totalUpdates = count($updates);
        $heldUpdates = collect(data_get($result, 'held_updates', []))->sortBy('package')->values()->all();
        $packageManager = data_get($result, 'package_manager');
        $checkedAt = $server->settings->patch_checked_at;
        $fullUpgradeOnly = in_array($packageManager, \App\Actions\Server\CheckUpdates::FULL_UPGRADE_ONLY, true);
        $securityUpdates = data_get($result, 'security_updates');
        $rebootRequired = data_get($result, 'reboot_required') === true;
        $riskyUpdates = collect($updates)->filter(fn ($update) => filled(data_get($update, 'risk')))->count();
    @endphp

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="security" activeSubMenu="patches" />

        <div class="application-settings-form flex w-full flex-col gap-6">
            <form wire:submit="submit">
                <x-unsaved-bar action="submit" />

                <x-application.settings-section id="server-patching-overview-section" :title="__('Server patching')"
                    :helper="__('Discover and apply operating system package updates.')">
                    <x-slot:actions>
                        <x-beta-badge />
                        @if (isDev())
                            <x-forms.button type="button" wire:click="sendTestEmail">
                                {{ __('Send test email') }}
                            </x-forms.button>
                        @endif
                        @can('update', $server)
                            @if ($rebootRequired)
                                <x-modal-confirmation :title="__('Reboot server?')" :buttonTitle="__('Reboot server')" isErrorButton
                                    submitAction="rebootServer" :actions="[
                                        __('The server reboots in a few seconds.'),
                                        __('All containers on this server stop, and the containers with a restart policy start again after the reboot.'),
                                        __('The server is not reachable for a few minutes.'),
                                    ]"
                                    confirmationText="{{ $server->name }}"
                                    :confirmationLabel="__('Enter the server name to confirm')"
                                    :shortConfirmationLabel="__('Server name')" :confirmWithPassword="false"
                                    :step2ButtonText="__('Reboot server')" />
                            @endif
                            <x-forms.button type="button" wire:click="checkForUpdates" :disabled="$checking">
                                <x-reicon name="refresh" class="size-3.5" />
                                {{ __('Check for updates') }}
                            </x-forms.button>
                        @endcan
                    </x-slot:actions>

                    <div class="flex flex-col gap-4">

                        <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-600 dark:text-fg-dim">
                            <span>
                                {{ __('Last checked:') }}
                                @if ($checkedAt)
                                    <span title="{{ $checkedAt->toDateTimeString() }}">{{ $checkedAt->diffForHumans() }}</span>
                                @else
                                    {{ __('never') }}
                                @endif
                            </span>
                            @if ($checkedAt && ! $error)
                                @if ($totalUpdates === 0)
                                    <x-status-badge :status="__('Up to date')" type="success" />
                                @endif
                                @if ($securityUpdates > 0)
                                    <x-status-badge :status="trans_choice(':count security update|:count security updates', $securityUpdates)" type="error" />
                                @endif
                                @if ($rebootRequired)
                                    <x-status-badge :status="__('Reboot required')" type="warning" />
                                @endif
                            @endif
                        </div>

                        @php
                            $autoUpdatesSupported = \App\Actions\Server\ConfigureAutomaticSecurityUpdates::supportFor($packageManager);
                        @endphp
                        <div @class(['grid gap-4', 'lg:grid-cols-3' => $autoUpdatesSupported])>
                            <x-forms.input canGate="update" :canResource="$server" id="patchCheckFrequency"
                                :label="__('Check frequency')" :placeholder="__('weekly (default, on Sunday)')"
                                :helper="__('Cron expression or preset such as daily, weekly, or monthly. Leave empty for the default: weekly, at a fixed time on Sunday. Coolify notifies your team when updates are available and when a check starts to fail.')" />
                            @if ($autoUpdatesSupported)
                                <div class="w-full">
                                <div class="mb-1.5 flex h-4 w-full items-center gap-1.5">
                                    <label class="mb-0! flex items-center gap-1 text-sm font-medium leading-4">{{ __('Automatic security updates') }}</label>
                                    <x-helper :helper="($packageManager === 'apt' ? __('Uses unattended-upgrades for the security origins only.') : __('Uses dnf-automatic with upgrade_type = security.')).' '.__('Turning it off removes the Coolify configuration and keeps the operating system default.')" />
                                </div>
                                <div class="input flex items-center justify-between gap-2 pr-1!">
                                    <span class="flex min-w-0 items-center gap-2 truncate text-neutral-700 dark:text-fg-dim">
                                        <span @class(['size-1.5 shrink-0 rounded-full', 'bg-emerald-500' => $autoSecurityUpdates, 'bg-neutral-400 dark:bg-neutral-500' => ! $autoSecurityUpdates])></span>
                                        {{ $autoSecurityUpdates ? __('On, every day') : __('Off') }}
                                    </span>
                                    @can('update', $server)
                                        <div class="flex shrink-0" wire:key="auto-security-updates-{{ $autoSecurityUpdates ? 'on' : 'off' }}">
                                            @if ($autoSecurityUpdates)
                                                <x-modal-confirmation :title="__('Turn off automatic security updates?')"
                                                    submitAction="toggleAutomaticSecurityUpdates"
                                                    :warningMessage="__('Security fixes are then not installed automatically by Coolify.')"
                                                    temporaryDisableTwoStepConfirmation :confirmWithText="false"
                                                    :confirmWithPassword="false" :actions="[
                                                        __('Coolify removes its configuration from the server.'),
                                                        __('The server uses the default of the operating system again. Check it if you want no automatic updates at all.'),
                                                    ]"
                                                    :step2ButtonText="__('Turn off')">
                                                    <x-slot:trigger>
                                                        <button type="button"
                                                            class="h-6 shrink-0 rounded-md border border-neutral-200 bg-white px-2 text-[12px] font-medium text-neutral-800 hover:bg-neutral-50 dark:border-white/[0.1] dark:bg-white/[0.06] dark:text-fg dark:hover:bg-white/[0.1]">
                                                            {{ __('Turn off') }}
                                                        </button>
                                                    </x-slot:trigger>
                                                </x-modal-confirmation>
                                            @else
                                                <x-modal-confirmation :title="__('Turn on automatic security updates?')"
                                                    :warningMessage="__('Updates run without you. Your resources can stop for a short time during an update. You can turn this off at any time.')"
                                                    submitAction="toggleAutomaticSecurityUpdates"
                                                    temporaryDisableTwoStepConfirmation :confirmWithText="false"
                                                    :confirmWithPassword="false" :actions="[
                                                        __('Every day at the time in Maintenance window (time zone of the server), the server installs security updates without asking.'),
                                                        __('If Docker comes from the operating system packages (for example docker.io or moby-engine), a security update of Docker restarts it, and all containers on this server stop and start again. Docker from the Docker repository (docker-ce, as Coolify installs it) has no security updates there and is not updated.'),
                                                        __('Services that use an updated library (for example OpenSSL or glibc) can restart.'),
                                                        __('A new kernel is installed, but the server is not rebooted. Coolify then shows Reboot required.'),
                                                    ]"
                                                    :step2ButtonText="__('Turn on')">
                                                    <x-slot:trigger>
                                                        <button type="button" isHighlighted
                                                            class="h-6 shrink-0 rounded-md border px-2 text-[12px] font-medium">
                                                            {{ __('Turn on') }}
                                                        </button>
                                                    </x-slot:trigger>
                                                </x-modal-confirmation>
                                            @endif
                                        </div>
                                    @endcan
                                </div>
                            </div>
                            <x-forms.input canGate="update" :canResource="$server" id="autoSecurityUpdatesTime"
                                    :label="__('Maintenance window')" placeholder="03:00"
                                    :helper="__('Daily start time (24-hour HH:MM) in the time zone of the server. Updates that need a reboot are installed, but the server is not rebooted.')" />
                            @endif
                        </div>

                        <x-callout type="info" :title="__('Supported package managers')">
                            {{ __('Automated package discovery currently supports apk, apt, dnf, pacman, and zypper. Automatic security updates are available for apt and dnf (Debian, Ubuntu, and RHEL family)') }}@if ($packageManager && ! $autoUpdatesSupported){{ __('; this server uses :manager', ['manager' => $packageManager]) }}@endif.
                            {{ __('Status notifications can be managed from') }}
                            <a class="font-medium underline" href="{{ route('notifications.email') }}"
                                {{ wireNavigate() }}>{{ __('notification settings') }}</a>.
                        </x-callout>
                    </div>
                </x-application.settings-section>
            </form>

            @if ($checking)
                <div wire:poll.10s="loadResult">
                    <x-application.settings-section :title="__('Checking for updates')"
                        :helper="__('Package discovery runs in the background and can take several minutes.')">
                        <div class="flex items-center gap-3 py-4 text-sm text-neutral-600 dark:text-fg-dim">
                            <x-loading />
                            {{ __('Inspecting installed packages…') }}
                        </div>
                    </x-application.settings-section>
                </div>
            @elseif ($error)
                <x-application.settings-section :title="__('Package updates')"
                    :helper="__('Available operating system updates for this server.')">
                    <x-callout type="danger" :title="__('Could not check for updates')">
                        {{ $error }}
                    </x-callout>
                </x-application.settings-section>
            @elseif ($checkedAt && $totalUpdates === 0)
                <x-application.settings-section :title="__('Package updates')"
                    :helper="__('Available operating system updates for this server.')">
                    <x-empty size="sm" :title="__('Server is up to date')"
                        :description="__('No package updates are currently available.')" icon-name="check-circle" />
                </x-application.settings-section>
            @elseif ($totalUpdates > 0)
                <x-application.settings-section id="server-package-updates-section" :title="__('Package updates')"
                    :helper="$securityUpdates !== null
                        ? __(':total available, :security of them security :label.', ['total' => trans_choice(':count update|:count updates', $totalUpdates), 'security' => $securityUpdates, 'label' => trans_choice('update|updates', $securityUpdates)])
                        : __(':total available.', ['total' => trans_choice(':count update|:count updates', $totalUpdates)])"
                    flush>
                    @can('update', $server)
                        <x-slot:actions>
                            <x-modal-confirmation :title="__('Confirm package update?')" :buttonTitle="__('Update all packages')"
                                isHighlightedButton submitAction="updateAllPackages" :actions="[
                                    __('All packages will be updated to their latest available versions.'),
                                    $riskyUpdates > 0
                                        ? trans_choice(':count update can restart Docker, the running containers, or core system services, or need a reboot.|:count updates can restart Docker, the running containers, or core system services, or need a reboot.', $riskyUpdates)
                                        : __('Some services on the server can restart.'),
                                ]"
                                confirmationText="Update All Packages"
                                :confirmationLabel="__('Confirm by entering the text below')"
                                :shortConfirmationLabel="__('Confirmation')" :confirmWithPassword="false"
                                :step2ButtonText="__('Update All Packages')" />
                        </x-slot:actions>
                    @endcan

                    @if ($fullUpgradeOnly)
                        <div class="px-4 pt-4">
                            <x-callout type="info" :title="__('Full system upgrade only')">
                                {{ __('Arch Linux does not support partial upgrades, so packages cannot be updated one by one.') }}
                            </x-callout>
                        </div>
                    @endif

                    <div class="data-table">
                        <div class="data-table-header package-updates-table-grid">
                            <span>{{ __('Package') }}</span>
                            <span>{{ __('New version') }}</span>
                            <span class="text-right">{{ __('Action') }}</span>
                        </div>
                        @foreach ($updates as $update)
                            <div wire:key="package-update-{{ data_get($update, 'package') }}-{{ data_get($update, 'architecture') }}"
                                class="data-table-row package-updates-table-grid border-b border-neutral-200 last:border-b-0 dark:border-white/[0.08]">
                                <div class="flex min-w-0 items-center gap-2">
                                    @if (filled(data_get($update, 'risk')))
                                        <span class="shrink-0" data-tooltip="{{ \App\Actions\Server\CheckUpdates::RISK_LABELS[data_get($update, 'risk')] ?? '' }}">
                                            <x-reicon name="alert-triangle" class="size-4 text-red-500" />
                                            <span class="sr-only">{{ \App\Actions\Server\CheckUpdates::RISK_LABELS[data_get($update, 'risk')] ?? '' }}</span>
                                        </span>
                                    @endif
                                    <span class="min-w-0 truncate font-mono text-[12px] text-neutral-950 dark:text-fg">
                                        {{ data_get($update, 'package') }}
                                    </span>
                                    @if (data_get($update, 'security'))
                                        <x-status-badge :status="__('Security')" type="error" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-[12px] text-neutral-700 dark:text-fg-dim">
                                        {{ data_get($update, 'new_version') }}
                                    </p>
                                    @if (data_get($update, 'current_version') && data_get($update, 'current_version') !== 'unknown')
                                        <p class="mt-0.5 truncate text-[10px] text-neutral-500 dark:text-fg-faint">
                                            {{ __('Current:') }} {{ data_get($update, 'current_version') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex justify-end">
                                    @if (! $fullUpgradeOnly)
                                        @can('update', $server)
                                            <x-forms.button type="button"
                                                wire:click="updatePackage({{ Js::from(data_get($update, 'package')) }})">
                                                {{ __('Update') }}
                                            </x-forms.button>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div
                            class="flex min-h-11 items-center border-t border-neutral-200 px-4 text-[11px] text-neutral-500 dark:border-white/[0.08] dark:text-fg-faint">
                            {{ trans_choice(':count package update|:count package updates', $totalUpdates) }}
                        </div>
                    </div>
                </x-application.settings-section>
            @endif

            @if (! $checking && ! $error && count($heldUpdates) > 0)
                <x-application.settings-section id="server-held-updates-section" :title="__('Held back updates')"
                    :helper="__('The package manager does not install these updates. Phased rollout: Ubuntu releases the update to a part of all servers at a time, and this server gets it in a later phase. Held: a package hold (apt-mark hold) or a version pin (/etc/apk/world) on the server blocks the update.')"
                    flush>
                    <div class="data-table">
                        <div class="data-table-header package-updates-table-grid">
                            <span>{{ __('Package') }}</span>
                            <span>{{ __('New version') }}</span>
                            <span class="text-right">{{ __('Reason') }}</span>
                        </div>
                        @foreach ($heldUpdates as $update)
                            <div wire:key="held-update-{{ data_get($update, 'package') }}-{{ data_get($update, 'architecture') }}"
                                class="data-table-row package-updates-table-grid border-b border-neutral-200 last:border-b-0 dark:border-white/[0.08]">
                                <span class="min-w-0 truncate font-mono text-[12px] text-neutral-950 dark:text-fg">
                                    {{ data_get($update, 'package') }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-[12px] text-neutral-700 dark:text-fg-dim">
                                        {{ data_get($update, 'new_version') }}
                                    </p>
                                    <p class="mt-0.5 truncate text-[10px] text-neutral-500 dark:text-fg-faint">
                                        {{ __('Current:') }} {{ data_get($update, 'current_version') }}
                                    </p>
                                </div>
                                <div class="flex justify-end">
                                    <x-status-badge :status="\App\Actions\Server\CheckUpdates::HELD_REASON_LABELS[data_get($update, 'held_reason')] ?? __('Held')" type="warning" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-application.settings-section>
            @endif

        </div>
    </div>
</div>
