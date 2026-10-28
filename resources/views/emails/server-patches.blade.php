<x-emails.layout>
{{ __(':count package updates are available for your server :name.', ['count' => $total_updates, 'name' => $name]) }}

## {{ __('Summary') }}

- {{ __('Operating System: :value', ['value' => ucfirst($osId)]) }}
- {{ __('Package Manager: :value', ['value' => $package_manager]) }}
- {{ __('Total Updates: :value', ['value' => $total_updates]) }}
@if (isset($security_updates))
- {{ __('Security Updates: :value', ['value' => $security_updates]) }}
@endif
@if ($reboot_required ?? false)
- {{ __('A reboot is required to apply installed updates.') }}
@endif

## {{ __('Available Updates') }}

@if ($total_updates > 0)
@foreach ($updates as $update)

{{ __('Package: :package (:architecture), from version :current to :new at repository :repository', ['package' => $update['package'].(($update['security'] ?? false) ? ' [security]' : ''), 'architecture' => $update['architecture'] ?? 'unknown', 'current' => $update['current_version'], 'new' => $update['new_version'], 'repository' => $update['repository'] ?? 'Unknown']) }}
@endforeach

## {{ __('Security Considerations') }}

{{ __('Some of these updates may include important security patches. We recommend reviewing and applying these updates promptly.') }}

### {{ __('Critical packages that may require container/server/service restarts:') }}
@php
$criticalPackages = collect($updates)->filter(fn ($update) => filled($update['risk'] ?? null));
@endphp

@if ($criticalPackages->count() > 0)
@foreach ($criticalPackages as $package)
- {{ __(':package: :current → :new', ['package' => $package['package'], 'current' => $package['current_version'], 'new' => $package['new_version']]) }}
@endforeach
@else
{{ __('No critical packages requiring container restarts detected.') }}
@endif

## {{ __('Next Steps') }}

1. {{ __('Review the available updates') }}
2. {{ __('Plan maintenance window if critical packages are involved') }}
3. {{ __('Apply updates through the Coolify dashboard') }}
4. {{ __('Monitor services after updates are applied') }}
@else
{{ __('Your server is up to date! No packages require updating at this time.') }}
@endif

---

{{ __('You can manage server patches in your [Coolify Dashboard](:url).', ['url' => $server_url]) }}
</x-emails.layout>
