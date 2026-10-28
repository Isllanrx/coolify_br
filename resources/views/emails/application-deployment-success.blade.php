<x-emails.layout>
@if ($pull_request_id === 0)
{{ __('A new version of :name is available at [:fqdn](:fqdn) .', ['name' => $name, 'fqdn' => $fqdn]) }}
@else
{{ __('Pull request #:id of :name deployed successfully [:fqdn](:fqdn).', ['id' => $pull_request_id, 'name' => $name, 'fqdn' => $fqdn]) }}
@endif

<x-emails.deployment-commit :commit="$commit ?? null" />
[{{ __('View Deployment Logs') }}]({{ $deployment_url }})

</x-emails.layout>
