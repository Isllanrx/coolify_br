<x-emails.layout>
{{ __(':name has been stopped.', ['name' => $name]) }}

{{ __('If it was your intention to stop this application, you can ignore this email.') }}

{{ __('If not, [check what is going on](:url).', ['url' => $application_url]) }}
</x-emails.layout>
