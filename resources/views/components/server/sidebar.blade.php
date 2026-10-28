@props(['server', 'activeMenu', 'activeSubMenu' => null])

@php
    $serverRouteParameters = ['server_uuid' => $server->uuid];
    $sentinelStatus = $server->sentinelStatus();
    $proxyNotRunning = $server->proxySet() && ($server->proxy->status ?? 'unknown') !== 'running';
    $sentinelStatusStartedAt = $server->sentinel_waiting_since ?? \Illuminate\Support\Carbon::parse($server->sentinel_updated_at);
    $sentinelTimeoutSeconds = $server->sentinel_waiting_since !== null
        ? $server->firstSentinelReportTimeoutSeconds()
        : $server->waitBeforeDoingSshCheck();
    $sentinelExpiresInMilliseconds = max(
        0,
        ($sentinelStatusStartedAt->copy()->addSeconds($sentinelTimeoutSeconds)->timestamp - now()->timestamp) * 1000,
    );
    $serverMenuItems = [
        [
            'label' => __('General'),
            'route' => 'server.show',
            'active' => $activeMenu === 'general',
            'icon' => 'settings',
            'group' => __('Settings'),
        ],
        [
            'label' => __('Advanced'),
            'route' => 'server.advanced',
            'active' => $activeMenu === 'advanced',
            'icon' => 'grid',
            'group' => __('Settings'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Private Key'),
            'route' => 'server.private-key',
            'active' => $activeMenu === 'private-key',
            'icon' => 'keys',
            'group' => __('Settings'),
        ],
        [
            'label' => __('Cloud Token'),
            'route' => 'server.cloud-provider-token',
            'active' => $activeMenu === 'cloud-provider-token',
            'icon' => 'subscription',
            'group' => __('Settings'),
            'visible' => (bool) ($server->hetzner_server_id || $server->vultr_instance_id || $server->digitalocean_droplet_id || $server->hostinger_virtual_machine_id),
        ],
        [
            'label' => __('CA Certificate'),
            'route' => 'server.ca-certificate',
            'active' => $activeMenu === 'ca-certificate',
            'icon' => 'file',
            'group' => __('Settings'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Cloudflare Tunnel'),
            'route' => 'server.cloudflare-tunnel',
            'active' => $activeMenu === 'cloudflare-tunnel',
            'icon' => 'cloudflare',
            'group' => __('Networking'),
            'visible' => ! $server->isLocalhost(),
        ],
        [
            'label' => __('Proxy'),
            'route' => 'server.proxy',
            'active' => $activeMenu === 'proxy',
            'icon' => 'network',
            'group' => __('Platform'),
            'visible' => ! $server->isSwarmWorker() && $server->canHostResources(),
            'warning' => $server->hasCurrentTraefikOutdatedInfo(),
            'disabled' => ! $server->isFunctional(),
            'tracks_proxy_configuration' => true,
            'children' => [
                ['label' => __('Configuration'), 'route' => 'server.proxy', 'active' => $activeSubMenu === 'configuration', 'icon' => 'settings'],
                ['label' => __('Dynamic Configurations'), 'route' => 'server.proxy.dynamic-confs', 'active' => $activeSubMenu === 'dynamic-confs', 'icon' => 'sliders', 'visible' => $server->proxySet()],
                ['label' => __('TLS Certificates'), 'route' => 'server.proxy.certificates', 'active' => $activeSubMenu === 'certificates', 'icon' => 'shield-star', 'visible' => $server->proxyType() === \App\Enums\ProxyTypes::TRAEFIK->value],
                ['label' => __('Logs'), 'route' => 'server.proxy.logs', 'active' => $activeSubMenu === 'logs', 'icon' => 'file-content', 'visible' => $server->proxySet(), 'navigate' => false],
            ],
        ],
        [
            'label' => __('Sentinel'),
            'route' => 'server.sentinel',
            'active' => $activeMenu === 'sentinel',
            'icon' => 'shield-star',
            'group' => __('Platform'),
            'visible' => ! $server->isSwarm() && $server->canHostResources() && auth()->user()?->can('viewSentinel', $server),
            'disabled' => ! $server->isFunctional(),
            'warning' => $server->isSentinelEnabled() && $sentinelStatus === 'out_of_sync',
            'tracks_sentinel_status' => true,
            'children' => [
                ['label' => __('Configuration'), 'route' => 'server.sentinel', 'active' => $activeSubMenu === 'configuration', 'icon' => 'settings'],
                ['label' => __('Logs'), 'route' => 'server.sentinel.logs', 'active' => $activeSubMenu === 'logs', 'icon' => 'file-content'],
            ],
        ],
        [
            'label' => __('Resources'),
            'route' => 'server.resources',
            'active' => $activeMenu === 'resources',
            'icon' => 'projects',
            'group' => __('Platform'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Terminal'),
            'route' => 'server.command',
            'active' => $activeMenu === 'terminal',
            'icon' => 'browser-terminal',
            'group' => __('Operations'),
            'navigate' => false,
            'visible' => auth()->user()?->can('canAccessTerminal'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Destinations'),
            'route' => 'server.destinations',
            'active' => $activeMenu === 'destinations',
            'icon' => 'destinations',
            'group' => __('Networking'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Swarm'),
            'route' => 'server.swarm',
            'active' => $activeMenu === 'swarm',
            'icon' => 'layers',
            'group' => __('Networking'),
            'visible' => $server->team->usesSwarm() && ! $server->isBuildServer() && ! $server->settings->is_cloudflare_tunnel,
        ],
        [
            'label' => __('Images'),
            'route' => 'server.docker-images',
            'active' => $activeMenu === 'docker-images',
            'icon' => 'layers',
            'group' => __('Operations'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Docker Cleanup'),
            'route' => 'server.docker-cleanup',
            'active' => $activeMenu === 'docker-cleanup',
            'icon' => 'broom',
            'group' => __('Operations'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('GitHub Runners'),
            'route' => 'server.github-runners',
            'active' => $activeMenu === 'github-runners',
            'icon' => 'play-circle',
            'group' => __('Operations'),
            'visible' => ! $server->isLocalhost(),
            'beta' => true,
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Registries'),
            'route' => 'server.registries',
            'active' => $activeMenu === 'registries',
            'icon' => 'layers',
            'group' => __('Operations'),
            'visible' => auth()->user()?->can('update', $server),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Log Drains'),
            'route' => 'server.log-drains',
            'active' => $activeMenu === 'log-drains',
            'icon' => 'notifications',
            'group' => __('Operations'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Metrics'),
            'route' => 'server.metrics',
            'active' => $activeMenu === 'metrics',
            'icon' => 'graph',
            'group' => __('Operations'),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Analytics'),
            'route' => 'server.analytics',
            'active' => $activeMenu === 'analytics',
            'icon' => 'analytics',
            'group' => __('Operations'),
            'visible' => ! $server->isSwarm() && ! $server->isBuildServer(),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Security'),
            'route' => 'server.security.patches',
            'active' => $activeMenu === 'security',
            'icon' => 'shield-alert',
            'group' => __('Security'),
            'visible' => auth()->user()?->can('update', $server),
            'disabled' => ! $server->isFunctional(),
            'children' => [
                ['label' => __('Patching'), 'route' => 'server.security.patches', 'active' => $activeSubMenu === 'patches', 'icon' => 'bandage', 'beta' => true],
                ['label' => __('Terminal Access'), 'route' => 'server.security.terminal-access', 'active' => $activeSubMenu === 'terminal-access', 'icon' => 'browser-terminal', 'navigate' => false],
            ],
        ],
        [
            'label' => __('Transfer'),
            'route' => 'server.transfer',
            'active' => $activeMenu === 'transfer',
            'icon' => 'arrow-right',
            'beta' => true,
            'group' => __('Operations'),
            'visible' => ! $server->isLocalhost() && auth()->user()?->can('update', $server),
            'disabled' => ! $server->isFunctional(),
        ],
        [
            'label' => __('Danger Zone'),
            'route' => 'server.delete',
            'active' => $activeMenu === 'danger',
            'icon' => 'danger',
            'group' => __('Danger Zone'),
            // Coolify host (id 0) cannot be deleted. Other servers may still use
            // host.docker.internal (e.g. Lima VMs) and must keep the Danger menu.
            'visible' => ! $server->is_coolify_host,
        ],
    ];

    $serverMenuItems = collect($serverMenuItems)
        ->filter(fn (array $item): bool => $item['visible'] ?? true)
        ->values();
    $groupedServerMenuItems = $serverMenuItems->groupBy('group');
@endphp

@php
    // The main sidebar and the mobile drawer show this menu (see components/navbar.blade.php).
    registerMainNavContext(0, __('Servers'), __('Back to the main menu'));
@endphp

{{-- Rendered twice: in the page (CSS hides it; it keeps the command palette items), and teleported into the main sidebar.
     Both copies keep their own warning state (proxy, Sentinel). --}}
@foreach (['page', 'main-nav'] as $placement)
@if ($placement === 'main-nav')
@teleport('#main-nav-context-0')
<div class="nav-context-menu"
    x-data="{
        proxyConfigurationPending: @js($server->hasPendingProxyConfiguration()),
        traefikOutdated: @js($server->hasCurrentTraefikOutdatedInfo()),
        proxyNotRunning: @js($proxyNotRunning),
        sentinelOutOfSync: @js($server->isSentinelEnabled() && $sentinelStatus === 'out_of_sync'),
        sentinelExpiryTimer: null,
        scheduleSentinelExpiry(delay) {
            clearTimeout(this.sentinelExpiryTimer);
            if (!this.sentinelOutOfSync) {
                this.sentinelExpiryTimer = setTimeout(() => this.sentinelOutOfSync = true, delay);
            }
        }
    }"
    x-init="scheduleSentinelExpiry(@js($sentinelExpiresInMilliseconds))"
    @proxy-configuration-state-changed.window="
        proxyConfigurationPending = $event.detail.pending;
        traefikOutdated = $event.detail.traefikOutdated;
        proxyNotRunning = $event.detail.proxyNotRunning;
    "
    @sentinel-status-changed.window="
        sentinelOutOfSync = $event.detail.outOfSync;
        scheduleSentinelExpiry($event.detail.expiresInMilliseconds);
    ">
@else
<aside class="application-settings-navigation in-main-nav min-w-0"
    data-settings-search-items="{{ json_encode(settingsSearchItems($groupedServerMenuItems, $serverRouteParameters)) }}"
    x-data="{
        proxyConfigurationPending: @js($server->hasPendingProxyConfiguration()),
        traefikOutdated: @js($server->hasCurrentTraefikOutdatedInfo()),
        proxyNotRunning: @js($proxyNotRunning),
        sentinelOutOfSync: @js($server->isSentinelEnabled() && $sentinelStatus === 'out_of_sync'),
        sentinelExpiryTimer: null,
        scheduleSentinelExpiry(delay) {
            clearTimeout(this.sentinelExpiryTimer);
            if (!this.sentinelOutOfSync) {
                this.sentinelExpiryTimer = setTimeout(() => this.sentinelOutOfSync = true, delay);
            }
        }
    }"
    x-init="scheduleSentinelExpiry(@js($sentinelExpiresInMilliseconds))"
    @proxy-configuration-state-changed.window="
        proxyConfigurationPending = $event.detail.pending;
        traefikOutdated = $event.detail.traefikOutdated;
        proxyNotRunning = $event.detail.proxyNotRunning;
    "
    @sentinel-status-changed.window="
        sentinelOutOfSync = $event.detail.outOfSync;
        scheduleSentinelExpiry($event.detail.expiresInMilliseconds);
    ">
@endif
    <nav aria-label="{{ __('Server configuration sections') }}"
        class="grid grid-cols-1 gap-0.5">
        @foreach ($groupedServerMenuItems as $groupLabel => $groupItems)
            @unless ($loop->first)
                <div class="my-2 border-t border-neutral-200 dark:border-white/[0.06]"
                    aria-hidden="true"></div>
            @endunless
            @foreach ($groupItems as $menuItem)
                @if ($menuItem['disabled'] ?? false)
                    <span wire:key="{{ $placement }}-server-settings-link-{{ str($menuItem['label'])->slug() }}"
                        class="menu-item cursor-not-allowed opacity-40" aria-disabled="true"
                        title="{{ __('Validate the server connection first.') }}">
                        <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                        <span class="menu-item-label">{{ $menuItem['label'] }}</span>
                    </span>
                    @continue
                @endif
                <a wire:key="{{ $placement }}-server-settings-link-{{ str($menuItem['label'])->slug() }}"
                    @class([
                        'menu-item',
                        'menu-item-active' => $menuItem['active'],
                    ])
                    @if ($menuItem['navigate'] ?? true) {{ wireNavigate() }} @endif
                    href="{{ route($menuItem['route'], $serverRouteParameters) }}">
                    <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                    <span class="menu-item-label">{{ $menuItem['label'] }}</span>
                    @if ($menuItem['tracks_proxy_configuration'] ?? false)
                        <x-reicon name="alert-triangle" x-cloak
                            x-show="proxyConfigurationPending || traefikOutdated || proxyNotRunning"
                            class="ml-auto size-3.5 shrink-0 text-orange-500 dark:text-warning" />
                    @elseif ($menuItem['tracks_sentinel_status'] ?? false)
                        <x-reicon name="alert-triangle" x-cloak x-show="sentinelOutOfSync"
                            class="ml-auto size-3.5 shrink-0 text-orange-500 dark:text-warning" />
                    @elseif ($menuItem['warning'] ?? false)
                        <x-reicon name="alert-triangle"
                            class="ml-auto size-3.5 shrink-0 text-orange-500 dark:text-warning" />
                    @elseif ($menuItem['beta'] ?? false)
                        <x-beta-badge class="ml-auto shrink-0" />
                    @endif
                </a>
                @if ($menuItem['active'] && isset($menuItem['children']))
                    <div class="col-span-full grid grid-cols-1 gap-0.5 border-l border-neutral-200 pl-2 dark:border-white/[0.08]">
                        @foreach (collect($menuItem['children'])->filter(fn (array $child): bool => $child['visible'] ?? true) as $child)
                            <a wire:key="{{ $placement }}-server-settings-child-{{ str($menuItem['label'].'-'.$child['label'])->slug() }}"
                                @class(['menu-item', 'menu-item-active' => $child['active']])
                                @if ($child['navigate'] ?? true) {{ wireNavigate() }} @endif
                                href="{{ route($child['route'], $serverRouteParameters) }}">
                                <x-reicon :name="$child['icon']" class="menu-item-icon" />
                                <span class="menu-item-label">{{ $child['label'] }}</span>
                                @if ($child['beta'] ?? false)
                                    <x-beta-badge class="ml-auto shrink-0" />
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endforeach
    </nav>
@if ($placement === 'main-nav')
</div>
@endteleport
@else
</aside>
@endif
@endforeach
