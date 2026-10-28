<x-emails.layout>
@if ($pull_request_id === 0)
{{ __('Failed to deploy a new version of :name at [:fqdn](:fqdn) .', ['name' => $name, 'fqdn' => $fqdn]) }}
@else
{{ __('Failed to deploy a pull request #:id of :name at [:fqdn](:fqdn).', ['id' => $pull_request_id, 'name' => $name, 'fqdn' => $fqdn]) }}
@endif

<x-emails.deployment-commit :commit="$commit ?? null" />
[{{ __('View Deployment Logs') }}]({{ $deployment_url }})
</x-emails.layout>
