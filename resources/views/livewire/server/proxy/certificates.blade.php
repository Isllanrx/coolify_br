@php use App\Enums\ProxyTypes; @endphp

<div class="application-settings-form flex w-full flex-col gap-6">
    @if ($server->hasPendingProxyConfiguration())
        <x-callout type="warning" :title="__('Restart required')">
            {{ __('Restart the proxy to apply TLS certificate changes.') }}
        </x-callout>
    @endif
    @if ($server->proxyType() === ProxyTypes::TRAEFIK->value)
        <x-application.settings-section id="server-proxy-certificates-section" :title="__('TLS certificates')"
            x-init="$wire.loadTraefikCertificates()"
            :helper="__('Review certificates stored in Traefik\'s acme.json file. Deleting an entry removes it from local ACME storage. It does not revoke the certificate at the certificate authority. If a route still uses the domain, Traefik requests a new certificate after the restart.')">
            <x-slot:actions>
                <x-forms.button type="button" wire:click="loadTraefikCertificates"
                    wire:loading.attr="disabled" wire:target="loadTraefikCertificates">
                    {{ __('Refresh') }}
                </x-forms.button>
            </x-slot:actions>

            <div wire:loading.block wire:target="loadTraefikCertificates"
                class="hidden overflow-hidden rounded-lg ring-1 ring-neutral-200 dark:ring-white/[0.08]">
                <x-skeleton.table flush :rows="4" />
            </div>

            <div wire:loading.remove wire:target="loadTraefikCertificates">
                @if ($traefikCertificatesLoaded && count($traefikCertificates) === 0)
                    <x-empty size="sm" :title="__('No TLS certificates found')"
                        :description="__('Traefik\'s ACME storage does not contain certificate entries.')"
                        icon-name="shield-star" />
                @elseif (count($traefikCertificates) > 0)
                    <div class="overflow-hidden rounded-lg ring-1 ring-neutral-200 dark:ring-white/[0.08]">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-3xl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Domain') }}</th>
                                        <th>{{ __('Resolver') }}</th>
                                        <th>{{ __('Alternative names') }}</th>
                                        <th>{{ __('Expires') }}</th>
                                        <th><span class="sr-only">{{ __('Actions') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($traefikCertificates as $certificate)
                                        <tr wire:key="traefik-certificate-{{ $certificate['id'] }}">
                                            <td class="font-medium text-neutral-950 dark:text-fg">
                                                {{ $certificate['main_domain'] }}
                                            </td>
                                            <td>
                                                <span class="font-mono text-xs">{{ $certificate['resolver'] }}</span>
                                                @if ($certificate['store'])
                                                    <span class="text-xs text-neutral-500 dark:text-fg-dim">
                                                        ({{ $certificate['store'] }})
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (count($certificate['sans']) > 0)
                                                    <div class="flex max-w-md flex-wrap gap-1">
                                                        @foreach ($certificate['sans'] as $domain)
                                                            <span
                                                                class="rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-xs text-neutral-700 dark:bg-white/[0.06] dark:text-fg-dim">
                                                                {{ $domain }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-neutral-500 dark:text-fg-dim">{{ __('None') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $certificate['expires_at'] ?? __('Unknown') }}
                                            </td>
                                            <td class="text-right">
                                                @can('update', $server)
                                                    <x-modal-confirmation :title="__('Delete TLS Certificate?')"
                                                        :buttonTitle="__('Delete')"
                                                        submitAction="deleteTraefikCertificate({{ $certificate['id'] }})"
                                                        :actions="[
                                                            __('Save the current acme.json as a backup that you can restore below.'),
                                                            __('Delete the certificate for :domain from acme.json.', ['domain' => $certificate['main_domain']]),
                                                        ]"
                                                        :warningMessage="__('The proxy keeps using the certificate until you restart it. If a route still uses the domain, Traefik requests a new certificate after the restart. You can restore the backup below to undo this.')"
                                                        confirmationText="{{ $certificate['main_domain'] }}"
                                                        :confirmationLabel="__('Confirm by entering the domain')"
                                                        :shortConfirmationLabel="__('Domain')"
                                                        :step2ButtonText="__('Delete Certificate')"
                                                        isErrorButton :confirmWithPassword="false"
                                                        :confirmWithText="true" />
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @can('manageProxy', $server)
                    @if (count($traefikAcmeBackups) > 0)
                        <div class="mt-6 flex flex-col gap-2">
                            <div>
                                <h4 class="text-sm font-medium text-neutral-950 dark:text-fg">{{ __('acme.json backups') }}</h4>
                                <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                                    {{ __('Coolify saves a copy of acme.json before it changes the file and keeps the last') }} {{ \App\Actions\Proxy\ListTraefikAcmeBackups::KEEP }} copies.
                                </p>
                            </div>
                            <div class="overflow-hidden rounded-lg ring-1 ring-neutral-200 dark:ring-white/[0.08]">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-2xl">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Backup') }}</th>
                                                <th>{{ __('Created') }}</th>
                                                <th>{{ __('Size') }}</th>
                                                <th><span class="sr-only">{{ __('Actions') }}</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($traefikAcmeBackups as $backup)
                                                <tr wire:key="traefik-acme-backup-{{ $backup['name'] }}">
                                                    <td class="font-mono text-xs text-neutral-950 dark:text-fg">
                                                        {{ $backup['name'] }}
                                                    </td>
                                                    <td>{{ $backup['created_at'] }}</td>
                                                    <td>{{ formatBytes($backup['size']) }}</td>
                                                    <td>
                                                        <div class="flex justify-end gap-2">
                                                            <x-modal-confirmation :title="__('Restore acme.json Backup?')"
                                                                :buttonTitle="__('Restore')"
                                                                submitAction="restoreTraefikAcmeBackup('{{ $backup['name'] }}')"
                                                                :checkboxes="[
                                                                    ['id' => 'restartProxyAfterAcmeRestore', 'label' => __('Restart the proxy now. Sites on this server are unavailable for a few seconds.')],
                                                                ]"
                                                                :actions="[
                                                                    __('Save the current acme.json as a new backup.'),
                                                                    __('Replace acme.json with the backup from :date.', ['date' => $backup['created_at']]),
                                                                ]"
                                                                :warningMessage="__('Until the proxy restarts, it keeps its loaded certificates and can write them back to acme.json. Certificates issued after this backup are removed from acme.json.')"
                                                                :step2ButtonText="__('Restore Backup')"
                                                                :confirmWithPassword="false"
                                                                :confirmWithText="false" />
                                                            <x-modal-confirmation :title="__('Delete acme.json Backup?')"
                                                                :buttonTitle="__('Delete')"
                                                                submitAction="deleteTraefikAcmeBackup('{{ $backup['name'] }}')"
                                                                :actions="[__('Delete the backup :name.', ['name' => $backup['name']])]"
                                                                :step2ButtonText="__('Delete Backup')"
                                                                isErrorButton :confirmWithPassword="false"
                                                                :confirmWithText="false" />
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan
            </div>
        </x-application.settings-section>
    @endif

</div>
