<x-emails.layout>
{{ __(':name has been automatically stopped after :count restarts (limit: :max).', ['name' => $name, 'count' => $restart_count, 'max' => $max_restart_count]) }}

{{ __('The application appears to be in a restart loop. Please investigate the issue and redeploy when ready.') }}

{{ __('[Check what is going on](:url).', ['url' => $resource_url]) }}
</x-emails.layout>
