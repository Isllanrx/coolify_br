@php
    $notificationMenuItems = [
        ['label' => __('Email'), 'route' => 'notifications.email', 'icon' => 'mail'],
        ['label' => __('Discord'), 'route' => 'notifications.discord', 'brandIcon' => 'discord'],
        ['label' => __('Telegram'), 'route' => 'notifications.telegram', 'brandIcon' => 'telegram'],
        ['label' => __('Slack'), 'route' => 'notifications.slack', 'brandIcon' => 'slack'],
        ['label' => __('Pushover'), 'route' => 'notifications.pushover', 'brandIcon' => 'pushover'],
        ['label' => __('Webhook'), 'route' => 'notifications.webhook', 'icon' => 'destinations'],
    ];
@endphp

{{-- These pages are in the Notifications submenu of the main sidebar (components/navbar.blade.php). --}}
<section class="w-full max-w-none">
    <header class="settings-mobile-header xl:hidden">
        <h1 class="settings-mobile-title">{{ __('Notifications') }}</h1>
        <p class="settings-mobile-description">{{ __('Configure how your team receives deployment and system alerts.') }}</p>
    </header>
    {{-- Feeds the page search with the Notifications pages. --}}
    <div hidden data-settings-search-items="{{ json_encode(settingsSearchItems(['Notifications' => $notificationMenuItems], [])) }}"></div>
    <div class="min-w-0">
        {{ $slot }}
    </div>
</section>
